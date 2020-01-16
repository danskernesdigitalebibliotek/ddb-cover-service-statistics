<?php

/**
 * @file Contains service that extracts statistics that should be delivered to
 * Faktor.
 */

namespace App\Service;

use PHPUnit\Runner\Exception;
use Psr\Log\LoggerInterface;

/**
 * Class StatisticsExtractorService
 */
class StatisticsExtractionService
{
    private $logger;
    private $elasticsearchURL;

    /**
     * StatisticsExtractionService constructor.
     */
    public function __construct(LoggerInterface $logger, $boundElasticsearchURL)
    {
        $this->logger = $logger;
        $this->elasticsearchURL = $boundElasticsearchURL;
    }

    public function getLatestData() {
        $indexName = 'stats_16-01-2020';
        $path = $indexName.'/search/_search';

        $jsonQuery = json_encode([]);

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

        return $results;
    }
}
