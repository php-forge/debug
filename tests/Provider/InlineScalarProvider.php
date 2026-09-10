<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Provider;

use PHPForge\Debug\Tests\PanelViewTest;

/**
 * Provides scalar and `null` inputs whose conversion to plain inline text is verified by {@see PanelViewTest}.
 */
final class InlineScalarProvider
{
    /**
     * @return iterable<string, array{mixed, array{kind: 'text', value: string, style: 'plain'}}>
     */
    public static function plainText(): iterable
    {
        yield 'false' => [false, ['kind' => 'text', 'value' => 'false', 'style' => 'plain']];
        yield 'float' => [1.5, ['kind' => 'text', 'value' => '1.5', 'style' => 'plain']];
        yield 'int' => [7, ['kind' => 'text', 'value' => '7', 'style' => 'plain']];
        yield 'null' => [null, ['kind' => 'text', 'value' => 'null', 'style' => 'plain']];
        yield 'string' => ['<raw>', ['kind' => 'text', 'value' => '<raw>', 'style' => 'plain']];
        yield 'true' => [true, ['kind' => 'text', 'value' => 'true', 'style' => 'plain']];
    }
}
