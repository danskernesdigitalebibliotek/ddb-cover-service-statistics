<?php

/**
 * @file
 * Contains interface for elasticsearch service.
 */

namespace App\Service;

/**
 * Interface ElasticsearchServiceInterface.
 */
interface SearchServiceInterface
{
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
     * Suppress phan false positive:
     * @phan-file-suppress PhanTypeInvalidThrowsIsInterface
     */
    public function getLogsFromSearch(\DateTime $date, string $message): array;

    /**
     * Reset the internal batch handling.
     */
    public function reset();
}
