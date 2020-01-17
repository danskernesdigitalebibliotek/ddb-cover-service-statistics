<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(repositoryClass=ExtractionResultRepository::class)
 */
class ExtractionResult implements \JsonSerializable
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
     * @ODM\Field(type="string")
     */
    protected $clientId;

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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'date' => $this->getDate(),
        ];
    }
}
