<?php

namespace Pilotabai\CompetitionDbBundle\Tests\Controller\Api;

use Pilotabai\CompetitionDbBundle\Tests\ApiTestCase;

class TokenControllerTest extends ApiTestCase
{
    private $username = 'nicolaspb';
    private $password = '123456pw';

    public function testPOSTCreateToken()
    {
        $response = $this->client->post('api/tokens', [
            'auth' => [$this->username, $this->password]
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->asserter()->assertResponsePropertyExists(
            $response,
            'token'
        );
    }

    public function testPOSTTokenInvalidCredentials()
    {
        $response = $this->client->post('api/tokens', [
            'auth' => [$this->username, $this->password.'Incorrect']
        ]);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/problem+json', $response->getHeader('Content-Type')[0]);
        $this->asserter()->assertResponsePropertyEquals($response, 'type', 'about:blank');
        $this->asserter()->assertResponsePropertyEquals($response, 'title', 'Unauthorized');
        $this->asserter()->assertResponsePropertyEquals($response, 'detail', 'Invalid credentials.');
    }
}