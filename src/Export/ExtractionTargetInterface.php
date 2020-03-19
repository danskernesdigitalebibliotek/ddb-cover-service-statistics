<?php
/**
 * @file
 * Contains interface for an ExtractionTarget.
 */

namespace App\Export;

use App\Document\Entry;
use App\Document\ExtractionResult;

/**
 * Interface ExtractionTargetInterface.
 */
interface ExtractionTargetInterface
{
    /**
     * Prepare target.
     */
    public function initialize(): void;

    /**
     * Finish target.
     */
    public function finish(): void;

    /**
     * Adds an entry to the target.
     *
     * @param Entry $entry
     *   The entry
     */
    public function addEntry(Entry $entry): void;

    /**
     * Tests for entry existence in the target.
     *
     * @param $id
     *   The id to test for
     *
     * @return bool
     */
    public function entryExists($id): bool;

    /**
     * Record the result of an extraction.
     *
     * @param ExtractionResult $extractionResult
     *   The extraction result
     */
    public function recordExtractionResult(ExtractionResult $extractionResult): void;

    /**
     * Flush the target to free memory.
     */
    public function flush(): void;

    /**
     * Select which types should be extracted.
     *
     * Default: all
     * Types: 'hit', 'nohit', 'undetermined'.
     *
     * @param array $types
     *   The selected types
     */
    public function setExtractionTypes(array $types): void;

    /**
     * Returns true if the target accepts the type.
     *
     * @param string $type
     *   The type. Types: 'hit', 'nohit', 'undetermined'.
     *
     * @return bool
     */
    public function acceptType(string $type): bool;
}
