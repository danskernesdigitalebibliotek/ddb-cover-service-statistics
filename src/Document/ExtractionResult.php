<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(repositoryClass=App\Repository\ExtractionResultRepository::class)
 */
class ExtractionResult
{
    /**
     * @ODM\Id()
     */
    protected $id;

    /**
     * @ODM\Field(type="date")
     */
    protected $date;

    /**
     * @ODM\Field(type="integer")
     */
    protected $numberOfEntriesAdded;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getNumberOfEntriesAdded()
    {
        return $this->numberOfEntriesAdded;
    }

    /**
     * @param mixed $numberOfEntriesAdded
     */
    public function setNumberOfEntriesAdded($numberOfEntriesAdded): void
    {
        $this->numberOfEntriesAdded = $numberOfEntriesAdded;
    }
}
