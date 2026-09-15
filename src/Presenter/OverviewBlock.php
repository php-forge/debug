<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents labeled overview fields in display order.
 */
final readonly class OverviewBlock implements Block
{
    /**
     * @param list<FieldEntry> $fields Labeled fields in display order.
     * @param bool $compact Whether to request compact presentation from the host.
     */
    public function __construct(public array $fields, public bool $compact) {}
}
