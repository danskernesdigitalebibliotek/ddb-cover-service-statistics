<?php

/**
 * @file
 * Contains ElasticsearchFaker that creates test data in Elasticsearch.
 */

namespace App\Test;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class ElasticsearchFaker.
 */
class DataFakerService
{
    private $elasticsearchURL;
    private $httpClient;

    /**
     * ElasticsearchFaker constructor.
     *
     * @param \Symfony\Contracts\HttpClient\HttpClientInterface $httpClient
     *   The http client
     * @param $boundElasticsearchURL
     *   Url of Elasticsearch instance
     */
    public function __construct(HttpClientInterface $httpClient, $boundElasticsearchURL)
    {
        $this->elasticsearchURL = $boundElasticsearchURL;
        $this->httpClient = $httpClient;
    }

    /**
     * Create test data for current date.
     *
     * @param \DateTime|null $date
     *   Date to create index of fake data for. Defaults to today.
     *
     * @return bool
     *
     * @throws \Throwable
     */
    public function createElasticsearchTestData(\DateTime $date = null): bool
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        $dateString = ($date)->format('d-m-Y');

        $indexName = 'stats_'.$dateString;
        $path = $indexName;

        // Create index.
        $this->httpClient->request('PUT', $this->elasticsearchURL.$path);

        $queries = [
            // Version 1 of stats logging:
            '{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087","870970-basis:53969127","870970-basis:00000001"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg"]}',
            '{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg"]}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"pid":["870970-basis:29506914","870970-basis:29506906","882330-basis:17154889"],"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg"],"elasticQueryTime":0.038236141204833984}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg"],"elasticQueryTime":0.033380985260009766}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["900000000000000"]},"fileNames":null,"elasticQueryTime":0.025002002716064453}',
            // Version 2 of stats logging:
            '{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087","870970-basis:53969127","870970-basis:00000001"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","identifier":"870970-basis:26957087","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788702246841.jpg","identifier":"870970-basis:53969127","type":"pid"},{"match":null,"identifier":"870970-basis:00000001","type":"pid"}]}',
            '{"service":"CoverCollectionDataProvider","clientID":"REST_API","remoteIP":"127.0.0.1","isType":"pid","isIdentifiers":["870970-basis:26957087"],"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788763806176.jpg","identifier":"870970-basis:26957087","type":"pid"}]}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"pid":["870970-basis:29506914","870970-basis:29506906","882330-basis:17154889"],"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396728.jpg","identifier":"870970-basis:29506914","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788711396650.jpg","identifier":"870970-basis:29506906","type":"pid"},{"match":null,"identifier":"882330-basis:17154889","type":"pid"},{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","identifier":"9788740602456","type":"isbn"}],"elasticQueryTime":0.038236141204833984}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["9788740602456"]},"fileNames":["http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg"],"matches":[{"match":"http:\/\/cover-service-faktor-export.local.itkdev.dk\/9788740602456.jpg","identifier":"9788740602456","type":"isbn"}],"elasticQueryTime":0.033380985260009766}',
            '{"service":"MoreInfoService","clientID":"123456","remoteIP":"127.0.0.1","searchParameters":{"isbn":["900000000000000"]},"fileNames":null,"matches":[{"match":null,"identifier":"900000000000000","type":"isbn"}],"elasticQueryTime":0.025002002716064453}',
        ];

        foreach ($queries as $query) {
            $context = json_decode($query);
            $document = (object) [];
            $document->message = 'Cover request/response';
            $document->context = $context;
            $document->level = 200;
            $document->level_name = 'INFO';
            $document->channel = 'statistics';
            $document->datetime = $date->format(DATE_ISO8601);
            $jsonQuery = json_encode($document);

            $this->httpClient->request('POST', $this->elasticsearchURL.$path.'/logs/', [
                'body' => $jsonQuery,
                'headers' => [
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($jsonQuery),
                ],
            ]);
        }

        return true;
    }
}
