<?php

namespace App\Tests;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Command\CleanupEntriesCommand;
use App\Command\ExtractStatisticsCommand;
use App\Command\LoadFixturesCommand;
use App\Document\Entry;
use App\Document\ExtractionResult;
use App\EventSubscriber\ResponseSubscriber;
use App\Fixtures\FixturesService;
use App\Repository\EntryRepository;
use App\Repository\ExtractionResultRepository;
use App\Service\ElasticSearchService;
use App\Service\SearchServiceInterface;
use App\Service\StatisticsExtractionService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetCollectionExists(): void
    {
        $client = static::createClient();

        $headers = $this->login();

        $response = $client->request('GET', '/api/entries', [
            'headers' => array_merge($headers, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ]);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
    }

    /**
     * Test that entries can be extracted from an elasticsearch result.
     *
     * @throws \Throwable
     */
    public function testExtractStatistics(): void
    {
        // Get special container that allows fetching private services
        $container = self::$container;

        $headers = $this->login();

        // Get services.
        $entryRepository = $container->get(EntryRepository::class);
        $extractionResultRepository = $container->get(ExtractionResultRepository::class);
        $logger = $container->get(LoggerInterface::class);
        $documentManager = $container->get(DocumentManager::class);

        // Create an extraction result from yesterday, to only extract one day.
        $extractionResult = new ExtractionResult();
        $extractionResult->setNumberOfEntriesAdded(0);
        $extractionResult->setDate(new \DateTime('-1 day'));
        $documentManager->persist($extractionResult);
        $documentManager->flush();

        // Create mock
        $elasticSearchServiceMock = $this->createMock(SearchServiceInterface::class);
        $mockResponse = json_decode('[{"_index":"stats_18-01-2020","_type":"logs","_id":"jp_Vt28BPlVUX1bQ2KG1","_score":0.8630463,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","identifier":"9788740602456","type":"isbn"}],"elasticQueryTime":0.033380985260009766},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"jJ_Vt28BPlVUX1bQ2KF8","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","identifier":"870970-basis:26957087","type":"pid"}]},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"j5_Vt28BPlVUX1bQ2KHW","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["900000000000000"]},"fileNames":null,"matches":[{"match":null,"identifier":"900000000000000","type":"isbn"}],"elasticQueryTime":0.025002002716064453},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"iZ_Vt28BPlVUX1bQ2KEY","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg"],"elasticQueryTime":0.033380985260009766},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"i5_Vt28BPlVUX1bQ2KFE","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087","870970-basis:53969127","870970-basis:00000001"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","identifier":"870970-basis:26957087","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg","identifier":"870970-basis:53969127","type":"pid"},{"match":null,"identifier":"870970-basis:00000001","type":"pid"}]},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"iJ_Vt28BPlVUX1bQ16He","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"pid":["870970-basis:29506914","870970-basis:29506906","882330-basis:17154889"],"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg"],"elasticQueryTime":0.038236141204833984},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"jZ_Vt28BPlVUX1bQ2KGg","_score":0.5469647,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"pid":["870970-basis:29506914","870970-basis:29506906","882330-basis:17154889"],"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","identifier":"870970-basis:29506914","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg","identifier":"870970-basis:29506906","type":"pid"},{"match":null,"identifier":"882330-basis:17154889","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","identifier":"9788740602456","type":"isbn"}],"elasticQueryTime":0.038236141204833984},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"hp_Vt28BPlVUX1bQ16F9","_score":0.40059417,"_source":{"message":"Cover request\/response","context":{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087","870970-basis:53969127","870970-basis:00000001"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg"]},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"h5_Vt28BPlVUX1bQ16HQ","_score":0.40059417,"_source":{"message":"Cover request\/response","context":{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg"]},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}},{"_index":"stats_18-01-2020","_type":"logs","_id":"ip_Vt28BPlVUX1bQ2KEm","_score":0.40059417,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["900000000000000"]},"fileNames":null,"elasticQueryTime":0.025002002716064453},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}}]');
        $elasticSearchServiceMock
            ->expects($this->at(0))
            ->method('getLogsFromSearch')
            ->will($this->returnValue($mockResponse))
        ;
        $elasticSearchServiceMock
            ->expects($this->at(1))
            ->method('getLogsFromSearch')
            ->will($this->returnValue([]))
        ;

        $extractionService = new StatisticsExtractionService($documentManager, $entryRepository, $extractionResultRepository, $logger, $elasticSearchServiceMock);

        $extractionService->extractLatestStatistics();

        $entries = $documentManager->getRepository(Entry::class)->findAll();

        // Assert that 20 Entry documents exist in the database.
        $this->assertEquals(20, count($entries), 'Number of entries in database should be 20');

        // Assert that the entries can be extracted from the API.
        $client = static::createClient();
        $response = $client->request('GET', '/api/entries', [
            'headers' => array_merge($headers, [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ]);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json; charset=utf-8');
        $content = $response->toArray();
        $this->assertEquals(20, count($content), 'Number of entries in response should be 20');

        // Since an extraction was added before the extraction was run
        // we expect 2 ExtractionResults to exist in the database.
        $extractionResults = $documentManager->getRepository(ExtractionResult::class)->findAll();
        $this->assertEquals(2, count($extractionResults), 'Number of extraction results in database should be 2');
    }

    /**
     * Test Elasticsearch service.
     *
     * @throws \Throwable
     */
    public function testElasticsearchService()
    {
        $expectedResult = [
            (object) [
                'id' => 'firstHit',
            ],
        ];

        $responses = [
            new MockResponse(),
            new MockResponse(
                json_encode(
                    (object) [
                        'hits' => (object) [
                            'total' => 1,
                            'hits' => $expectedResult,
                        ],
                        '_scroll_id' => 0,
                    ]
                ),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]
            ),
        ];

        $clientMock = new MockHttpClient($responses);

        $elasticsearchService = new ElasticSearchService($clientMock, 'http://fakesearch:9200/');
        $result = $elasticsearchService->getLogsFromSearch(new \DateTime('-1 day'), 'test');

        $this->assertEquals($expectedResult, $result, 'Result from elasticsearch does not match expected result');
    }

    /**
     * Test FixturesService.
     *
     * @throws \Throwable
     */
    public function testFixturesService()
    {
        $responses = [
            new MockResponse('', ['http_code' => 200]),
        ];

        for ($i = 0; $i < 1000; ++$i) {
            $responses[] = new MockResponse('', ['http_code' => 200]);
        }

        $clientMock = new MockHttpClient($responses);

        $fixturesService = new FixturesService($clientMock, 'http://fakesearch:9200/');
        $result = $fixturesService->runFixture(new \DateTime());
        $this->assertTrue($result, 'FixturesService should finish executing');
    }

    /**
     * @throws \Exception
     */
    public function testCleanupEntries()
    {
        $container = self::$container;

        $documentManager = $container->get(DocumentManager::class);
        $entryRepository = $container->get(EntryRepository::class);
        $extractionResultRepository = $container->get(ExtractionResultRepository::class);
        $logger = $container->get(LoggerInterface::class);

        $elasticSearchServiceMock = $this->createMock(SearchServiceInterface::class);
        $extractionService = new StatisticsExtractionService($documentManager, $entryRepository, $extractionResultRepository, $logger, $elasticSearchServiceMock);

        // Create test data
        for ($i = 0; $i < 3; ++$i) {
            $entry = new Entry();
            $entry->setExtracted(true);
            $entry->setExtractionDate(new \DateTime('-1 day'));
            $documentManager->persist($entry);
        }

        $documentManager->flush();

        // Run clean up with a past date compare point to confirm that the
        // entries are not deleted.
        $extractionService->removeExtractedEntries(new \DateTime('-4 days'));
        $entries = $documentManager->getRepository(Entry::class)->findAll();
        $this->assertEquals(3, count($entries), 'Number of extracted entries should equal 3');

        // Run clean up with a futre date compare point to confirm that the
        // entries are deleted.
        $extractionService->removeExtractedEntries(new \DateTime('+1 day'));
        $entries = $documentManager->getRepository(Entry::class)->findAll();
        $this->assertEquals(0, count($entries), 'Number of extracted entries should equal 0');
    }

    /**
     * Test the commands.
     *
     * @throws \Exception
     */
    public function testCommands()
    {
        $container = self::$container;

        $documentManager = $container->get(DocumentManager::class);
        $extractionResultRepository = $container->get(ExtractionResultRepository::class);
        $entryRepository = $container->get(EntryRepository::class);
        $logger = $container->get(LoggerInterface::class);

        $elasticSearchServiceMock = $this->createMock(SearchServiceInterface::class);
        $extractionService = new StatisticsExtractionService($documentManager, $entryRepository, $extractionResultRepository, $logger, $elasticSearchServiceMock);

        // Test CleanupEntriesCommand.
        $command = new CleanupEntriesCommand($extractionService);
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $result = $command->run($input, $output);
        $this->assertEquals(0, $result, 'CleanupEntriesCommand should return 0');

        // Test ExtractStatisticsCommand.
        $command = new ExtractStatisticsCommand($extractionService);
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $command->run($input, $output);
        $result = $command->run($input, $output);
        $this->assertEquals(0, $result, 'CleanupEntriesCommand should return 0');

        $responses = [];
        for ($i = 0; $i < 1001; ++$i) {
            $responses[] = new MockResponse('', ['http_code' => 200]);
        }
        $clientMock = new MockHttpClient($responses);

        // Test LoadFixturesCommand
        $command = new LoadFixturesCommand($this->createKernel(), new FixturesService($clientMock, 'http://fakesearch:9200/'));
        $this->assertEquals('Load fixtures into elasticsearch', $command->getDescription(), 'Description should have been set.');
        $input = new ArrayInput([
            'date' => 'today',
        ]);
        $output = new NullOutput();
        $result = $command->run($input, $output);
        $this->assertEquals(0, $result, 'LoadFixturesCommand should return 0');
    }

    /**
     * Test that duplicates cannot exist for Entry documents.
     */
    public function testDuplicateEntryCannotExist()
    {
        // Get special container that allows fetching private services
        $container = self::$container;

        // Get services.
        $entryRepository = $container->get(EntryRepository::class);
        $extractionResultRepository = $container->get(ExtractionResultRepository::class);
        $logger = $container->get(LoggerInterface::class);
        /* @var DocumentManager $documentManager */
        $documentManager = $container->get(DocumentManager::class);

        // Create mock
        $elasticSearchServiceMock = $this->createMock(SearchServiceInterface::class);
        // Create one entry.
        $mockResponse = json_decode('[{"_index":"stats_18-01-2020","_type":"logs","_id":"jp_Vt28BPlVUX1bQ2KG1","_score":0.8630463,"_source":{"message":"Cover request\/response","context":{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","identifier":"9788740602456","type":"isbn"}],"elasticQueryTime":0.033380985260009766},"level":200,"level_name":"INFO","channel":"statistics","datetime":"2020-01-18T08:47:21+0000"}}]');
        $elasticSearchServiceMock
            ->expects($this->at(0))
            ->method('getLogsFromSearch')
            ->will($this->returnValue($mockResponse))
        ;
        $elasticSearchServiceMock
            ->expects($this->at(1))
            ->method('getLogsFromSearch')
            ->will($this->returnValue([]))
        ;

        $extractionService = new StatisticsExtractionService($documentManager, $entryRepository, $extractionResultRepository, $logger, $elasticSearchServiceMock);

        $extractionService->extractLatestStatistics();

        $entries = $documentManager->getRepository(Entry::class)->findAll();
        self::assertEquals(1, count($entries), 'Only one entry should exist');

        // Remove the ExtractionResult entries and reimport.
        $extractionResults = $documentManager->getRepository(ExtractionResult::class)->findAll();
        $extractionResultCount = count($extractionResults);
        /* @var ExtractionResult $result */
        foreach ($extractionResults as $result) {
            $documentManager->remove($result);
        }
        $documentManager->flush();

        $extractionService->extractLatestStatistics();

        $entries = $documentManager->getRepository(Entry::class)->findAll();
        $extractionResults = $documentManager->getRepository(ExtractionResult::class)->findAll();

        self::assertEquals($extractionResultCount, count($extractionResults), 'The same number of extraction results should exist as before');

        self::assertEquals(1, count($entries), 'Only one entry should still exist');
    }

    /**
     * Test miscellaneous.
     */
    public function testMisc()
    {
        $this->assertEquals([
            ResponseEvent::class => 'onResponseEvent',
        ], ResponseSubscriber::getSubscribedEvents(), 'ResponseSubscriber should subscribe to onResponseEvent');
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();

        // Clean database
        $this->cleanMongoDatabase();
    }

    /**
     * Fake login.
     *
     * @return array
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function login(): array
    {
        // Get special container that allows fetching private services
        $container = self::$container;

        $cache = $container->get(AdapterInterface::class);

        // Get services.
        $authFaker = new AuthFaker($cache);

        return $authFaker->login();
    }

    /**
     * Remove all content from mongo database.
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
