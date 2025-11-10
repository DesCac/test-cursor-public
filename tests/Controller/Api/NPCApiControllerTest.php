<?php

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NPCApiControllerTest extends WebTestCase
{
    public function testGetNPCReturnsJson(): void
    {
        $client = static::createClient();

        // This test assumes fixtures are loaded
        $client->request('GET', '/api/npcs/1');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('nodes', $data);
    }

    public function testGetNonExistentNPCReturns404(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/npcs/99999');

        $this->assertResponseStatusCodeSame(404);
    }
}
