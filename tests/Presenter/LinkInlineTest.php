<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Presenter;

use InvalidArgumentException;
use PHPForge\Debug\Exception\PanelViewMessage;
use PHPForge\Debug\Presenter\LinkInline;
use PHPForge\Debug\Tests\Provider\LinkTargetProvider;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the link targets {@see LinkInline} accepts and rejects.
 *
 * {@see LinkTargetProvider} for test case data providers.
 */
final class LinkInlineTest extends TestCase
{
    #[DataProviderExternal(LinkTargetProvider::class, 'accepted')]
    public function testLinkTargetsWithoutAnExecutableSchemeAreAccepted(string $href): void
    {
        self::assertSame(
            $href,
            (new LinkInline('Open', $href, false))->href,
            'An accepted target must travel unmodified.',
        );
    }

    #[DataProviderExternal(LinkTargetProvider::class, 'normalized')]
    public function testThrowInvalidArgumentExceptionForBrowserNormalizedLinkTarget(string $href): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::LINK_TARGET_NORMALIZED->getMessage(),
        );

        new LinkInline('Open', $href, false);
    }

    #[DataProviderExternal(LinkTargetProvider::class, 'rejected')]
    public function testThrowInvalidArgumentExceptionForExecutableLinkTarget(string $href, string $scheme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::LINK_TARGET_SCHEME_INVALID->getMessage($scheme),
        );

        new LinkInline('Open', $href, false);
    }

    public function testThrowInvalidArgumentExceptionForUnparsableLinkTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            PanelViewMessage::LINK_TARGET_UNPARSABLE->getMessage(),
        );

        new LinkInline('Open', 'http://:80', false);
    }
}
