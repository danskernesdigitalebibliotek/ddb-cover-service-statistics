<?php

/**
 * @file Contains service that extracts statistics that should be delivered to
 * Faktor.
 */

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Class StatisticsExtractorService.
 */
class StatisticsExtractionService
{
    private $logger;
    private $elasticsearchURL;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     *   The logger
     * @param $boundElasticsearchURL
     *   Url of elasticsearch instance
     */
    public function __construct(LoggerInterface $logger, $boundElasticsearchURL)
    {
        $this->logger = $logger;
        $this->elasticsearchURL = $boundElasticsearchURL;
    }

    /**
     * Get the records from the given date.
     *
     * @param \DateTime|null $date
     *   Requested date. Defaults to today.
     *
     * @return array
     *   Array of logs for the given date
     *
     * @throws \Exception
     */
    public function getStatistics(\DateTime $date = null)
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        $dateString = $date->format('d-m-Y');

        $indexName = 'stats_'.$dateString;
        $path = $indexName.'/_search';

        $jsonQuery = json_encode(
            (object) [
                "query" => (object) [
                    "match" => (object) [
                        "message" => "Cover request/response"
                    ]
                ],
            ]
        );

        $ch = curl_init($this->elasticsearchURL.$path);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($jsonQuery),
        ]);
        $response = curl_exec($ch);

        if (false === $response) {
            $error = curl_error($ch);
            throw new \Exception($error);
        } else {
            $results = json_decode($response);
        }
        curl_close($ch);

        return $results->hits->hits;
    }
}
