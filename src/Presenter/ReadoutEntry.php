<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents one headline readout card.
 */
final readonly class ReadoutEntry
{
    /**
     * @param string $label Metric name shown above the value; the host escapes it.
     * @param string $value Headline value; the host escapes it.
     * @param string $caption Qualifier shown under the value, or `''` to omit it.
     */
    public function __construct(public string $label, public string $value, public string $caption) {}
}
