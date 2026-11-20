<?php
declare (strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LlmsTxtControllerTest extends WebTestCase
{

    public function testLlmsTxtIsMarkdownOverviewWithTopicLinks(): void
    {
        // given
        $client = static::createClient();

        // when
        $client->request('GET', '/llms.txt');

        // then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/plain; charset=UTF-8');

        $content = $client->getResponse()->getContent();
        $this->assertStringStartsWith("# hamotazky.cz\n\n> ", $content);
        $this->assertStringContainsString('## Class A (HAREC)', $content);
        $this->assertStringContainsString('## Class N (NOVICE)', $content);
        $this->assertMatchesRegularExpression('~\]\(http://localhost/harec/prohlizeni/[a-z0-9-]+\): \d+ class A questions~', $content);
        $this->assertMatchesRegularExpression('~\]\(http://localhost/novice/prohlizeni/[a-z0-9-]+\): \d+ class N questions~', $content);
        $this->assertStringContainsString('](http://localhost/api/v1/docs/)', $content);
    }

}
