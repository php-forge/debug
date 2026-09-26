<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\{PanelView, Tone};
use PHPForge\Debug\Presenter\{
    CardEntry,
    CardsBlock,
    ColumnEntry,
    FactEntry,
    FactsBlock,
    FileEntry,
    FilesBlock,
    HeroBlock,
    LinkInline,
    LinksBlock,
    ManifestBlock,
    PackageEntry,
    PillEntry,
    PillsBlock,
    ReadoutEntry,
    ReadoutsBlock,
    SectionBlock,
    StatEntry,
    StatsBlock,
    TextInline,
    TextStyle,
};
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for the {@see PanelView} composite blocks describing heroes, cards, stats, files, links, facts, readouts,
 * pills, manifests, and sections.
 */
#[Group('panel-view')]
final class CompositeBlockTest extends TestCase
{
    public function testCardKeepsItsMetaAndTitledColumnsInDeclarationOrder(): void
    {
        $badge = PanelView::badge('3 css', Tone::INFO);

        $files = PanelView::create()->files(PanelView::file('.css', 'site.css', Tone::INFO));
        $wiring = PanelView::create()->facts(PanelView::fact('SOURCE', '@app/assets'));

        self::assertEquals(
            new CardEntry(
                'app-asset',
                'asset',
                'AppAsset',
                'App\\Asset\\',
                [$badge, new TextInline('1 js', TextStyle::PLAIN)],
                [new ColumnEntry('FILES', $files), new ColumnEntry('WIRING', $wiring)],
            ),
            PanelView::card(
                'app-asset',
                'asset',
                'AppAsset',
                'App\\Asset\\',
                [$badge, '1 js'],
                PanelView::column('FILES', $files),
                PanelView::column('WIRING', $wiring),
            ),
            'A card keeps its meta and columns in declaration order.',
        );

        $columns = ['files' => PanelView::column('FILES', $files), 'wiring' => PanelView::column('WIRING', $wiring)];

        self::assertEquals(
            [new ColumnEntry('FILES', $files), new ColumnEntry('WIRING', $wiring)],
            PanelView::card('app-asset', 'asset', 'AppAsset', '', [], ...$columns)->columns,
            'String keys must not reach the column list.',
        );
    }

    public function testCardsKeepTheirEntriesInDeclarationOrder(): void
    {
        $app = PanelView::card('app-asset', 'asset', 'AppAsset', '', []);
        $yii = PanelView::card('yii-asset', 'asset', 'YiiAsset', '', []);

        $cards = ['app' => $app, 'yii' => $yii];

        self::assertEquals(
            [new CardsBlock([$app, $yii]), new CardsBlock([])],
            PanelView::create()
                ->cards($app, $yii)
                ->cards()
                ->blocks(),
            'A card set keeps its cards in declaration order.',
        );
        self::assertEquals(
            [new CardsBlock([$app, $yii])],
            PanelView::create()->cards(...$cards)->blocks(),
            'String keys must not reach the card list.',
        );
    }

    public function testCardWithoutMetaOrColumnsCarriesEmptyLists(): void
    {
        self::assertEquals(
            new CardEntry('', '', 'AppAsset', '', [], []),
            PanelView::card('', '', 'AppAsset', '', []),
            'An unadorned card keeps its optional parts empty.',
        );
    }

    public function testFactsKeepTheirLabelAndValuePairs(): void
    {
        self::assertEquals(
            [new FactsBlock([new FactEntry('Charset', 'UTF-8'), new FactEntry('Language', 'en')])],
            PanelView::create()
                ->facts(PanelView::fact('Charset', 'UTF-8'), PanelView::fact('Language', 'en'))
                ->blocks(),
            'A fact strip keeps every pair in declaration order.',
        );

        $facts = ['charset' => PanelView::fact('Charset', 'UTF-8')];

        self::assertEquals(
            [new FactsBlock([new FactEntry('Charset', 'UTF-8')])],
            PanelView::create()->facts(...$facts)->blocks(),
            'String keys must not reach the fact list.',
        );
    }

    public function testFilesKeepTheirTypeToneAndOrder(): void
    {
        self::assertEquals(
            new FileEntry('.js', 'app.js', Tone::MUTED),
            PanelView::file('.js', 'app.js'),
            'A file without a tone stays muted.',
        );
        self::assertEquals(
            [
                new FilesBlock(
                    [
                        new FileEntry('.css', 'site.css', Tone::INFO),
                        new FileEntry('.js', 'app.js', Tone::WARNING),
                    ],
                ),
                new FilesBlock([]),
            ],
            PanelView::create()
                ->files(
                    PanelView::file('.css', 'site.css', Tone::INFO),
                    PanelView::file('.js', 'app.js', Tone::WARNING),
                )
                ->files()
                ->blocks(),
            'A file list keeps every entry in declaration order.',
        );

        $files = ['site' => PanelView::file('.css', 'site.css', Tone::INFO)];

        self::assertEquals(
            [new FilesBlock([new FileEntry('.css', 'site.css', Tone::INFO)])],
            PanelView::create()->files(...$files)->blocks(),
            'String keys must not reach the file list.',
        );
    }

    public function testHeroKeepsItsIdentityStatusAndMetricOrder(): void
    {
        $status = PanelView::badge('Active', Tone::SUCCESS);

        $metrics = ['id' => PanelView::fact('User ID', '1'), 'roles' => PanelView::fact('Roles', 'admin')];

        self::assertEquals(
            [
                new HeroBlock(
                    'AD',
                    'admin',
                    'admin@example.com',
                    $status,
                    [new FactEntry('User ID', '1'), new FactEntry('Roles', 'admin')],
                ),
            ],
            PanelView::create()->hero('AD', 'admin', 'admin@example.com', $status, ...$metrics)->blocks(),
            'Metrics must keep declaration order without their string keys.',
        );
        self::assertEquals(
            [new HeroBlock('', 'admin', '', null, [])],
            PanelView::create()->hero('', 'admin', '', null)->blocks(),
            'A neutral header keeps its optional parts empty.',
        );
    }

