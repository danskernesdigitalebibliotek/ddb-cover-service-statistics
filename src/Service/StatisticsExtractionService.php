<?php

/**
 * @file Contains service that extracts statistics that should be delivered to
 * Faktor.
 */

namespace App\Service;

use App\Document\Entry;
use App\Document\ExtractionResult;
use App\Export\ExtractionTargetInterface;
use App\Export\MongoDBTarget;
use App\Repository\EntryRepository;
use App\Repository\ExtractionResultRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Psr\Log\LoggerInterface;

/**
 * Class StatisticsExtractorService.
 */
class StatisticsExtractionService
{
    use ProgressBarTrait;

    protected const BATCH_SIZE = 50;

    private $documentManager;
    private $logger;
    private $extractionResultRepository;
    private $elasticSearchService;
    private $entryRepository;

    /**
     * StatisticsExtractionService constructor.
     *
     * @param DocumentManager $documentManager
     *   The document manager
     * @param EntryRepository $entryRepository
     *   Repository for Entry documents
     * @param ExtractionResultRepository $extractionResultRepository
     *   Repository for ExtractResult documents
     * @param LoggerInterface $logger
     *   The logger
     * @param SearchServiceInterface $elasticsearchService
     *   Service to integrate with elasticsearch
     */
    public function __construct(DocumentManager $documentManager, EntryRepository $entryRepository, ExtractionResultRepository $extractionResultRepository, LoggerInterface $logger, SearchServiceInterface $elasticsearchService)
    {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->elasticSearchService = $elasticsearchService;
        $this->extractionResultRepository = $extractionResultRepository;
        $this->entryRepository = $entryRepository;
    }

    /**
     * Extract new statistics.
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function extractLatestStatistics()
    {
        $today = new \DateTime();

        $this->progressStart('Starting extraction process');

        $target = new MongoDBTarget($this->documentManager, $this->entryRepository, $this->extractionResultRepository);

        $target->initialize();

        $lastExtraction = null;

        // Get latest extraction entry.
        /* @var ExtractionResult $lastExtraction */
        $lastExtraction = $this->extractionResultRepository->getNewestEntry();

        // Default to 1. december 2019 to make sure we extract all statistics
        // from the start of production of CoverService.
        /* @var \DateTime $latestExtractionDate */
        $latestExtractionDate = $lastExtraction ? $lastExtraction->getDate() : new \Datetime('1 december 2019');

        $numberOfDaysToSearch = (int) $today->diff($latestExtractionDate)->format('%a');

        $entriesAdded = 0;

        // Extract logs for all dates from latest extraction date to yesterday.
        // Create all as Entry documents in the database.
        while ($numberOfDaysToSearch > 0) {
            $dayToSearch = new \DateTime('-'.($numberOfDaysToSearch - 1).' days');

            $entriesAddedFromDay = 0;
            $nextBatchLimit = self::BATCH_SIZE;

            $this->progressMessage('Search stats for date '.$dayToSearch->format('d-m-Y'));

            $this->extractDay($dayToSearch, $target, $entriesAdded, $entriesAddedFromDay, $nextBatchLimit);

            // Reset/remove the internal batch state for the current date.
            $this->elasticSearchService->reset();

            // Save new extraction result.
            $extractionResult = new ExtractionResult();
            $extractionResult->setDate($dayToSearch);
            $extractionResult->setNumberOfEntriesAdded($entriesAddedFromDay);
            $target->recordExtractionResult($extractionResult);

            $target->flush();

            --$numberOfDaysToSearch;
        }

        $target->finish();

