<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a captured diagnostic value the host formats and escapes.
 */
final readonly class ValueInline implements Inline
{
    /**
     * @param mixed $value Diagnostic value, preserved without conversion.
     * @param bool $typeOnly Whether to show only the value's type instead of its contents.
     */
    public function __construct(public mixed $value, public bool $typeOnly) {}
}
