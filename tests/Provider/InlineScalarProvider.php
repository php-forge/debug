<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Provider;

use PHPForge\Debug\Presenter\TextInline;
use PHPForge\Debug\Presenter\TextStyle;
use PHPForge\Debug\Tests\PanelViewTest;

/**
 * Provides scalar and `null` inputs whose conversion to plain inline text is verified by {@see PanelViewTest}.
 */
final class InlineScalarProvider
{
    /**
     * @return iterable<string, array{mixed, TextInline}>
     */
    public static function plainText(): iterable
    {
        yield 'false' => [false, new TextInline('false', TextStyle::PLAIN)];
        yield 'float' => [1.5, new TextInline('1.5', TextStyle::PLAIN)];
        yield 'int' => [7, new TextInline('7', TextStyle::PLAIN)];
        yield 'null' => [null, new TextInline('null', TextStyle::PLAIN)];
        yield 'string' => ['<raw>', new TextInline('<raw>', TextStyle::PLAIN)];
        yield 'true' => [true, new TextInline('true', TextStyle::PLAIN)];
    }
}
