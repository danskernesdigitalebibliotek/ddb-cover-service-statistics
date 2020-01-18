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
     * @throws \Exception
     */
    public function extractStatistics()
    {
        // Get latest extraction entry. Default to first day of production.
        /* @var ExtractionResult $lastExtraction */
        $lastExtraction = $this->extractionResultRepository->getNewestEntry();

        // @TODO: Set correct date of first stats entry
        /* @var \DateTime $latestExtractionDate */
        $latestExtractionDate = $lastExtraction ? $lastExtraction->getDate() : new \Datetime('17 january 2020');

        $today = new \DateTime();
        $numberOfDaysToSearch = (int) $today->diff($latestExtractionDate)->format('%a');

        // Extract logs for all dates from latest extraction date to yesterday.
        while ($numberOfDaysToSearch > 0) {
            $dayToSearch = new \DateTime('-'.($numberOfDaysToSearch - 1).' days');

            $statistics = $this->elasticsearchService->getLogsFromElasticsearch($dayToSearch, 'Cover request/response');

            // Add all to mongodb.
            foreach ($statistics as $statisticsEntry) {
                // Version 2 of statistics logging, where matches is set.
                if (isset($statisticsEntry->_source->context->matches)) {
                    foreach ($statisticsEntry->_source->context->matches as $matchEntry) {
                        $response = [];

                        if (null === $matchEntry->match) {
                            $response['message'] = 'image not found';
                        } else {
                            $response['message'] = 'ok';
                        }

                        // Create entry.
                        $entry = new Entry();
                        $entry->setDate(new \DateTime($statisticsEntry->_source->datetime));
                        $entry->setAgency($statisticsEntry->_source->context->clientID);
                        $entry->setClientId('CoverService');
                        $entry->setImageId($matchEntry->match);
                        $entry->setMaterialId($matchEntry->identifier);
                        $entry->setEvent('request_image');
                        $entry->setResponse(json_encode($response));

                        $this->documentManager->persist($entry);
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
                    }
                    // Extract identifiers from isIdentifiers for REST_API requests
                    elseif (isset($statisticsEntry->_source->context->isIdentifiers)) {
                        $searchIdentifiers = $statisticsEntry->_source->context->isIdentifiers;
                    }

                    // If only searching for one identifier and only finding on file
                    // create success entry.
                    if (1 === count($searchIdentifiers) && 1 === count($fileNames)) {
                        $entry = new Entry();
                        $entry->setDate(new \DateTime($statisticsEntry->_source->datetime));
                        $entry->setAgency($statisticsEntry->_source->context->clientID);
                        $entry->setClientId('CoverService');
                        $entry->setImageId($fileNames[0]);
                        $entry->setMaterialId($searchIdentifiers[0]);
                        $entry->setEvent('request_image');
                        $entry->setResponse(json_encode(['message' => 'ok']));

                        $this->documentManager->persist($entry);

                        continue;
                    }

                    // If fileNames is empty report failure for each identifier.
                    if (0 === count($fileNames)) {
                        foreach ($searchIdentifiers as $identifier) {
                            $entry = new Entry();
                            $entry->setDate(new \DateTime($statisticsEntry->_source->datetime));
                            $entry->setAgency($statisticsEntry->_source->context->clientID);
                            $entry->setClientId('CoverService');
                            $entry->setImageId(null);
                            $entry->setMaterialId($identifier);
                            $entry->setEvent('request_image');
                            $entry->setResponse(json_encode(['image not found']));

                            $this->documentManager->persist($entry);
                        }

                        continue;
                    }

                    // Otherwise, report results as undetermined.
                    foreach ($searchIdentifiers as $identifier) {
                        $entry = new Entry();
                        $entry->setDate(new \DateTime($statisticsEntry->_source->datetime));
                        $entry->setAgency($statisticsEntry->_source->context->clientID);
                        $entry->setClientId('CoverService');
                        $entry->setImageId('undetermined');
                        $entry->setMaterialId($identifier);
                        $entry->setEvent('request_image');
                        $entry->setResponse(json_encode(['image maybe found']));

                        $this->documentManager->persist($entry);
                    }
                }
            }

            --$numberOfDaysToSearch;
        }

        // Save new extraction result.
        $extractionResult = new ExtractionResult();
        $extractionResult->setDate(new \DateTime());
        $this->documentManager->persist($extractionResult);

        // Flush to database.
        $this->documentManager->flush();
    }
}
