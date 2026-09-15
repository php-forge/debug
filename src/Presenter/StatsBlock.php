<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a strip of headline stat tiles.
 */
final readonly class StatsBlock implements Block
{
    /**
     * @param list<StatEntry> $stats Stat tiles in display order.
     */
    public function __construct(public array $stats) {}
}
