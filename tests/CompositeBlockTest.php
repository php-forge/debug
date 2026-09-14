<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use Closure;
use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\PanelView;
use PHPForge\Debug\Tests\Provider\MalformedEntryProvider;
use PHPUnit\Framework\Attributes\{DataProviderExternal, Group};
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the {@see PanelView} composite blocks describing facts, readouts, pills, manifests, and sections.
 *
 * {@see MalformedEntryProvider} for test case data providers.
 */
#[Group('panel-view')]
final class CompositeBlockTest extends TestCase
{
    public function testFactsKeepTheirLabelAndValuePairs(): void
    {
        self::assertSame(
            [
                [
                    'kind' => 'facts',
                    'facts' => [
                        ['kind' => 'fact', 'label' => 'Charset', 'value' => 'UTF-8'],
                        ['kind' => 'fact', 'label' => 'Language', 'value' => 'en'],
                    ],
                ],
            ],
            PanelView::create()
                ->facts(PanelView::fact('Charset', 'UTF-8'), PanelView::fact('Language', 'en'))
                ->blocks(),
            'A fact strip keeps every pair in declaration order.',
        );
    }

    public function testManifestGroupsPackagesUnderOneVendorLabel(): void
    {
        $view = PanelView::create()->manifest(
            'yiisoft/',
            PanelView::package('aliases', 'v3.1.1'),
            PanelView::package('arrays', 'v3.2.1'),
        );

        self::assertSame(
            [
                [
                    'kind' => 'manifest',
                    'label' => 'yiisoft/',
                    'packages' => [
                        ['kind' => 'package', 'name' => 'aliases', 'version' => 'v3.1.1'],
                        ['kind' => 'package', 'name' => 'arrays', 'version' => 'v3.2.1'],
                    ],
                ],
            ],
            $view->blocks(),
            'A manifest keeps its packages in declaration order under the vendor label.',
        );
    }

    public function testPillsKeepTheirStateAndOrder(): void
    {
        $view = PanelView::create()->pills(
            PanelView::pill('APCu', 'on', true),
            PanelView::pill('Memcache', 'off', false),
        );

        self::assertSame(
            [
                [
                    'kind' => 'pills',
                    'pills' => [
                        ['kind' => 'pill', 'label' => 'APCu', 'state' => 'on', 'enabled' => true],
                        ['kind' => 'pill', 'label' => 'Memcache', 'state' => 'off', 'enabled' => false],
                    ],
                ],
            ],
            $view->blocks(),
            'A pill strip keeps every subject with its own state.',
        );
    }

    public function testReadoutCaptionIsOptional(): void
    {
        self::assertSame(
            ['kind' => 'readout', 'label' => 'Yii', 'value' => '3', 'caption' => ''],
            PanelView::readout('Yii', '3'),
            'A readout without a qualifier carries an empty caption.',
        );
        self::assertSame(
            [['kind' => 'readouts', 'readouts' => [['kind' => 'readout', 'label' => 'PHP', 'value' => '8.5.9', 'caption' => 'runtime']]]],
            PanelView::create()->readouts(PanelView::readout('PHP', '8.5.9', 'runtime'))->blocks(),
            'A readout row carries its cards in declaration order.',
        );
    }

    public function testSectionCountIsOptionalAndWrapsItsOwnBlocks(): void
    {
        $content = PanelView::create()->paragraph('Nothing captured.');
        $blocks = PanelView::create()
            ->section('::', 'Extensions', $content)
            ->section('//', 'Details', $content, 47)
            ->blocks();

        self::assertSame(
            ['kind' => 'section', 'mark' => '::', 'title' => 'Extensions', 'count' => null, 'content' => $content],
            $blocks[0] ?? [],
            'A section without a tally reports `null`.',
        );
        self::assertSame(
            ['kind' => 'section', 'mark' => '//', 'title' => 'Details', 'count' => 47, 'content' => $content],
            $blocks[1] ?? [],
            'A section keeps the tally it was given.',
        );
    }
    /**
     * @param Closure(): PanelView $build Composition that must reject the malformed entry.
     * @param string $kind Entry kind the rejected argument was meant to carry.
     */
    #[DataProviderExternal(MalformedEntryProvider::class, 'entries')]
    public function testThrowInvalidArgumentExceptionForAnEntryTheFactoryDidNotBuild(Closure $build, string $kind): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::ENTRY_INVALID->getMessage($kind, $kind),
        );

        $build();
    }
}
