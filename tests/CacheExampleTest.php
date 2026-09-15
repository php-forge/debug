<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests;

use InvalidArgumentException;
use PHPForge\Debug\PanelView;
use PHPForge\Debug\Tests\Provider\CacheCaptureProvider;
use PHPForge\Debug\Tests\Support\{Cache, CacheCollector, CachePanel};
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the application-owned cache example built on {@see CachePanel} and {@see CacheCollector}.
 *
 * {@see CacheCaptureProvider} for test case data providers.
 */
final class CacheExampleTest extends TestCase
{
    public function testCountsRepeatedRealOperations(): void
    {
        $panel = new CachePanel();
        $collector = new CacheCollector($panel->id(), new \Psr\Log\NullLogger());
        $cache = new Cache($collector);

        $collector->startup();

        $cache->get('repeat');
        $cache->get('repeat');
        $cache->set('repeat', 'private');
        $cache->get('repeat');
        $cache->get('repeat');
        $cache->get('repeat');

        $payload = $collector->capture();

        self::assertNotNull(
            $payload,
            'An active collector must report a capture.',
        );

        $collector->shutdown();

        $metrics = $panel->present($payload)->toolbarMetrics();

        self::assertSame(
            '3',
            ($metrics[0] ?? null)?->value,
            'Hits must count every reuse.',
        );
        self::assertSame(
            '2',
            ($metrics[1] ?? null)?->value,
            'Misses must count both lookups.',
        );
    }

    public function testLoggingPreservesTheApplicationRecordAndIgnoresUnrelatedEvents(): void
    {
        $logger = new class extends \Psr\Log\AbstractLogger {
            /**
             * @var list<array{mixed, string|\Stringable, array<array-key, mixed>}>
             */
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [$level, $message, $context];
            }
        };

        $collector = new CacheCollector((new CachePanel())->id(), $logger);

        $context = [
            'exception' => new \RuntimeException('original'),
            'nested' => new \stdClass(),
        ];

        $collector->error('Original {exception}', $context);

        self::assertNull(
            $collector->capture(),
            'No capture exists before startup.',
        );
        $collector->startup();

        $collector->info('Unrelated', ['event' => 'another.event']);

        self::assertSame(
            ['schema' => 1, 'operations' => []],
            $collector->capture(),
            'Unrelated events must not be buffered.',
        );

        $cache = new Cache($collector);

        $cache->set('key', 'private contents');

        $records = $logger->records;

        self::assertCount(
            3,
            $records,
            'Every record must reach the application logger once.',
        );
        self::assertSame(
            ['error', 'Original {exception}', $context],
            $records[0],
            'Forwarded records must stay byte-identical.',
        );
        self::assertSame(
            ['schema' => 1, 'operations' => [['set', 'key', 'stored']]],
            $collector->capture(),
            'Cache events must be buffered while active.',
        );
        self::assertSame(
            ['event' => 'cache.operation', 'operation' => 'set', 'key' => 'key', 'result' => 'stored'],
            $records[2][2],
            'Cached contents must never enter the log context.',
        );

        $collector->shutdown();

        $cache->get('key');

        self::assertCount(
            4,
            $logger->records,
            'Forwarding must continue after shutdown.',
        );

        self::assertNull(
            $collector->capture(),
            'Shutdown must clear the capture.',
        );

        $collector->startup();

        self::assertSame(
            ['schema' => 1, 'operations' => []],
            $collector->capture(),
            'A restarted collector must begin empty.',
        );

