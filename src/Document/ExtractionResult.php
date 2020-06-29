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
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getNumberOfEntriesAdded(): int
    {
        return $this->numberOfEntriesAdded;
    }

    /**
     * @param int $numberOfEntriesAdded
     */
    public function setNumberOfEntriesAdded($numberOfEntriesAdded): void
    {
        $this->numberOfEntriesAdded = $numberOfEntriesAdded;
    }
}
