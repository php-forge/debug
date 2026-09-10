<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\{Panel, PanelView};
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for extension-owned panel metadata.
 */
final class PanelDefinitionTest extends TestCase
{
    public function testExtensionDefinesItsMetadataAndContent(): void
    {
        $panel = new class extends Panel {
            protected const string ID = 'cache';
            protected const string TITLE = 'Application cache';
            protected const string ICON = 'database';

            public function present(array $data): PanelView
            {
                return PanelView::create()
                    ->heading('Cache entries')
                    ->table(['Key', 'Hits'], [['home', 5]]);
            }
        };

        self::assertSame(
            'cache',
            $panel->id(),
            'The extension must own its stable ID.',
        );
        self::assertSame(
            'Application cache',
            $panel->name(),
            'The extension must own its navigation title.',
        );
        self::assertSame(
            'database',
            $panel->icon(),
            'The extension must choose its icon.',
        );
        self::assertEquals(
            PanelView::create()
                ->heading('Cache entries')
                ->table(['Key', 'Hits'], [['home', 5]]),
            $panel->present([]),
            'The extension must own section titles, table headers, and values.',
        );
    }

    public function testRejectsMissingMetadataExplicitly(): void
    {
        $panel = new class extends Panel {
            public function present(array $data): PanelView
            {
                return PanelView::create();
            }
        };

        $accessors = ['ICON' => $panel->icon(...), 'ID' => $panel->id(...), 'TITLE' => $panel->name(...)];

        foreach ($accessors as $field => $accessor) {
            try {
                $accessor();
                self::fail(
                    'Missing definition metadata must be rejected.',
                );
            } catch (InvalidArgumentException $exception) {
                self::assertSame(
                    "A panel definition must declare {$field}.",
                    $exception->getMessage(),
                    'The error must identify the missing metadata.',
                );
            }
        }
    }
}
