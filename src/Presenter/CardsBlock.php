<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents a set of entity cards the host lays out together.
 */
final readonly class CardsBlock implements Block
{
    /**
     * @param list<CardEntry> $cards Entity cards in display order.
     */
    public function __construct(public array $cards) {}
}
