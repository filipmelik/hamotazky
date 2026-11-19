<?php
declare (strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiErrorHandlingTest extends WebTestCase
{

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[DataProvider('invalidGetRequestsProvider')]
    public function testInvalidGetRequestsReturnJsonError(string $uri, int $expectedStatus): void
    {
        // when
        $this->client->request('GET', $uri);

        // then
        $this->assertJsonError($expectedStatus);
    }

    public static function invalidGetRequestsProvider(): iterable
    {
        yield 'unknown topic id' => ['/api/v1/questions-by-topic-id?topicId=99999', 404];
        yield 'decimal topic id' => ['/api/v1/questions-by-topic-id?topicId=1.5', 400];
        yield 'negative topic id' => ['/api/v1/questions-by-topic-id?topicId=-1', 400];
        yield 'array topic id' => ['/api/v1/questions-by-topic-id?topicId[]=1', 400];
        yield 'array licence class' => ['/api/v1/topics?licenceClass[]=A', 400];
        yield 'array licence class for test' => ['/api/v1/get-test?licenceClass[]=A', 400];
        yield 'array count' => ['/api/v1/random-questions?count[]=1', 400];
        yield 'array topic slug' => ['/api/v1/random-questions?topicSlug[]=q-kody', 400];
        yield 'missing question ids' => ['/api/v1/questions/', 404];
        yield 'unknown api route' => ['/api/v1/nope', 404];
        yield 'wrong method' => ['/api/v1/evaluate-test', 405];
    }

    public function testWrongMethodKeepsAllowHeader(): void
    {
        // when
        $this->client->request('GET', '/api/v1/evaluate-test');

        // then
        $this->assertJsonError(405);
        $this->assertResponseHeaderSame('allow', 'POST, OPTIONS');
    }

    #[DataProvider('invalidEvaluateTestBodyProvider')]
    public function testInvalidEvaluateTestBodyReturnsJsonError(string $body, string $expectedErrorPart): void
    {
        // when
        $this->client->request('POST', '/api/v1/evaluate-test', content: $body);

        // then
        $data = $this->assertJsonError(400);
        $this->assertStringContainsString($expectedErrorPart, $data['error']);
    }

    public static function invalidEvaluateTestBodyProvider(): iterable
    {
        $part = fn (array $overrides) => json_encode([
            'answers' => [],
            'test' => ['licenceClass' => 'A', 'testParts' => [$overrides]],
        ]);
        $question = ['questionId' => '1', 'answers' => [['answerId' => '1', 'isCorrect' => true]]];

        yield 'empty body' => ['', 'Request body'];
        yield 'not json' => ['not json', 'Request body'];
        yield 'json list' => ['[]', 'Request body'];
        yield 'missing test' => ['{"answers":{}}', 'Request body'];
        yield 'answers not object' => ['{"answers":"x","test":{}}', 'Request body'];
        yield 'missing licence class' => ['{"answers":{},"test":{}}', 'test.licenceClass'];
        yield 'non-string licence class' => ['{"answers":{},"test":{"licenceClass":[1]}}', 'test.licenceClass'];
        yield 'unknown licence class' => ['{"answers":{},"test":{"licenceClass":"X"}}', 'Unknown licence class'];
        yield 'non-string answer' => ['{"answers":{"1":5},"test":{"licenceClass":"A"}}', 'answers'];
        yield 'missing test parts' => ['{"answers":{},"test":{"licenceClass":"A"}}', 'test.testParts'];
        yield 'test parts not array' => ['{"answers":{},"test":{"licenceClass":"A","testParts":"x"}}', 'test.testParts'];
        yield 'unknown topic group' => [
            $part(['topicGroup' => ['topicGroupId' => '9'], 'topicGroupQuestions' => [$question]]),
            'topicGroupId',
        ];
        yield 'no questions in group' => [
            $part(['topicGroup' => ['topicGroupId' => '1'], 'topicGroupQuestions' => []]),
            'topicGroupQuestions',
        ];
        yield 'question without id' => [
            $part(['topicGroup' => ['topicGroupId' => '1'], 'topicGroupQuestions' => ['x']]),
            'questionId',
        ];
        yield 'answer without isCorrect' => [
            $part(['topicGroup' => ['topicGroupId' => '1'], 'topicGroupQuestions' => [
                ['questionId' => '1', 'answers' => [['answerId' => '1']]],
            ]]),
            'isCorrect',
        ];
    }

    public function testValidEvaluateTestStillWorks(): void
    {
        // given
        $this->client->request('GET', '/api/v1/get-test?licenceClass=N');
        $test = json_decode($this->client->getResponse()->getContent(), true);

        // when
        $this->client->request('POST', '/api/v1/evaluate-test', content: json_encode([
            'answers' => new \stdClass(),
            'test' => $test,
        ]));

        // then
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['result']['passedOverall']);
    }

    #[DataProvider('unknownTopicPagesProvider')]
    public function testUnknownTopicSlugPageReturns404(string $uri): void
    {
        // when
        $this->client->request('GET', $uri);

        // then
        $this->assertResponseStatusCodeSame(404);
    }

    public static function unknownTopicPagesProvider(): iterable
    {
        yield 'listing' => ['/harec/prohlizeni/nope'];
        yield 'practice' => ['/novice/procvicovani/nope'];
    }

    private function assertJsonError(int $expectedStatus): array
    {
        $this->assertResponseStatusCodeSame($expectedStatus);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertResponseHeaderSame('access-control-allow-origin', '*');

        $data = json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($expectedStatus, $data['status']);
        $this->assertIsString($data['error']);

        return $data;
    }

}
