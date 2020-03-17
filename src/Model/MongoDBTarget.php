<?php
/**
 * @file
 * Contains MongoDB implementation of ExtractionTargetInterface.
 */

namespace App\Model;

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
     * @inheritDoc
     */
    public function initialize(): void {}

    /**
     * @inheritDoc
     */
    public function finish(): void {}

    /**
     * @inheritDoc
     */
    public function addEntry(Entry $entry): void
    {
        $this->documentManager->persist($entry);
    }

    /**
     * @inheritDoc
     */
    public function entryExists($id): bool
    {
        return $this->entryRepository->entryExists($id);
    }

    /**
     * @inheritDoc
     */
    public function recordExtractionResult(ExtractionResult $extractionResult): void
    {
        $this->documentManager->persist($extractionResult);
    }

    /**
     * @inheritDoc
     * @throws \Doctrine\ODM\MongoDB\MongoDBException
     */
    public function flush(): void
    {
        $this->documentManager->flush();
        $this->documentManager->clear();
    }
}
