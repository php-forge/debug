<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Marks an inline value a panel can place inside blocks, metrics, and table cells.
 *
 * The union is sealed, so a host narrows an inline value with `instanceof` and static analysis proves that no case is
 * left unhandled.
 *
 * @phpstan-sealed BadgeInline|LinkInline|TextInline|TraceInline|ValueInline
 */
interface Inline {}
