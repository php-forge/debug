<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a row of headline readout cards.
 */
final readonly class ReadoutsBlock implements Block
{
    /**
     * @param list<ReadoutEntry> $readouts Readout cards in display order.
     */
    public function __construct(public array $readouts) {}
}
