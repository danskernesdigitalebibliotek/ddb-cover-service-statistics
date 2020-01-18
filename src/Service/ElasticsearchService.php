<?php

/**
 * @file
 * Contains service to integrate with elasticsearch.
 */

namespace App\Service;

/**
 * Class ElasticsearchService
 */
class ElasticsearchService
{
    private $elasticsearchURL;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param $boundElasticsearchURL
     *   Url of elasticsearch instance
     */
    public function __construct($boundElasticsearchURL)
    {
        $this->elasticsearchURL = $boundElasticsearchURL;
    }

    /**
     * Get the records from the given date from Elasticsearch.
     *
     * @param \DateTime $date
     *   Requested date
     * @param string $message
     *   The message to search for
     *
     * @return array
     *   Array of logs for the given date
     *
     * @throws \Exception
     */
    public function getLogsFromElasticsearch(\DateTime $date, string $message)
    {
        $dateString = $date->format('d-m-Y');

        $indexName = 'stats_'.$dateString;
        $path = $indexName.'/_search';

        $jsonQuery = json_encode(
            (object) [
                'query' => (object) [
                    'match' => (object) [
                        'message' => $message,
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
