<?php
/**
 * @file
 * Contains CSV implementation of ExtractionTargetInterface.
 */

namespace App\Export;

use App\Document\Entry;
use App\Document\ExtractionResult;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

/**
 * Class CsvTarget.
 */
class CsvTarget implements ExtractionTargetInterface
{
    /* @var \Box\Spout\Writer\WriterInterface $writer */
    private $writer;
    /* @var string $filename */
    private $filename;
    /* @var array $types */
    private $types = null;

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
     * Make sure the writer has been closed.
     */
    public function __destruct()
    {
        if (isset($this->writer)) {
            $this->writer->close();
        }
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function finish(): void
    {
        $this->writer->close();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function entryExists($id): bool
    {
        // Since this is a spreadsheet export, we are not concerned with already added entries.
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function recordExtractionResult(ExtractionResult $extractionResult): void
    {
        // We do not do anything to the result, since we are writing to a file.
    }

    /**
     * {@inheritdoc}
     */
    public function flush(): void
    {
        // No need to flush results, since we are streaming rows to the file.
    }

    /**
     * {@inheritdoc}
     */
    public function setExtractionTypes(array $types): void
    {
        $this->types = $types;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptsType(string $type): bool
    {
        if (null !== $this->types) {
            return in_array($type, $this->types);
        }

        return true;
    }
}
