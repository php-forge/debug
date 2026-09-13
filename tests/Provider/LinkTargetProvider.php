<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Provider;

use PHPForge\Debug\Tests\PanelViewTest;

/**
 * Provides link targets whose acceptance or rejection by {@see PanelViewTest} defines the safe-scheme contract.
 */
final class LinkTargetProvider
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function accepted(): iterable
    {
        yield 'absolute path' => ['/debug/php-info'];
        yield 'colon inside a fragment' => ['#a:b'];
        yield 'colon inside a query' => ['?at=a:b'];
        yield 'colon opening the target' => [':relative'];
        yield 'fragment' => ['#queries'];
        yield 'http' => ['http://example.test/'];
        yield 'https with uppercase scheme' => ['HTTPS://example.test/'];
        yield 'mailto' => ['mailto:dev@example.test'];
        yield 'protocol relative' => ['//cdn.example.test/a.css'];
        yield 'query only' => ['?panel=db&tag=1'];
        yield 'relative path with a colon after the first segment' => ['view/a:b'];
        yield 'relative path' => ['view?panel=db'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function normalized(): iterable
    {
        yield 'carriage return inside the scheme' => ["java\rscript:alert(1)"];
        yield 'leading newline' => ["\njavascript:alert(1)"];
        yield 'leading null byte' => ["\0javascript:alert(1)"];
        yield 'leading space' => [' javascript:alert(1)'];
        yield 'leading tab' => ["\tjavascript:alert(1)"];
        yield 'newline inside the scheme' => ["java\nscript:alert(1)"];
        yield 'trailing space' => ['https://example.test/ '];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function rejected(): iterable
    {
        yield 'about' => ['about:blank', 'about'];
        yield 'colon-led digits a browser resolves relative' => ['1:30-report', '1'];
        yield 'data' => ['data:text/html;base64,PHN2Zz4=', 'data'];
        yield 'file' => ['file:///etc/passwd', 'file'];
        yield 'javascript with mixed case' => ['JavaScript:alert(1)', 'JavaScript'];
        yield 'javascript' => ['javascript:alert(1)', 'javascript'];
        yield 'vbscript' => ['vbscript:msgbox(1)', 'vbscript'];
    }
}
