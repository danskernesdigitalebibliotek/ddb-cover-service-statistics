<?php

/**
 * @file
 * Contains interface for elasticsearch service.
 */

namespace App\Service;

/**
 * Interface ElasticsearchServiceInterface.
 */
interface ElasticsearchServiceInterface
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
     */
    public function getLogsFromElasticsearch(\DateTime $date, string $message): array;
}
