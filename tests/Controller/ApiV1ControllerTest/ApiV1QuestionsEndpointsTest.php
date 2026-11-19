<?php
declare (strict_types=1);

namespace App\Tests\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiV1QuestionsEndpointsTest extends WebTestCase
{

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testQuestionsByIdsKeepsRequestedOrderAndIgnoresDuplicates(): void
    {
        // when
        $data = $this->requestJson('/api/v1/questions/5,1,3,5');

        // then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSame(['5', '1', '3'], array_column($data, 'questionId'));
        foreach ($data as $question) {
            $this->assertNotEmpty($question['text']);
            $this->assertNotEmpty($question['answers']);
            $this->assertCount(1, array_filter($question['answers'], fn (array $a) => $a['isCorrect']));
        }
    }

    public function testQuestionsByIdsReturns404ForUnknownIds(): void
    {
        // when
        $data = $this->requestJson('/api/v1/questions/1,99999');

        // then
        $this->assertResponseStatusCodeSame(404);
        $this->assertStringContainsString('99999', $data['error']);
    }

    #[DataProvider('invalidQuestionIdsProvider')]
    public function testQuestionsByIdsRejectsInvalidInput(string $questionIds): void
    {
        // when
        $data = $this->requestJson('/api/v1/questions/' . $questionIds);

        // then
        $this->assertResponseStatusCodeSame(400);
        $this->assertArrayHasKey('error', $data);
    }

    public static function invalidQuestionIdsProvider(): iterable
    {
        yield 'only separators' => [',,'];
        yield 'invalid characters' => ['1;drop'];
        yield 'too many ids' => [implode(',', range(1, 101))];
    }

    public function testRandomQuestionsDefaultsToTenQuestions(): void
    {
        // when
        $data = $this->requestJson('/api/v1/random-questions');

        // then
        $this->assertResponseIsSuccessful();
        $this->assertCount(10, $data);
        $this->assertSame($data, array_values(array_unique($data, SORT_REGULAR)));
    }

    public function testRandomQuestionsAreLimitedToTopicsAndLicenceClass(): void
    {
        // given
        $db = static::getContainer()->get(Connection::class);
        $topicIds = $db->fetchFirstColumn(
            'SELECT `topicId` FROM Topic WHERE `slug` IN (?)',
            [['q-kody', 'zkratky']],
            [ArrayParameterType::STRING],
        );

        // when
        $data = $this->requestJson('/api/v1/random-questions?topicSlug=q-kody,zkratky&licenceClass=N&count=50');

        // then
        $this->assertResponseIsSuccessful();
        $this->assertCount(50, $data);

        $questionIds = array_column($data, 'questionId');
        $this->assertCount(50, array_unique($questionIds));
        foreach ($data as $question) {
            $this->assertContains($question['topicId'], array_map('strval', $topicIds));
        }

        $noviceQuestionsCount = (int)$db->fetchOne(
            'SELECT COUNT(*) FROM Question2LicenceClass WHERE `licenceClass` = ? AND `questionId` IN (?)',
            ['N', $questionIds],
            [ParameterType::STRING, ArrayParameterType::STRING],
        );
        $this->assertSame(50, $noviceQuestionsCount);
    }

    public function testRandomQuestionsReturnsAllWhenCountExceedsAvailable(): void
    {
        // given
        $db = static::getContainer()->get(Connection::class);
        $available = (int)$db->fetchOne(
            'SELECT COUNT(*) FROM Topic2Question t2q JOIN Topic t ON (t.`topicId` = t2q.`topicId`) WHERE t.`slug` = ?',
            ['bezpecnost'],
        );

        // when
        $data = $this->requestJson('/api/v1/random-questions?topicSlug=bezpecnost&count=100');

        // then
        $this->assertResponseIsSuccessful();
        $this->assertLessThan(100, $available);
        $this->assertCount($available, $data);
    }

    #[DataProvider('invalidRandomQuestionsQueryProvider')]
    public function testRandomQuestionsRejectsInvalidInput(string $query, string $expectedErrorPart): void
    {
        // when
        $data = $this->requestJson('/api/v1/random-questions?' . $query);

        // then
        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString($expectedErrorPart, $data['error']);
    }

    public static function invalidRandomQuestionsQueryProvider(): iterable
    {
        yield 'unknown slug' => ['topicSlug=q-kody,nope', 'nope'];
        yield 'empty slug' => ['topicSlug=', 'topicSlug'];
        yield 'zero count' => ['count=0', 'count'];
        yield 'non-numeric count' => ['count=abc', 'count'];
        yield 'count over limit' => ['count=101', 'count'];
        yield 'invalid licence class' => ['licenceClass=X', 'licenceClass'];
    }

    #[DataProvider('getOnlyEndpointsProvider')]
    public function testGetEndpointsRejectOtherMethods(string $uri): void
    {
        // when
        $this->client->request('POST', $uri);

        // then
        $this->assertResponseStatusCodeSame(405);
    }

    public static function getOnlyEndpointsProvider(): iterable
    {
        yield 'topics' => ['/api/v1/topics'];
        yield 'grouped topics' => ['/api/v1/grouped-topics'];
        yield 'questions by topic id' => ['/api/v1/questions-by-topic-id?topicId=1'];
        yield 'questions by ids' => ['/api/v1/questions/1'];
        yield 'random questions' => ['/api/v1/random-questions'];
        yield 'test' => ['/api/v1/get-test?licenceClass=A'];
    }

    private function requestJson(string $uri): array
    {
        $this->client->request('GET', $uri);

        return json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

}
