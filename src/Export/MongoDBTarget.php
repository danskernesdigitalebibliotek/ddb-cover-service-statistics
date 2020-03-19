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
     * @param DocumentManager $documentManager
     * @param EntryRepository $entryRepository
     * @param ExtractionResultRepository $extractionResultRepository
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
        // Since we use services to write to the database, we do not need to initialize anything.
    }

    /**
     * {@inheritdoc}
     */
    public function finish(): void
    {
        // Nothing needs to be closed.
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
    public function setExtractionTypes(array $types): void
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptsType(string $type): bool
    {
        // Accepts all types.
        return true;
    }
}
