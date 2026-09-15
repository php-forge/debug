<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a labeled strip of navigation links.
 */
final readonly class LinksBlock implements Block
{
    /**
     * @param string $label Text introducing the strip, such as `Depends on 2`; the host escapes it.
     * @param list<LinkInline> $links Links in display order.
     */
    public function __construct(public string $label, public array $links) {}
}
