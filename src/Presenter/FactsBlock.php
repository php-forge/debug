<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a compact strip of label and value pairs.
 */
final readonly class FactsBlock implements Block
{
    /**
     * @param list<FactEntry> $facts Fact pairs in display order.
     */
    public function __construct(public array $facts) {}
}
