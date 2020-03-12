<?php

/**
 * @file
 * Contains service to integrate with ElasticSearch.
 */

namespace App\Service;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class ElasticSearchService.
 */
class ElasticSearchService implements SearchServiceInterface
{
    private const SEARCH_SIZE = 100;
    private const CACHE_ID = 'ScrollId_cache';
    private const SCROLL_ID_TTL = 60;

    private $elasticSearchURL;

    /** @var HttpClientInterface $httpClient */
    private $httpClient;

    /** @var FilesystemAdapter $cache */
    private $cache;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param HttpClientInterface $httpClient
     *   The http client
     * @param string $boundElasticSearchURL
     *   URL of ElasticSearch instance
     */
    public function __construct(HttpClientInterface $httpClient, string $boundElasticSearchURL)
    {
        $this->elasticSearchURL = $boundElasticSearchURL;
        $this->httpClient = $httpClient;

        $this->cache = new FilesystemAdapter();
    }

    /**
     * {@inheritdoc}
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getLogsFromSearch(\DateTime $date, string $message): array
    {
        $index = 'stats_'.$date->format('d-m-Y');
        if (!$this->indexExists($index)) {
            // The empty array is the same as no more results for this date. So an non existing index is handled in the
            // same way as no more data for that date.
            return [];
        }

        // Build and send request to elastic-search.
        $jsonQuery = $this->buildRequestBody($message);
        $response = $this->httpClient->request('POST', $this->elasticSearchURL.$this->buildSearchPath($index), [
            'body' => $jsonQuery,
            'headers' => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($jsonQuery),
            ],
        ]);
        $results = json_decode($response->getContent());

        // Save the current scroll id (it might change from request to request).
        $this->setScrollId($results->_scroll_id);

        return $results->hits->hits;
    }

    /**
     * {@inheritdoc}
     *
     * For this search provider and reset requires us to delete the scroll API id and clear local cache.
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws InvalidArgumentException
     * @throws TransportExceptionInterface
     */
    public function reset()
    {
        $scrollId = $this->getScrollId();
        if (false !== $scrollId) {
            $query = json_encode((object) [
                'scroll_id' => $scrollId,
            ]);
            try {
                $this->httpClient->request('DELETE', $this->elasticSearchURL.'_search/scroll', [
                    'body' => $query,
                    'headers' => [
                        'Content-Type: application/json',
                        'Content-Length: '.strlen($query),
                    ],
                ]);
            } catch (\Exception $e) {
                // Doing special cases the scroll id may not exists and the http client will throw an exception. This
                // can happen if the extractor is stopped during runs and the cache and ES is no longer in sync.
            }
        }
        $this->cache->deleteItem(self::CACHE_ID);
    }

    /**
     * Check if index exists at ES.
     *
     * @param string $index
     *   Name of the index
     *
     * @return bool
     *  True if it exists else false
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws TransportExceptionInterface
     */
    private function indexExists(string $index)
    {
        $indexExists = $this->httpClient->request('HEAD', $this->elasticSearchURL.$index)->getStatusCode();
        if (200 !== $indexExists) {
            return false;
        }

        return true;
    }

    /**
     * Build the search path.
     *
     * This changes base on the first request to the scroll API and the next calls.
     *
     * @param string $index
     *   The name of the index
     *
     * @return string
     *   The path required for the context given
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws InvalidArgumentException
     */
    private function buildSearchPath(string $index)
    {
        $path = '_search/scroll';

        $scrollId = $this->getScrollId();
        if (false === $scrollId) {
            $path = $index.'/_search?scroll='.$this->getScrollTTL();
        }

        return $path;
    }

    /**
     * Get scroll API time-to-live.
     *
     * @return string
     *   The time to live formatted for ES
     */
    private function getScrollTTL()
    {
        return (self::SCROLL_ID_TTL).'s';
    }

    /**
     * Build request body based on current state.
     *
     * @param string $message
     *   The message to search for in ES
     *
     * @return false|string
     *   JSON encoded string to use with ES
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws InvalidArgumentException
     */
    private function buildRequestBody(string $message)
    {
        $scrollId = $this->getScrollId();
        if (false === $scrollId) {
            $query = [
                'size' => self::SEARCH_SIZE,
                'query' => (object) [
                    'match' => (object) [
                        'message' => $message,
                    ],
                ],
                'sort' => [
                    '_doc',
                ],
            ];
        } else {
            $query = [
                'scroll' => $this->getScrollTTL(),
                'scroll_id' => $scrollId,
            ];
        }

        return json_encode((object) $query);
    }

    /**
     * Get/set scroll id.
     *
     * @return string|bool
     *   If found in cache it's returned else false
     *
     * @suppress PhanTypeInvalidThrowsIsInterface
     *
     * @throws InvalidArgumentException
     */
    private function getScrollId()
    {
        $item = $this->cache->getItem(self::CACHE_ID);
        if ($item->isHit()) {
            $scrollId = $item->get();
        } else {
            $scrollId = false;
        }

        return $scrollId;
    }

    /**
     * Set/store scroll id into cache.
     *
     * @param string $scrollId
     *   The scroll id from elasticsearch. If not given the cache value will be returned
     *
     * @throws InvalidArgumentException
     */
    private function setScrollId(string $scrollId)
    {
        $item = $this->cache->getItem(self::CACHE_ID);
        $item->set($scrollId);
        $item->expiresAfter(self::SCROLL_ID_TTL);
        $this->cache->save($item);
    }
}
