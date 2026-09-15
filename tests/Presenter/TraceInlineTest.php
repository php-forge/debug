<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\Presenter\TraceInline;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the captured frames {@see TraceInline} accepts and rejects.
 */
final class TraceInlineTest extends TestCase
{
    public function testFramesKeepTheirFieldsAndOrder(): void
    {
        self::assertSame(
            [['file' => '/app/x.php', 'line' => 7], ['bare']],
            (new TraceInline([['file' => '/app/x.php', 'line' => 7], ['bare']]))->frames,
            'Fields must travel as captured, with keys normalized to strings.',
        );
        self::assertSame(
            [],
            (new TraceInline([]))->frames,
            'A call site without frames must stay empty.',
        );
    }

    public function testThrowInvalidArgumentExceptionForNonArrayTraceFrame(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::TRACE_FRAME_INVALID->getMessage(),
        );

        new TraceInline(['not a frame']);
    }
}
