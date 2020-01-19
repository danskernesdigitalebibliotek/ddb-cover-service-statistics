<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Document\Entry;
use App\Document\ExtractionResult;
use App\Repository\ExtractionResultRepository;
use App\Service\DataFakerService;
use App\Service\ElasticsearchService;
use App\Service\ElasticsearchServiceMock;
use App\Service\StatisticsExtractionService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class FunctionalTest.
 *
 * Contains functional tests for the App\Document\Entry.
 */
class FunctionalTest extends ApiTestCase
{
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

    /**
     * Test that entries can be extracted from an elasticsearch result.
     *
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testExtractStatistics(): void
    {
        self::bootKernel();

        // Get special container that allows fetching private services
        $container = self::$container;

        // Get services.
        $extractionResultRepository = $container->get(ExtractionResultRepository::class);
        $logger = $container->get(LoggerInterface::class);
        $documentManager = $container->get(DocumentManager::class);
        $httpClient = $container->get(HttpClientInterface::class);

        // Clean database
        $this->cleanMongoDatabase();

        // Create an extraction result from yesterday, to only extract one day.
        $extractionResult = new ExtractionResult();
        $extractionResult->setNumberOfEntriesAdded(0);
        $extractionResult->setDate(new \DateTime('-1 day'));
        $documentManager->persist($extractionResult);
        $documentManager->flush();

        // Create mock
        $elasticSearchServiceMock = new ElasticsearchServiceMock($httpClient, '');

        $extractionService = new StatisticsExtractionService($documentManager, $extractionResultRepository, $logger, $elasticSearchServiceMock);

        $extractionService->extractStatistics();

        $entries = $documentManager->getRepository(Entry::class)->findAll();

        // Assert that 20 Entry documents exist in the database.
        $this->assertEquals(20, count($entries), 'Number of entries in database should be 20');

        // Assert that the entries can be extracted from the API.
        $client = static::createClient();
        $response = $client->request('GET', '/entries');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $content = $response->toArray();
        $this->assertEquals(20, count($content['hydra:member']), 'Number of entries in response should be 20');

        // After the results have been delivered, they should be removed from the database
        // Assert that 0 Entry documents exist in the database.
        $entries = $documentManager->getRepository(Entry::class)->findAll();
        $this->assertEquals(0, count($entries), 'Number of entries in database should be 0');

        // Since an extraction was added before the extraction was run
        // we expect 2 ExtractionResults to exist in the database.
        $extractionResults = $documentManager->getRepository(ExtractionResult::class)->findAll();
        $this->assertEquals(2, count($extractionResults), 'Number of extraction results in database should be 2');
    }

    public function testElasticsearchService()
    {
        $expectedResult = [(object) [
            'id' => 'firstHit',
        ], ];

        $responses = [
            new MockResponse(),
            new MockResponse(json_encode(
                (object) ['hits' => (object) [
                    'hits' => $expectedResult,
                ], ]
            ), [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]),
        ];

        $clientMock = new MockHttpClient($responses);

        $elasticsearchService = new ElasticsearchService($clientMock, 'http://elasticsearch:9200/');
        $result = $elasticsearchService->getLogsFromElasticsearch(new \DateTime('-1 day'), 'test');

        $this->assertEquals($expectedResult, $result, 'Result from elasticsearch does not match expected result');
    }

    public function testDataFakerService()
    {
        self::bootKernel();

        // Get special container that allows fetching private services
        $container = self::$container;
        $documentManager = $container->get(DocumentManager::class);

        $responses = [
            new MockResponse('', ['http_code' => 200]),
        ];

        for ($i = 0; $i < 20; ++$i) {
            $responses[] = new MockResponse('', ['http_code' => 200]);
        }

        $clientMock = new MockHttpClient($responses);

        $dataFakerService = new DataFakerService($clientMock, $documentManager, 'http://elasticsearch:9200/');
        $result = $dataFakerService->createElasticsearchTestData(new \DateTime());
        $this->assertTrue($result, 'createElasticsearchTestData should finish executing');
    }

    /**
     * Remove all content from mongo database.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    private function cleanMongoDatabase()
    {
        $container = self::$container;
        $documentManager = $container->get(DocumentManager::class);

        $results = $documentManager->getRepository(ExtractionResult::class)->findAll();
        foreach ($results as $result) {
            $documentManager->remove($result);
        }
        $results = $documentManager->getRepository(Entry::class)->findAll();
        foreach ($results as $result) {
            $documentManager->remove($result);
        }
        $documentManager->flush();
    }
}
