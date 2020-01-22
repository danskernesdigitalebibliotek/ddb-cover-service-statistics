<?php

/**
 * @file Contains service that extracts statistics that should be delivered to
 * Faktor.
 */

namespace App\Service;

use App\Document\Entry;
use App\Document\ExtractionResult;
use App\Repository\ExtractionResultRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

/**
 * Class StatisticsExtractorService.
 */
class StatisticsExtractionService
{
    private $documentManager;
    private $logger;
    private $extractionResultRepository;
    private $elasticsearchService;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     *   The document manager
     * @param \App\Repository\ExtractionResultRepository $extractionResultRepository
     *   Repository for ExtractResult documents
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger
     * @param \App\Service\ElasticsearchService $elasticsearchService
     *   Service to integrate with elasticsearch
     */
    public function __construct(DocumentManager $documentManager, ExtractionResultRepository $extractionResultRepository, LoggerInterface $logger, ElasticsearchService $elasticsearchService)
    {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->elasticsearchService = $elasticsearchService;
        $this->extractionResultRepository = $extractionResultRepository;
    }

    /**
     * Extract new statistics.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     * @throws \Throwable
     */
    public function extractStatistics()
    {
        $today = new \DateTime();

        // Get latest extraction entry. Default to first day of production.
        /* @var ExtractionResult $lastExtraction */
        $lastExtraction = $this->extractionResultRepository->getNewestEntry();

        // Default to 1. december 2019 to make sure we extract all statistics from the start of production.
        /* @var \DateTime $latestExtractionDate */
        $latestExtractionDate = $lastExtraction ? $lastExtraction->getDate() : new \Datetime('1 december 2019');

        $numberOfDaysToSearch = (int) $today->diff($latestExtractionDate)->format('%a');

        $numberOfEntriesAdded = 0;

        // Extract logs for all dates from latest extraction date to yesterday.
        // Create all as Entries in the database.
        while ($numberOfDaysToSearch > 0) {
            $dayToSearch = new \DateTime('-'.($numberOfDaysToSearch - 1).' days');

            // Get entries from elasticsearch index.
            $statistics = $this->elasticsearchService->getLogsFromElasticsearch($dayToSearch, 'Cover request/response');

            // Add all to mongodb.
            foreach ($statistics as $statisticsEntry) {
                if (isset($statisticsEntry->_source->context->matches)) {
                    // Version 2 of statistics logging, where matches is set.
                    foreach ($statisticsEntry->_source->context->matches as $matchEntry) {
                        $response = [];

                        if (null === $matchEntry->match) {
                            $response['message'] = 'image not found';
                        } else {
                            $response['message'] = 'ok';
                        }

                        $entry = $this->createEntry(
                            new \DateTime($statisticsEntry->_source->datetime),
                            $statisticsEntry->_id,
                            $statisticsEntry->_source->context->clientID,
                            'request_image',
                            $matchEntry->identifier,
                            json_encode($response),
                            $matchEntry->match
                        );
                        $this->documentManager->persist($entry);
                        ++$numberOfEntriesAdded;
                    }
                } else {
                    // Version 1 of statistics logging, where matches is not set.
                    $fileNames = $statisticsEntry->_source->context->fileNames ?? [];
                    $searchIdentifiers = [];

                    // Extract identifiers from search parameters for SOAP requests
                    if (isset($statisticsEntry->_source->context->searchParameters)) {
                        foreach ($statisticsEntry->_source->context->searchParameters as $identifiers) {
                            $searchIdentifiers = array_merge($searchIdentifiers, $identifiers);
                        }
                    } elseif (isset($statisticsEntry->_source->context->isIdentifiers)) {
                        // Extract identifiers from isIdentifiers for REST_API requests
                        $searchIdentifiers = $statisticsEntry->_source->context->isIdentifiers;
                    }

                    // If only searching for one identifier and only finding on file
                    // create success entry.
                    if (1 === count($searchIdentifiers) && 1 === count($fileNames)) {
                        $entry = $this->createEntry(
                            new \DateTime($statisticsEntry->_source->datetime),
                            $statisticsEntry->_id,
                            $statisticsEntry->_source->context->clientID,
                            'request_image',
                            array_pop($searchIdentifiers),
                            json_encode(['message' => 'ok']),
                            array_pop($fileNames)
                        );
                        $this->documentManager->persist($entry);
                        ++$numberOfEntriesAdded;

                        continue;
                    }

                    // If fileNames is empty report failure for each identifier.
                    if (0 === count($fileNames)) {
                        foreach ($searchIdentifiers as $identifier) {
                            $entry = $this->createEntry(
                                new \DateTime($statisticsEntry->_source->datetime),
                                $statisticsEntry->_id,
                                $statisticsEntry->_source->context->clientID,
                                'request_image',
                                $identifier,
                                json_encode(['message' => 'image not found']),
                                null
                            );
                            $this->documentManager->persist($entry);
                            ++$numberOfEntriesAdded;
                        }

                        continue;
                    }

                    // Otherwise, report results as undetermined.
                    foreach ($searchIdentifiers as $identifier) {
                        $entry = $this->createEntry(
                            new \DateTime($statisticsEntry->_source->datetime),
                            $statisticsEntry->_id,
                            $statisticsEntry->_source->context->clientID,
                            'request_image',
                            $identifier,
                            json_encode(['message' => 'image maybe found']),
                            'undetermined'
                        );
                        $this->documentManager->persist($entry);
                        ++$numberOfEntriesAdded;
                    }
                }
            }

            --$numberOfDaysToSearch;
        }

        // Save new extraction result.
        $extractionResult = new ExtractionResult();
        $extractionResult->setDate(new \DateTime());
        $extractionResult->setNumberOfEntriesAdded($numberOfEntriesAdded);
        $this->documentManager->persist($extractionResult);

        // Flush to database.
        $this->documentManager->flush();
    }

    /**
     * Remove already extracted entries.
     *
     * @param \DateTime $compareDate
     *   Date to compare with. Entries extracted before $compareDate are removed
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function removeExtractedEntries(\DateTime $compareDate)
    {
        $entries = $this->documentManager->getRepository(Entry::class)->findBy(['extracted' => true]);

        /* @var Entry $entry */
        foreach ($entries as $entry) {
            if (null !== $entry->getExtracted() && null !== $entry->getExtractionDate()) {
                $diff = (int) $entry->getExtractionDate()->diff($compareDate)->format('%a');

                if (0 < $diff && $entry->getExtractionDate() < $compareDate) {
                    $this->documentManager->remove($entry);
                }
            }
        }

        $this->documentManager->flush();
    }

    /**
     * Create an entry.
     *
     * @param \DateTime $date
     *   The date of the registration in elasticsearch
     * @param string $elasticId
     *   The id of the entry in elasticsearch
     * @param string $agency
     *   The agency connected with the event
     * @param string $event
     *   The event
     * @param string $materialId
     *   The material id of the event
     * @param string $response
     *   The response
     * @param string|null $imageId
     *   The image id
     *
     * @return \App\Document\Entry
     */
    private function createEntry(\DateTime $date, string $elasticId, string $agency, string $event, string $materialId, string $response, ?string $imageId): Entry
    {
        $entry = new Entry();

        $entry->setDate($date);
        $entry->setElasticId($elasticId);
        $entry->setClientId('CoverService');
        $entry->setAgency($agency);
        $entry->setEvent($event);
        $entry->setMaterialId($materialId);
        $entry->setResponse($response);
        $entry->setImageId($imageId);
        $entry->setExtracted(false);

        return $entry;
    }
}
