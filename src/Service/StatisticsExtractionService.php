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
    private $elasticsearchURL;
    private $extractionResultRepository;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param \App\Repository\ExtractionResultRepository $extractionResultRepository
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger
     * @param $boundElasticsearchURL
     *   Url of elasticsearch instance
     */
    public function __construct(DocumentManager $documentManager, ExtractionResultRepository $extractionResultRepository, LoggerInterface $logger, $boundElasticsearchURL)
    {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->elasticsearchURL = $boundElasticsearchURL;
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
        $lastExtraction = $this->extractionResultRepository->getLastEntry();

        // @TODO: Set correct date of first stats entry
        /* @var \DateTime $latestExtractionDate */
        $latestExtractionDate = $lastExtraction ? $lastExtraction->getDate() : new \Datetime('31 December 2019');

        // Extract logs for all dates from latest extraction date to yesterday.
        $yesterday = new \DateTime('-1 day');

        $numberOfDaysToSearch = $yesterday->diff($latestExtractionDate)->format("%a");

        // @TODO: Get all stats indexes that have not been searched through.
        $statistics = $this->getLogsFromElasticsearch(new \DateTime());

        // Add all to mongodb.
        foreach ($statistics as $statisticsEntry) {
            // @TODO: Handle entries where matches are not set.

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
        }

        // Save new extraction result.
        $extractionResult = new ExtractionResult();
        $extractionResult->setDate(new \DateTime());
        $this->documentManager->persist($extractionResult);

        // Flush to database.
        $this->documentManager->flush();
    }

    /**
     * Get the records from the given date from Elasticsearch.
     *
     * @param \DateTime $date
     *   Requested date
     *
     * @return array
     *   Array of logs for the given date
     *
     * @throws \Exception
     */
    private function getLogsFromElasticsearch(\DateTime $date)
    {
        $dateString = $date->format('d-m-Y');

        $indexName = 'stats_'.$dateString;
        $path = $indexName.'/_search';

        $jsonQuery = json_encode(
            (object) [
                'query' => (object) [
                    'match' => (object) [
                        'message' => 'Cover request/response',
                    ],
                ],
            ]
        );

        $curlHandle = curl_init($this->elasticsearchURL.$path);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $jsonQuery);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($jsonQuery),
        ]);
        $response = curl_exec($curlHandle);

        $error = null;
        if (false === $response) {
            $error = curl_error($curlHandle);
        }

        curl_close($curlHandle);

        if (null !== $error) {
            throw new \Exception($error);
        }

        $results = json_decode($response);

        return $results->hits->hits ?? [];
    }
}
