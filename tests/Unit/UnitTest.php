<?php

namespace App\Tests;

use App\Document\Entry;
use App\Document\ExtractionResult;
use PHPUnit\Framework\TestCase;

/**
 * Class UnitTest.
 *
 * Contains unit tests for the App\Document\Entry.
 */
class UnitTest extends TestCase
{
    /**
     * Test that Entry document serialization works.
     *
     * @throws \Exception
     */
    public function testEntryDocumentSerialization(): void
    {
        $date = new \DateTime();

        $entry = new Entry();
        $entry->setResponse('rawData');
        $entry->setMaterialId('2');
        $entry->setImageId('3');
        $entry->setEvent('4');
        $entry->setDate($date);
        $entry->setClientId('6');
        $entry->setAgency('7');

        $encoded = json_encode($entry);

        $decoded = json_decode($encoded);

        $this->assertEquals('rawData', $decoded->response);
        $this->assertEquals('2', $decoded->materialId);
        $this->assertEquals('3', $decoded->imageId);
        $this->assertEquals('4', $decoded->event);
        $this->assertEquals(json_decode(json_encode($date)), $decoded->date);
        $this->assertEquals('6', $decoded->clientId);
        $this->assertEquals('7', $decoded->agency);
    }

    /**
     * Test that ExtractionResult document serialization works.
     *
     * @throws \Exception
     */
    public function testExtractionResultDocumentSerialization(): void
    {
        $date = new \DateTime();

        $extractionResult = new ExtractionResult();
        $extractionResult->setDate($date);
        $extractionResult->setNumberOfEntriesAdded(5);

        $encoded = json_encode($extractionResult);

        $decoded = json_decode($encoded);

        $this->assertEquals(json_decode(json_encode($date)), $decoded->date);
        $this->assertEquals(5, $decoded->numberOfEntriesAdded);
    }
}
