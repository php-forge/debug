<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one compact label and value pair of a fact strip.
 */
final readonly class FactEntry
{
    /**
     * @param string $label Name of the fact; the host escapes it.
     * @param string $value Recorded value; the host escapes it.
     */
    public function __construct(public string $label, public string $value) {}
}
