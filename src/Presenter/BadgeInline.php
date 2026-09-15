<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

use PHPForge\Debug\Tone;

/**
 * Represents an inline status label carrying a semantic tone.
 */
final readonly class BadgeInline implements Inline
{
    /**
     * @param string $label Badge text; the host escapes it.
     * @param Tone $tone Semantic tone interpreted by the host frontend.
     */
    public function __construct(public string $label, public Tone $tone) {}
}