        $this->progressFinish();
    }

    /**
     * Remove already extracted entries.
     *
     * @param \DateTime $compareDate
     *   Date to compare with. Entries extracted before $compareDate are removed
     *
     * @throws MongoDBException
     */
    public function removeExtractedEntries(\DateTime $compareDate)
    {
        $entries = $this->entryRepository->findBy(['extracted' => true]);

        $entriesRemoved = 0;

        /* @var Entry $entry */
        foreach ($entries as $entry) {
            if (null !== $entry->getExtracted() && null !== $entry->getExtractionDate()) {
                $diff = (int) $entry->getExtractionDate()->diff($compareDate)->format('%a');

                if (0 < $diff && $entry->getExtractionDate() < $compareDate) {
                    $this->documentManager->remove($entry);

                    ++$entriesRemoved;

                    // Flush when batch size is exceeded to avoid memory buildup.
                    if (0 === $entriesRemoved % self::BATCH_SIZE) {
                        $this->documentManager->flush();
                    }
                }
            }
        }

        $this->documentManager->flush();
    }

    /**
     * Extract statistics for one day.
     *
     * @param array $days
     *   Array of \DateTime. Days to extract
     * @param \App\Export\ExtractionTargetInterface $target
     *   The target to add entries to
     *
     * @throws \Exception
     */
    public function extractStatisticsForDays(array $days, ExtractionTargetInterface $target)
    {
        $this->progressStart('Starting extraction process');

        $target->initialize();

        $entriesAdded = 0;

        /* @var \DateTime $day */
        foreach ($days as $day) {
            $entriesAddedFromDay = 0;
            $nextBatchLimit = self::BATCH_SIZE;

            $this->progressMessage('Search stats for date '.$day->format('d-m-Y'));

            $this->extractDay($day, $target, $entriesAdded, $entriesAddedFromDay, $nextBatchLimit);

            // Reset/remove the internal batch state for the current date.
            $this->elasticSearchService->reset();

            // Save new extraction result.
            $extractionResult = new ExtractionResult();
            $extractionResult->setDate($day);
            $extractionResult->setNumberOfEntriesAdded($entriesAddedFromDay);
            $target->recordExtractionResult($extractionResult);

            $target->flush();
        }

        $target->flush();

        $target->finish();

        $this->progressFinish();
    }

    /**
     * Extract statistics for one day.
     *
     * @param \DateTime $dayToSearch
     * @param ExtractionTargetInterface $target
     * @param int $entriesAdded
     * @param int $entriesAddedFromDay
     * @param int $nextBatchLimit
     *
     * @throws \Exception
     */
    private function extractDay(\DateTime $dayToSearch, ExtractionTargetInterface $target, int &$entriesAdded, int &$entriesAddedFromDay, int &$nextBatchLimit)
    {
        do {
            // Get statistics batch (tracking of current batch is handled inside the search provider). An empty
            // array will be returned when no more hits are found or if there are no results for the given date.
            $statistics = $this->elasticSearchService->getLogsFromSearch($dayToSearch, 'Cover request/response');

            foreach ($statistics as $statisticsEntry) {
                // Validate that the entry is valid for the target given.
                if (!$target->validEntry($statisticsEntry)) {
                    $this->progressAdvance();
                    continue;
                }

                // Flush when batch size is exceeded to avoid memory buildup.
                if ($entriesAdded > $nextBatchLimit) {
                    $nextBatchLimit = $nextBatchLimit + self::BATCH_SIZE;
                    $target->flush();
                }

                $elasticId = $statisticsEntry->_id;
                $agency = $statisticsEntry->_source->context->clientID;

                // If entry has already been imported, continue.
                if ($target->entryExists($elasticId)) {
                    continue;
                }

                if (isset($statisticsEntry->_source->context->matches)) {
                    // Version 2 of statistics logging, where matches is set.
                    foreach ($statisticsEntry->_source->context->matches as $matchEntry) {
                        $response = [];

                        if (null === $matchEntry->match) {
                            $response['message'] = 'image not found';
                        } else {
                            $response['message'] = 'ok';
                        }

                        if ($target->acceptsType(null === $matchEntry->match ? 'nohit' : 'hit')) {
                            $entry = $this->createEntry(
                                new \DateTime($statisticsEntry->_source->datetime),
                                $elasticId,
                                $agency,
                                'request_image',
                                $matchEntry->type,
                                $matchEntry->identifier,
                                json_encode($response),
                                $matchEntry->match
                            );
                            $target->addEntry($entry);
                            ++$entriesAdded;
                            ++$entriesAddedFromDay;
                        }
                    }
                } else {
                    // Version 1 of statistics logging, where matches is not set.
                    $fileNames = $statisticsEntry->_source->context->fileNames ?? [];
                    $searchIdentifiers = [];
                    $identifierTypes = [];

                    // Extract identifiers from search parameters for SOAP requests
                    if (isset($statisticsEntry->_source->context->searchParameters)) {
                        foreach ($statisticsEntry->_source->context->searchParameters as $type => $identifiers) {
                            $searchIdentifiers = array_merge($searchIdentifiers, $identifiers);

                            foreach ($identifiers as $identifier) {
                                $identifierTypes[$identifier] = $type;
                            }
                        }
                    } elseif (isset($statisticsEntry->_source->context->isIdentifiers)) {
                        // Extract identifiers from isIdentifiers for REST_API requests
                        $searchIdentifiers = $statisticsEntry->_source->context->isIdentifiers;

                        foreach ($statisticsEntry->_source->context->isIdentifiers as $identifier) {
                            $identifierTypes[$identifier] = $statisticsEntry->_source->context->isType;
                        }
                    }

                    // If only searching for one identifier and only finding on file
                    // create success entry.
                    if (1 === count($searchIdentifiers) && 1 === count($fileNames)) {
                        $identifier = array_pop($searchIdentifiers);

                        if ($target->acceptsType('hit')) {
                            $entry = $this->createEntry(
                                new \DateTime($statisticsEntry->_source->datetime),
                                $elasticId,
                                $agency,
                                'request_image',
                                $identifierTypes[$identifier],
                                $identifier,
                                json_encode(['message' => 'ok']),
                                array_pop($fileNames)
                            );
                            $target->addEntry($entry);
                            ++$entriesAdded;
                            ++$entriesAddedFromDay;
                        }

                        continue;
                    }

                    // If fileNames is empty report failure for each identifier.
                    if (0 === count($fileNames)) {
                        foreach ($searchIdentifiers as $identifier) {
                            if ($target->acceptsType('nohit')) {
                                $entry = $this->createEntry(
                                    new \DateTime($statisticsEntry->_source->datetime),
                                    $elasticId,
                                    $agency,
                                    'request_image',
                                    $identifierTypes[$identifier],
                                    $identifier,
                                    json_encode(['message' => 'image not found']),
                                    null
                                );
                                $target->addEntry($entry);
                                ++$entriesAdded;
                                ++$entriesAddedFromDay;
                            }
                        }

                        continue;
                    }

                    // Otherwise, we do not know which files match the hits. Therefore, report results as undetermined.
                    foreach ($searchIdentifiers as $identifier) {
                        if ($target->acceptsType('undetermined')) {
                            $entry = $this->createEntry(
                                new \DateTime($statisticsEntry->_source->datetime),
                                $elasticId,
                                $agency,
                                'request_image',
                                $identifierTypes[$identifier],
                                $identifier,
                                json_encode(['message' => 'image maybe found']),
                                'undetermined'
                            );
                            $target->addEntry($entry);
                            ++$entriesAdded;
                            ++$entriesAddedFromDay;
                        }
                    }
                }
                $this->progressAdvance();
            }
            $this->progressAdvance();
        } while (!empty($statistics));
    }

    /**
     * Create a new entry.
     *
     * @param \DateTime $date
     *   The date of the registration in elasticsearch
     * @param string $elasticId
     *   The id of the entry in elasticsearch
     * @param string $agency
     *   The agency connected with the event
     * @param string $event
     *   The event
     * @param string $identifierType
     *   The identifier type
     * @param string $materialId
     *   The material id of the event
     * @param string $response
     *   The response
     * @param string|null $imageId
     *   The image id
     *
     * @return Entry
     */
    private function createEntry(\DateTime $date, string $elasticId, string $agency, string $event, string $identifierType, string $materialId, string $response, ?string $imageId): Entry
    {
        $entry = new Entry();

        $entry->setDate($date);
        $entry->setElasticId($elasticId);
        $entry->setClientId('CoverService');
        $entry->setAgency($agency);
        $entry->setEvent($event);
        $entry->setIdentifierType($identifierType);
        $entry->setMaterialId($materialId);
        $entry->setResponse($response);
        $entry->setImageId($imageId);
        $entry->setExtracted(false);

        return $entry;
    }
}
