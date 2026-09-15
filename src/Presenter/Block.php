<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Marks a content block a panel description can carry.
 *
 * The union is sealed, so a host narrows a block with `instanceof` and static analysis proves that no case is left
 * unhandled.
 *
 * @phpstan-sealed CardBlock|DisclosureBlock|EmptyStateBlock|FactsBlock|FilesBlock|GroupBlock|HeadingBlock|LinksBlock
 *   |ManifestBlock|OverviewBlock|ParagraphBlock|PillsBlock|ReadoutsBlock|SectionBlock|StatsBlock|TableBlock
 */
interface Block {}
