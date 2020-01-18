<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Document\Entry;
use App\Service\DataFakerService;
use App\Service\StatisticsExtractionService;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class EntryFunctionalTest.
 *
 * Contains functional tests for the App\Document\Entry.
 */
class EntryFunctionalTest extends ApiTestCase
{
    private $fakerService;
    private $extractionService;
    private $documentManager;

    /**
     * EntryFunctionalTest constructor.
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param \App\Service\DataFakerService $fakerService
     * @param \App\Service\StatisticsExtractionService $extractionService
     */
    public function __construct(DocumentManager $documentManager, DataFakerService $fakerService, StatisticsExtractionService $extractionService)
    {
        $this->fakerService = $fakerService;
        $this->extractionService = $extractionService;
        $this->documentManager = $documentManager;

        parent::__construct();
    }

    /**
     * Test that the Entry "get" collections endpoint works.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testGetCollectionExists(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/entries');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testExtractStatistics(): void
    {
        $this->fakerService->cleanMongoDatabase();

        // @TODO: Replace elasticsearch service with mock.
        $this->fakerService->mockElasticsearch();

        $this->extractionService->extractStatistics();

        $entries = $this->documentManager->getRepository(Entry::class)->findAll();

        $this->assertEquals(10, count($entries), 'Number of entries should be 10');
    }
}
