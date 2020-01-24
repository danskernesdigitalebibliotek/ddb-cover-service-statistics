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
    private const SEARCH_SIZE = 100;

    private $elasticsearchURL;
    private $httpClient;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient
     *   The http client
     * @param string $boundElasticsearchURL
     *   Url of elasticsearch instance
     */
    public function __construct(HttpClientInterface $httpClient, string $boundElasticsearchURL)
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
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getLogsFromElasticsearch(\DateTime $date, string $message): array
    {
        $dateString = $date->format('d-m-Y');

        $indexName = 'stats_'.$dateString;
        $path = $indexName.'/_search';

        $query = (object) [
            'query' => (object) [
                'match' => (object) [
                    'message' => $message,
                ],
            ],
        ];

        $indexExists = $this->httpClient->request('HEAD', $this->elasticsearchURL.$indexName)->getStatusCode();

        if (200 !== $indexExists) {
            return [];
        }

        $index = 0;
        $results = [];

        do {
            $jsonQuery = json_encode($query);

            $response = $this->httpClient->request('POST', $this->elasticsearchURL.$path, [
                'query' => [
                    'from' => $index,
                    'size' => self::SEARCH_SIZE,
                ],
                'body' => $jsonQuery,
                'headers' => [
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($jsonQuery),
                ],
            ]);

            $result = json_decode($response->getContent());
            $results = array_merge($results, $result->hits->hits ?? []);

            $index = $index + self::SEARCH_SIZE;
        } while ($result->hits->total > $index);

        return $results;
    }
}
