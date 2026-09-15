<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents an ordinary or section-level heading.
 */
final readonly class HeadingBlock implements Block
{
    /**
     * @param string $title Heading text.
     * @param bool $section Whether to request section-heading presentation from the host.
     */
    public function __construct(public string $title, public bool $section) {}
}
