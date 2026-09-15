<?php

declare(strict_types=1);

namespace PHPForge\Debug\Presenter;

/**
 * Represents inline text carrying a semantic presentation style.
 */
final readonly class TextInline implements Inline
{
    /**
     * @param string $value Text content; the host escapes it.
     * @param TextStyle $style Presentation style interpreted by the host frontend.
     */
    public function __construct(public string $value, public TextStyle $style) {}
}