        $collector->shutdown();
    }

    public function testRealOperationsAndTwoRequestLifecycles(): void
    {
        $panel = new CachePanel();

        self::assertSame(
            'db',
            $panel->icon(),
            'The example must own its icon identifier.',
        );

        $collector = new CacheCollector($panel->id(), new \Psr\Log\NullLogger());
        $cache = new Cache($collector);

        self::assertNull(
            $collector->capture(),
            'No capture exists before startup.',
        );
        self::assertSame(
            $panel->id(),
            $collector->id(),
            'Collector and panel must share one identifier.',
        );

        $collector->startup();
        $collector->startup();

        self::assertNull(
            $cache->get('example'),
            'An unknown key must miss.',
        );

        $cache->set('example', 'private contents');

        self::assertSame(
            'private contents',
            $cache->get('example'),
            'A stored key must hit.',
        );

        $capture = $collector->capture();

        self::assertSame(
            [
                'schema' => 1,
                'operations' => [
                    ['get', 'example', 'miss'],
                    ['set', 'example', 'stored'],
                    ['get', 'example', 'hit'],
                ],
            ],
            $capture,
            'Repeated startup must not discard buffered operations.',
        );

        $collector->shutdown();
        $collector->shutdown();

        $cache->set('other', 'not captured');

        self::assertNull(
            $collector->capture(),
            'Repeated shutdown must stay idempotent.',
        );
        self::assertEquals(
            PanelView::create()
                ->summary(' hits', 1)
                ->summary(' misses', 1)
                ->toolbar('Hits', 1)
                ->toolbar('Misses', 1)
                ->overview(['Hits' => 1, 'Misses' => 1])
                ->table(
                    [
                        'Operation',
                        'Key',
                        'Result',
                    ],
                    [
                        ['get', 'example', 'miss'],
                        ['set', 'example', 'stored'],
                        ['get', 'example', 'hit'],
                    ],
                    collapsible: true,
                ),
            $panel->present($capture),
            'A stored capture must describe the whole panel.',
        );

        $metrics = $panel->present($capture)->toolbarMetrics();

        self::assertSame(
            '1',
            ($metrics[0] ?? null)?->value,
            'Hits must reach the toolbar as text.',
        );
        self::assertStringNotContainsString(
            'private contents',
            json_encode($capture, JSON_THROW_ON_ERROR),
            'Cached contents must never be persisted.',
        );

        $collector->startup();

        self::assertSame(
            ['schema' => 1, 'operations' => []],
            $collector->capture(),
            'An observed empty cache is still a capture.',
        );
        self::assertTrue(
            $panel->present(['schema' => 1, 'operations' => []])->isActive(),
            'An empty capture must still open the panel.',
        );

        $collector->shutdown();
    }

    public function testThrowInvalidArgumentExceptionForMalformedLoggingContext(): void
    {
        $collector = new CacheCollector((new CachePanel())->id(), new \Psr\Log\NullLogger());

        $collector->startup();

        $collector->debug('Malformed', ['event' => 'cache.operation', 'key' => new \stdClass()]);

        $payload = $collector->capture();

        self::assertNotNull(
            $payload,
            'Logging a malformed context must not throw.',
        );

        $collector->shutdown();

        $this->expectException(InvalidArgumentException::class);

        (new CachePanel())->present($payload);
    }

    public function testThrowInvalidArgumentExceptionForMissingCaptureSchema(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CachePanel())->present([]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProviderExternal(CacheCaptureProvider::class, 'invalidCaptures')]
    public function testThrowInvalidArgumentExceptionForStoredCapture(array $payload): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CachePanel())->present($payload);
    }

    public function testThrowInvalidArgumentExceptionForUnsupportedCacheOperation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Invalid cache operation.',
        );

        (new CachePanel())->present(['schema' => 1, 'operations' => [['delete', 'key', 'hit']]]);
    }

    public function testTwoIndependentPanelsForwardEachRecordOnlyOnce(): void
    {
        $logger = new class extends \Psr\Log\AbstractLogger {
            public int $calls = 0;

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ++$this->calls;
            }
        };

        $first = new CacheCollector('first', $logger);
        $second = new CacheCollector('second', $first);
        $cache = new Cache($second);

        $first->startup();
        $second->startup();

        $cache->get('key');
        $cache->set('key', 'private');
        $cache->get('key');

        self::assertSame(
            3,
            $logger->calls,
            'Chained decorators must forward each record once.',
        );
        self::assertSame(
            $first->capture(),
            $second->capture(),
            'Both collectors must observe the same operations.',
        );
        self::assertNotSame(
            $first->id(),
            $second->id(),
            'Chained collectors must keep distinct identifiers.',
        );

        $first->shutdown();
        $second->shutdown();

        self::assertNull(
            $first->capture(),
            'Shutdown must clear the outer collector.',
        );
        self::assertNull(
            $second->capture(),
            'Shutdown must clear the inner collector.',
        );
    }
}
