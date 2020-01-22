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
        // Because it cannot be auto-wired.
        $uow = $documentManager->getUnitOfWork();
        $classMetaData = $documentManager->getClassMetadata(Entry::class);
        parent::__construct($documentManager, $uow, $classMetaData);
    }
}
