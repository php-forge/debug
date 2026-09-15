<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one labeled field of an overview block.
 */
final readonly class FieldEntry
{
    /**
     * @param string $label Field name; the host escapes it.
     * @param Inline $value Field value in its inline presentation.
     */
    public function __construct(public string $label, public Inline $value) {}
}
