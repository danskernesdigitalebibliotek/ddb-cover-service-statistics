<?php
/**
 * @file
 * Contains CSV implementation of ExtractionTargetInterface.
 */

namespace App\Model;

use App\Document\Entry;
use App\Document\ExtractionResult;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

class CsvTarget implements ExtractionTargetInterface
{
    /* @var \Box\Spout\Writer\WriterInterface $writer */
    private $writer;
    /* @var string $filename */
    private $filename;

    /**
     * CsvTarget constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->writer = WriterEntityFactory::createCSVWriter();

        $this->writer->openToFile($this->filename);

        // Add headers.
        $headers = [
            'elasticId',
            'date',
            'clientId',
            'agency',
            'event',
            'identifierType',
            'materialId',
            'response',
            'imageId',
        ];
        $rowFromValues = WriterEntityFactory::createRowFromArray($headers);
        $this->writer->addRow($rowFromValues);
    }

    /**
     * @inheritDoc
     */
    public function finish(): void
    {
        $this->writer->close();
    }

    /**
     * @inheritDoc
     */
    public function addEntry(Entry $entry): void
    {
        $values = [
            $entry->getElasticId(),
            $entry->getDate()->format('c'),
            $entry->getClientId(),
            $entry->getAgency(),
            $entry->getEvent(),
            $entry->getIdentifierType(),
            $entry->getMaterialId(),
            $entry->getResponse(),
            $entry->getImageId(),
        ];
        $rowFromValues = WriterEntityFactory::createRowFromArray($values);
        $this->writer->addRow($rowFromValues);
    }

    /**
     * @inheritDoc
     */
    public function entryExists($id): bool
    {
        // Since this is a spreadsheet export, we are not concerned with already added entries.
        return false;
    }

    /**
     * @inheritDoc
     */
    public function recordExtractionResult(ExtractionResult $extractionResult): void {}

    /**
     * @inheritDoc
     */
    public function flush(): void {}
}
