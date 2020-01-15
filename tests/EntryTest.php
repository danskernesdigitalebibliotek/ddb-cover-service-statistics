<?php


namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;

class EntryTest extends ApiTestCase
{
    public function testGetCollectionExists(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/entries');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
