<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a strip of status pills.
 */
final readonly class PillsBlock implements Block
{
    /**
     * @param list<PillEntry> $pills Pills in display order.
     */
    public function __construct(public array $pills) {}
}
