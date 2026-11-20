<?php
declare (strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndividualTestControllerTest extends WebTestCase
{

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testResumeTestReturnsTestHtml(): void
    {
        // given
        $test = $this->getTest('A');

        // when
        $this->client->request('POST', '/test/resume', ['test' => base64_encode(json_encode($test))]);

        // then
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($test, $data['rawTestContent']);
        $firstQuestionId = $test['testParts'][0]['topicGroupQuestions'][0]['questionId'];
        $this->assertStringContainsString('js-question-box-' . $firstQuestionId, $data['responseHtml']);
    }

    #[DataProvider('licenceClassProvider')]
    public function testEvaluateTestWithAllCorrectAnswersPasses(string $licenceClass, string $uri): void
    {
        // given
        $test = $this->getTest($licenceClass);
        $answers = [];
        foreach ($test['testParts'] as $testPart) {
            foreach ($testPart['topicGroupQuestions'] as $question) {
                foreach ($question['answers'] as $answer) {
                    if ($answer['isCorrect']) {
                        $answers[(string)$question['questionId']] = (string)$answer['answerId'];
                    }
                }
            }
        }

        // when
        $this->client->request('POST', $uri, [
            'test'    => base64_encode(json_encode($test)),
            'answers' => base64_encode(json_encode($answers)),
        ]);

        // then
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('main', 'Gratulujeme');
    }

    public static function licenceClassProvider(): iterable
    {
        yield 'harec' => ['A', '/harec/test/vyhodnoceni'];
        yield 'novice' => ['N', '/novice/test/vyhodnoceni'];
    }

    private function getTest(string $licenceClass): array
    {
        $this->client->request('GET', '/api/v1/get-test?licenceClass=' . $licenceClass);
        $this->assertResponseIsSuccessful();

        return json_decode($this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

}