    public function testLinksKeepTheirLabelAndOrder(): void
    {
        self::assertEquals(
            [
                new LinksBlock(
                    'Depends on 2',
                    [
                        new LinkInline('YiiAsset', '#yii-asset', false),
                        new LinkInline('Docs', 'https://example.test/d', true),
                    ],
                ),
                new LinksBlock('Depends on 0', []),
            ],
            PanelView::create()
                ->links(
                    'Depends on 2',
                    PanelView::link('YiiAsset', '#yii-asset'),
                    PanelView::link('Docs', 'https://example.test/d', true),
                )
                ->links('Depends on 0')
                ->blocks(),
            'A link strip keeps every target in declaration order.',
        );

        $links = ['yii' => PanelView::link('YiiAsset', '#yii-asset')];

        self::assertEquals(
            [new LinksBlock('Depends on 1', [new LinkInline('YiiAsset', '#yii-asset', false)])],
            PanelView::create()->links('Depends on 1', ...$links)->blocks(),
            'String keys must not reach the link list.',
        );
    }

    public function testManifestGroupsPackagesUnderOneVendorLabel(): void
    {
        $view = PanelView::create()->manifest(
            'yiisoft/',
            PanelView::package('aliases', 'v3.1.1'),
            PanelView::package('arrays', 'v3.2.1'),
        );

        self::assertEquals(
            [
                new ManifestBlock(
                    'yiisoft/',
                    [new PackageEntry('aliases', 'v3.1.1'), new PackageEntry('arrays', 'v3.2.1')],
                ),
            ],
            $view->blocks(),
            'A manifest keeps its packages in declaration order under the vendor label.',
        );

        $packages = ['aliases' => PanelView::package('aliases', 'v3.1.1')];

        self::assertEquals(
            [new ManifestBlock('yiisoft/', [new PackageEntry('aliases', 'v3.1.1')])],
            PanelView::create()->manifest('yiisoft/', ...$packages)->blocks(),
            'String keys must not reach the package list.',
        );
    }

    public function testPillsKeepTheirStateAndOrder(): void
    {
        $view = PanelView::create()->pills(
            PanelView::pill('APCu', 'on', true),
            PanelView::pill('Memcache', 'off', false),
        );

        self::assertEquals(
            [new PillsBlock([new PillEntry('APCu', 'on', true), new PillEntry('Memcache', 'off', false)])],
            $view->blocks(),
            'A pill strip keeps every subject with its own state.',
        );

        $pills = ['apcu' => PanelView::pill('APCu', 'on', true)];

        self::assertEquals(
            [new PillsBlock([new PillEntry('APCu', 'on', true)])],
            PanelView::create()->pills(...$pills)->blocks(),
            'String keys must not reach the pill list.',
        );
    }

    public function testReadoutCaptionIsOptional(): void
    {
        self::assertEquals(
            new ReadoutEntry('Yii', '3', ''),
            PanelView::readout('Yii', '3'),
            'A readout without a qualifier carries an empty caption.',
        );
        self::assertEquals(
            [new ReadoutsBlock([new ReadoutEntry('PHP', '8.5.9', 'runtime')])],
            PanelView::create()
                ->readouts(PanelView::readout('PHP', '8.5.9', 'runtime'))
                ->blocks(),
            'A readout row carries its cards in declaration order.',
        );

        $readouts = ['php' => PanelView::readout('PHP', '8.5.9', 'runtime')];

        self::assertEquals(
            [new ReadoutsBlock([new ReadoutEntry('PHP', '8.5.9', 'runtime')])],
            PanelView::create()->readouts(...$readouts)->blocks(),
            'String keys must not reach the readout list.',
        );
    }

    public function testSectionCountIsOptionalAndWrapsItsOwnBlocks(): void
    {
        $content = PanelView::create()->paragraph('Nothing captured.');
        $blocks = PanelView::create()
            ->section('::', 'Extensions', $content)
            ->section('//', 'Details', $content, 47)
            ->blocks();

        self::assertEquals(
            new SectionBlock('::', 'Extensions', null, $content),
            $blocks[0] ?? null,
            'A section without a tally reports `null`.',
        );
        self::assertEquals(
            new SectionBlock('//', 'Details', 47, $content),
            $blocks[1] ?? null,
            'A section keeps the tally it was given.',
        );
    }

    public function testStatsKeepTheirIconValueAndTone(): void
    {
        $tile = PanelView::stat('asset', 'BUNDLES', '4', Tone::INFO);

        $tiles = ['bundles' => $tile];

        self::assertEquals(
            new StatEntry('link', 'LINKS', '2', Tone::MUTED),
            PanelView::stat('link', 'LINKS', '2'),
            'A stat without a tone stays muted.',
        );
        self::assertEquals(
            [new StatsBlock([new StatEntry('asset', 'BUNDLES', '4', Tone::INFO)]), new StatsBlock([])],
            PanelView::create()
                ->stats($tile)
                ->stats()
                ->blocks(),
            'A stat strip keeps its tiles in declaration order.',
        );
        self::assertEquals(
            [new StatsBlock([$tile])],
            PanelView::create()->stats(...$tiles)->blocks(),
            'String keys must not reach the stat list.',
        );
    }

    public function testThrowInvalidArgumentExceptionForCardMetaNoInlineFactoryBuilt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::INLINE_CONTENT_INVALID->getMessage('stdClass'),
        );

        PanelView::card('', '', 'AppAsset', '', [new stdClass()]);
    }
}
