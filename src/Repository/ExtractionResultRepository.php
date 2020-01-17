<?php

namespace App\Repository;

use App\Document\ExtractionResult;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class ExtractionResultRepository
 */
class ExtractionResultRepository extends DocumentRepository
{
    /**
     * ExtractionResultRepository constructor.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     *   The doctrine document manager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $uow = $documentManager->getUnitOfWork();
        $classMetaData = $documentManager->getClassMetadata(ExtractionResult::class);
        parent::__construct($documentManager, $uow, $classMetaData);
    }

    /**
     * Get the newest entry.
     *
     * @return array|object|null
     *   The result
     */
    public function getNewestEntry()
    {
        return $this->createQueryBuilder()
            ->sort('date', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();
    }
}
