<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a titled plain-text disclosure.
 */
final readonly class DisclosureBlock implements Block
{
    /**
     * @param string $title Disclosure label.
     * @param string $content Plain-text payload, preserved without formatting.
     */
    public function __construct(public string $title, public string $content) {}
}
