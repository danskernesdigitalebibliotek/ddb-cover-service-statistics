<?php
/**
 * @file
 * Contains MongoDB implementation of ExtractionTargetInterface.
 */

namespace App\Export;

use App\Document\Entry;
use App\Document\ExtractionResult;
use App\Repository\EntryRepository;
use App\Repository\ExtractionResultRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class MongoDBTarget.
 */
class MongoDBTarget implements ExtractionTargetInterface
{
    private $documentManager;
    private $extractionResultRepository;
    private $entryRepository;
    /* @var array $types */
    private $types;

    /**
     * MongoDBTarget constructor.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @param \App\Repository\EntryRepository $entryRepository
     * @param \App\Repository\ExtractionResultRepository $extractionResultRepository
     */
    public function __construct(DocumentManager $documentManager, EntryRepository $entryRepository, ExtractionResultRepository $extractionResultRepository)
    {
        $this->documentManager = $documentManager;
        $this->extractionResultRepository = $extractionResultRepository;
        $this->entryRepository = $entryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function finish(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function addEntry(Entry $entry): void
    {
        $this->documentManager->persist($entry);
    }

    /**
     * {@inheritdoc}
     */
    public function entryExists($id): bool
    {
        return $this->entryRepository->entryExists($id);
    }

    /**
     * {@inheritdoc}
     */
    public function recordExtractionResult(ExtractionResult $extractionResult): void
    {
        $this->documentManager->persist($extractionResult);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function flush(): void
    {
        $this->documentManager->flush();
        $this->documentManager->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function setExtractionTypes(array $types = null): void
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptType(string $type): bool
    {
        // Accepts all types.
        return true;
    }
}
