<?php

namespace App\Document;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\MongoDbOdm\Filter\DateFilter;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"},
 * )
 * @ApiFilter(DateFilter::class, properties={"date"})
 * @ApiFilter(BooleanFilter::class, properties={"extracted"})
 *
 * @ODM\Document
 */
class Entry implements \JsonSerializable
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
     * @ODM\Field(type="string")
     */
    protected $agency;

    /**
     * @ODM\Field(type="string")
     */
    protected $event;

    /**
     * @ODM\Field(type="string")
     */
    protected $materialId;

    /**
     * @ODM\Field(type="raw")
     */
    protected $response;

    /**
     * @ODM\Field(type="string")
     */
    protected $imageId;

    /**
     * @ODM\Field(type="date")
     */
    protected $extractionDate;

    /**
     * @ODM\Field(type="boolean")
     */
    protected $extracted;

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
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getAgency()
    {
        return $this->agency;
    }

    /**
     * @param mixed $agency
     */
    public function setAgency($agency): void
    {
        $this->agency = $agency;
    }

    /**
     * @return mixed
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @param mixed $event
     */
    public function setEvent($event): void
    {
        $this->event = $event;
    }

    /**
     * @return mixed
     */
    public function getMaterialId()
    {
        return $this->materialId;
    }

    /**
     * @param mixed $materialId
     */
    public function setMaterialId($materialId): void
    {
        $this->materialId = $materialId;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }

    /**
     * @return mixed
     */
    public function getImageId()
    {
        return $this->imageId;
    }

    /**
     * @param mixed $imageId
     */
    public function setImageId($imageId): void
    {
        $this->imageId = $imageId;
    }

    /**
     * @return mixed
     */
    public function getExtracted()
    {
        return $this->extracted;
    }

    /**
     * @param mixed $extracted
     */
    public function setExtracted($extracted): void
    {
        $this->extracted = $extracted;
    }

    /**
     * @return mixed
     */
    public function getExtractionDate()
    {
        return $this->extractionDate;
    }

    /**
     * @param mixed $extractionDate
     */
    public function setExtractionDate($extractionDate): void
    {
        $this->extractionDate = $extractionDate;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->getId(),
            'date' => $this->getDate(),
            'clientId' => $this->getClientId(),
            'agency' => $this->getAgency(),
            'event' => $this->getEvent(),
            'materialId' => $this->getMaterialId(),
            'response' => $this->getResponse(),
            'imageId' => $this->getImageId(),
            'extracted' => $this->getExtracted(),
            'extractionDate' => $this->getExtractionDate(),
        ];
    }
}
