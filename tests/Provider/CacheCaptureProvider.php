<?php

declare(strict_types=1);

namespace PHPForge\Debug\Tests\Provider;

use PHPForge\Debug\Tests\CacheExampleTest;

/**
 * Provides stored cache captures whose rejection is verified by {@see CacheExampleTest}.
 */
final class CacheCaptureProvider
{
    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidCaptures(): iterable
    {
        yield 'boolean operation' => [['schema' => 1, 'operations' => [[true, 'key', 'hit']]]];
        yield 'boolean result' => [['schema' => 1, 'operations' => [['get', 'key', true]]]];
        yield 'extra cell' => [['schema' => 1, 'operations' => [['get', 'key', 'hit', 'extra']]]];
        yield 'get cannot store' => [['schema' => 1, 'operations' => [['get', 'key', 'stored']]]];
        yield 'missing result' => [['schema' => 1, 'operations' => [['get', 'key']]]];
        yield 'non-array operations' => [['schema' => 1, 'operations' => 'invalid']];
        yield 'non-array row' => [['schema' => 1, 'operations' => ['invalid']]];
        yield 'non-list operations' => [['schema' => 1, 'operations' => [1 => ['get', 'key', 'hit']]]];
        yield 'null key' => [['schema' => 1, 'operations' => [['get', null, 'hit']]]];
        yield 'numeric key' => [['schema' => 1, 'operations' => [['get', 1, 'hit']]]];
        yield 'numeric set result' => [['schema' => 1, 'operations' => [['set', 'key', 1]]]];
        yield 'set cannot hit' => [['schema' => 1, 'operations' => [['set', 'key', 'hit']]]];
        yield 'string schema' => [['schema' => '1', 'operations' => []]];
        yield 'unsupported schema' => [['schema' => 2, 'operations' => []]];
    }
}
