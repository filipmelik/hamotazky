<?php
declare (strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiV1ControllerTest extends WebTestCase
{

    public function testTopicsEndpoint(): void
    {
        // given
        $client = static::createClient();

        // when
        $client->request('GET', '/api/v1/topics');
        $response = $client->getResponse();
        $actual = $response->getContent();

        // then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSame('wassup', $actual);
    }

}