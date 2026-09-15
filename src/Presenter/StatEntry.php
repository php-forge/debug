<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\Tone;

/**
 * Represents one headline stat tile.
 */
final readonly class StatEntry
{
    /**
     * @param string $icon Host icon key shown above the value.
     * @param string $label Metric name shown under the value; the host escapes it.
     * @param string $value Headline value; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend, or {@see Tone::MUTED} to keep its own accent.
     */
    public function __construct(public string $icon, public string $label, public string $value, public Tone $tone) {}
}
