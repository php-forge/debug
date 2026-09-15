<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a card describing one entity, optionally split into titled columns of its own content.
 */
final readonly class CardBlock implements Block
{
    /**
     * @param string $id Anchor the host emits so other blocks can link to the card, or `''` to omit it.
     * @param string $icon Host icon key shown before the title, or `''` to omit it.
     * @param string $title Entity name shown as the card heading; the host escapes it.
     * @param string $subtitle Qualifier shown under the title, or `''` to omit it.
     * @param list<Inline> $meta Inline values shown beside the title, such as counts.
     * @param list<ColumnEntry> $columns Titled columns of the card body in display order.
     */
    public function __construct(
        public string $id,
        public string $icon,
        public string $title,
        public string $subtitle,
        public array $meta,
        public array $columns,
    ) {}
}
