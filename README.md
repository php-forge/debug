<!-- markdownlint-disable MD041 -->
<p align="center">
    <a href="https://github.com/php-forge/debug" target="_blank">
      <img src="https://avatars.githubusercontent.com/u/103309199?s=400&u=ca3561c692f53ed7eb290d3bb226a2828741606f&v=4" width="30%" alt="PHP Forge">
    </a>
    <h1 align="center">Debug Interop</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/php-forge/debug/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/debug/build.yml?style=for-the-badge&label=PHPUnit&logo=github" alt="PHPUnit">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/php-forge/debug/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fphp-forge%2Fdebug%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/php-forge/debug/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/debug/static.yml?style=for-the-badge&label=PHPStan&logo=github" alt="PHPStan">
    </a>
    <a href="https://github.com/php-forge/debug/actions/workflows/security.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/php-forge/debug/security.yml?style=for-the-badge&label=Security&logo=github" alt="Security">
    </a>
</p>

<p align="center">
    <strong>Framework-neutral contracts for portable collectors and panels rendered by the debugger frontend.</strong>
</p>

## A complete panel in two classes

A collector buffers diagnostics while the request runs; a panel turns the stored capture into a view. Neither
imports a debugger engine, and both share one identifier.

```php
use PHPForge\Debug\CollectorInterface;

final class CacheCollector implements CollectorInterface
{
    /**
     * @var list<array{string, string, string}>
     */
    private array $operations = [];
    private bool $started = false;

    public function capture(): array|null
    {
        return $this->started ? ['operations' => $this->operations] : null;
    }

    public function id(): string
    {
        return 'cache';
    }

    public function record(string $operation, string $key, string $result): void
    {
        if ($this->started) {
            $this->operations[] = [$operation, $key, $result];
        }
    }

    public function shutdown(): void
    {
        $this->started = false;
        $this->operations = [];
    }

    public function startup(): void
    {
        $this->started = true;
    }
}
```

```php
use PHPForge\Debug\{ColumnStyle, Panel, PanelView};

final class CachePanel extends Panel
{
    protected const string ICON = 'db';
    protected const string ID = 'cache';
    protected const string TITLE = 'Cache';

    public function present(array $data): PanelView
    {
        $operations = is_array($data['operations'] ?? null) ? $data['operations'] : [];
        
        $view = PanelView::create()
            ->summary(count($operations) === 1 ? ' operation' : ' operations', count($operations))
            ->toolbar('Cache', count($operations))
            ->active($operations !== []);

        return $operations === []
            ? $view->emptyState('No cache operations', 'The cache was observed, but nothing happened.')
            : $view->table(
                ['Operation', 'Key', 'Result'],
                $operations,
                collapsible: true,
                styles: [1 => ColumnStyle::IDENTIFIER],
            );
    }
}
```

Call `record()` from the application service that already knows about the operation. `capture()` returns `null` when
there is nothing to report, and an array otherwise: an empty array is an observed empty request, not absence. The
host encodes that array strictly, so omit secrets and keep values JSON-encodable.

## Register it

Merge these fragments into an application whose debugger is already enabled. The collector's `id()` and the panel's
`ID` must match: that is how the host pairs a capture with its panel.

```php
// Yii2: inside the YII_ENV_DEV guard, preserving the existing module settings.
$collector = new CacheCollector();

$config['modules']['debug']['collectors'][] = $collector;
$config['modules']['debug']['panels'][] = new CachePanel();
```

```php
// Yii3: return the extended registry from the application's development DI factory.
$collector = new CacheCollector();

$registry = $registry
    ->withCollector($collector)
    ->withPanel(new CachePanel());
```

Both hosts derive the IDs and wrap the portable objects internally. No catalog entry, icon enum, storage dispatch
entry, or change to an official package is needed. Inject the same `$collector` into the application service that
calls `record()`.

A runnable version of this example, capturing through PSR-3 instead of a direct call, lives in
[tests/Support](tests/Support); `python3 tools/check-consumer.py` installs it as an independent Composer package and
replays a stored capture with no debugger host present.

## Presentation vocabulary

Five types are published: `CollectorInterface`, `Panel`, `PanelView`, `Tone`, and `ColumnStyle`. Everything a panel can
display is a `PanelView` method, so there is no value class to import and no shape to build by hand.

```php
use PHPForge\Debug\{ColumnStyle, PanelView, Tone};

PanelView::create()
    ->summary(' props', 2)
    ->toolbar('Props', 2)
    ->heading('Props', section: true)
    ->overview(['Component' => 'Site', 'State' => PanelView::badge('shared', Tone::INFO)])
    ->paragraph('Rendered by ', PanelView::code('Inertia::render()'))
    ->callout(Tone::WARNING, 'Runtime inspection is unavailable.')
    ->table(['Prop', 'Value'], [['auth', PanelView::value(['id' => 1])]], styles: [0 => ColumnStyle::IDENTIFIER])
    ->group('Component', PanelView::create()->paragraph('Nested content'))
    ->emptyState('No operations', 'The cache was observed, but nothing happened.')
    ->disclosure('Raw payload', $json)
    ->active(true);
```

Plain scalars and `null` become text. `PanelView::text()`, `::strong()`, `::code()`, `::preview()`, `::badge()`, and
`::value()` produce validated inline values accepted wherever a scalar is accepted. Every method validates its
arguments and rejects invalid input with an explicit `InvalidArgumentException`. The host reads the finished
description through `summaryMetrics()`, `toolbarMetrics()`, `blocks()`, and `isActive()`.

## Verification

[docs/testing.md](docs/testing.md) lists the Composer scripts and the two isolated-consumer checks.

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/php-forge/debug/actions/workflows/static.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/php-forge/debug.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/php-forge/debug)
[![Total Downloads](https://img.shields.io/packagist/dt/php-forge/debug.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/php-forge/debug)

## Code quality

[![Codecov](https://img.shields.io/codecov/c/github/php-forge/debug.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/gh/php-forge/debug)
[![Quality](https://img.shields.io/github/actions/workflow/status/php-forge/debug/quality.yml?style=for-the-badge&label=Quality&logo=github)](https://github.com/php-forge/debug/actions/workflows/quality.yml)
[![StyleCI](https://img.shields.io/badge/StyleCI-Passed-44CC11.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.styleci.io/repos/php-forge/debug?branch=main)

## Social networks

[![Follow on X](https://img.shields.io/badge/-Follow%20on%20X-1DA1F2.svg?style=for-the-badge&logo=x&logoColor=white&labelColor=000000)](https://x.com/Terabytesoftw)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
