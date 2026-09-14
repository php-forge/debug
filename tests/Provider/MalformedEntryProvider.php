<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Provider;

use Closure;
use PHPForge\Debug\PanelView;
use PHPForge\Debug\Tests\CompositeBlockTest;

/**
 * Provides composite-block entries no factory produced, whose rejection is verified by {@see CompositeBlockTest}.
 */
final class MalformedEntryProvider
{
    /**
     * @return iterable<string, array{Closure(): PanelView, string}>
     */
    public static function entries(): iterable
    {
        yield 'fact built by another factory' => [
            static fn(): PanelView => PanelView::create()->facts(
                ['kind' => 'pill', 'label' => 'Charset', 'value' => 'UTF-8'],
            ),
            'fact',
        ];
        yield 'fact without a label' => [
            static fn(): PanelView => PanelView::create()->facts(
                ['kind' => 'fact', 'label' => 8, 'value' => 'UTF-8'],
            ),
            'fact',
        ];
        yield 'fact without a value' => [
            static fn(): PanelView => PanelView::create()->facts(
                ['kind' => 'fact', 'label' => 'Charset', 'value' => 8],
            ),
            'fact',
        ];
        yield 'empty fact' => [
            static fn(): PanelView => PanelView::create()->facts([]),
            'fact',
        ];
        yield 'package built by another factory' => [
            static fn(): PanelView => PanelView::create()->manifest(
                'yiisoft/',
                ['kind' => 'fact', 'name' => 'aliases', 'version' => 'v3.1.1'],
            ),
            'package',
        ];
        yield 'package without a name' => [
            static fn(): PanelView => PanelView::create()->manifest(
                'yiisoft/',
                ['kind' => 'package', 'name' => 1, 'version' => 'v3.1.1'],
            ),
            'package',
        ];
        yield 'package without a version' => [
            static fn(): PanelView => PanelView::create()->manifest(
                'yiisoft/',
                ['kind' => 'package', 'name' => 'aliases', 'version' => 3],
            ),
            'package',
        ];
        yield 'empty package' => [
            static fn(): PanelView => PanelView::create()->manifest('yiisoft/', []),
            'package',
        ];
        yield 'pill built by another factory' => [
            static fn(): PanelView => PanelView::create()->pills(
                ['kind' => 'fact', 'label' => 'APCu', 'state' => 'on', 'enabled' => true],
            ),
            'pill',
        ];
        yield 'pill without a label' => [
            static fn(): PanelView => PanelView::create()->pills(
                ['kind' => 'pill', 'label' => 1, 'state' => 'on', 'enabled' => true],
            ),
            'pill',
        ];
        yield 'pill without a state' => [
            static fn(): PanelView => PanelView::create()->pills(
                ['kind' => 'pill', 'label' => 'APCu', 'state' => 1, 'enabled' => true],
            ),
            'pill',
        ];
        yield 'pill without a boolean flag' => [
            static fn(): PanelView => PanelView::create()->pills(
                ['kind' => 'pill', 'label' => 'APCu', 'state' => 'on', 'enabled' => 'yes'],
            ),
            'pill',
        ];
        yield 'readout built by another factory' => [
            static fn(): PanelView => PanelView::create()->readouts(
                ['kind' => 'fact', 'label' => 'Yii', 'value' => '3', 'caption' => 'framework'],
            ),
            'readout',
        ];
        yield 'readout without a label' => [
            static fn(): PanelView => PanelView::create()->readouts(
                ['kind' => 'readout', 'label' => 1, 'value' => '3', 'caption' => 'framework'],
            ),
            'readout',
        ];
        yield 'readout without a value' => [
            static fn(): PanelView => PanelView::create()->readouts(
                ['kind' => 'readout', 'label' => 'Yii', 'value' => 3, 'caption' => 'framework'],
            ),
            'readout',
        ];
        yield 'readout without a caption' => [
            static fn(): PanelView => PanelView::create()->readouts(
                ['kind' => 'readout', 'label' => 'Yii', 'value' => '3', 'caption' => 0],
            ),
            'readout',
        ];
    }
}
