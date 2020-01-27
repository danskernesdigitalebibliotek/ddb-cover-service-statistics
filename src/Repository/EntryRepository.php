<?php

/**
 * @file
 * Contains repository for Entry documents.
 */

namespace App\Repository;

use App\Document\Entry;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class EntryRepository.
 */
class EntryRepository extends DocumentRepository
{
    /**
     * EntryRepository constructor.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     *   The doctrine document manager
     */
    public function __construct(DocumentManager $documentManager)
    {
        // Because unit of work and class meta data are not injectable we
        // manually inject them.
        $uow = $documentManager->getUnitOfWork();
        $classMetaData = $documentManager->getClassMetadata(Entry::class);
        parent::__construct($documentManager, $uow, $classMetaData);
    }

    /**
     * Check if an entry with the given elasticId exists.
     *
     * @param string $elasticId
     *   The _id field from elasticsearch entries
     *
     * @return bool
     */
    public function entryExists(string $elasticId): bool
    {
        return !empty($this->findOneBy(['elasticId' => $elasticId]));
    }
}
