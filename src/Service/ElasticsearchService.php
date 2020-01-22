<?php

/**
 * @file
 * Contains service to integrate with elasticsearch.
 */

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class ElasticsearchService.
 */
class ElasticsearchService implements ElasticsearchServiceInterface
{
    private $elasticsearchURL;
    private $httpClient;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient
     *   The http client
     * @param $boundElasticsearchURL
     *   Url of elasticsearch instance
     */
    public function __construct(HttpClientInterface $httpClient, $boundElasticsearchURL)
    {
        $this->elasticsearchURL = $boundElasticsearchURL;
        $this->httpClient = $httpClient;
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
     * @throws \Throwable
     */
    public function getLogsFromElasticsearch(\DateTime $date, string $message) : array
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

        $indexExists = $this->httpClient->request('HEAD', $this->elasticsearchURL.$indexName)->getStatusCode();

        if (200 !== $indexExists) {
            return [];
        }

        $response = $this->httpClient->request('POST', $this->elasticsearchURL.$path, [
            'body' => $jsonQuery,
            'headers' => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($jsonQuery),
            ],
        ]);

        $result = json_decode($response->getContent());

        return $result->hits->hits ?? [];
    }
}
