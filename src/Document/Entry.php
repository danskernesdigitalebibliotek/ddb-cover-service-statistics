<?php

/**
 * @file
 * Entry document.
 *
 * Suppress phan false positive:
 * @phan-file-suppress PhanUnreferencedUseNormal
 */

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
 * @ODM\Document(repositoryClass=App\Repository\EntryRepository::class)
 */
class Entry
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
    protected $elasticId;

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
     * @return string
     */
    public function getId()
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
     * @return string
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId): void
    {
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getAgency(): string
    {
        return $this->agency;
    }

    /**
     * @param string $agency
     */
    public function setAgency($agency): void
    {
        $this->agency = $agency;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @param string $event
     */
    public function setEvent($event): void
    {
        $this->event = $event;
    }

    /**
     * @return string
     */
    public function getMaterialId(): string
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
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response): void
    {
        $this->response = $response;
    }

    /**
     * @return string|null
     */
    public function getImageId(): ?string
    {
        return $this->imageId;
    }

    /**
     * @param string $imageId
     */
    public function setImageId($imageId): void
    {
        $this->imageId = $imageId;
    }

    /**
     * @return bool
     */
    public function getExtracted(): bool
    {
        return $this->extracted;
    }

    /**
     * @param bool $extracted
     */
    public function setExtracted($extracted): void
    {
        $this->extracted = $extracted;
    }

    /**
     * @return \DateTime|null
     */
    public function getExtractionDate(): ?\DateTime
    {
        return $this->extractionDate;
    }

    /**
     * @param \DateTime $extractionDate
     */
    public function setExtractionDate($extractionDate): void
    {
        $this->extractionDate = $extractionDate;
    }

    /**
     * @return string
     */
    public function getElasticId(): string
    {
        return $this->elasticId;
    }

    /**
     * @param string $elasticId
     */
    public function setElasticId($elasticId): void
    {
        $this->elasticId = $elasticId;
    }
}
