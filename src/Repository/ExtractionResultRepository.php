<?php


namespace App\Repository;

use App\Document\ExtractionResult;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

class ExtractionResultRepository extends DocumentRepository
{
    public function __construct(DocumentManager $documentManagerm)
    {
        $uow = $documentManagerm->getUnitOfWork();
        $classMetaData = $documentManagerm->getClassMetadata(ExtractionResult::class);
        parent::__construct($documentManagerm, $uow, $classMetaData);
    }

    public function getLastEntry()
    {
        return $this->createQueryBuilder()
            ->sort('e.date', 'DESC')
            ->limit(1)
            ->getQuery()
            ->getSingleResult();
    }
}
