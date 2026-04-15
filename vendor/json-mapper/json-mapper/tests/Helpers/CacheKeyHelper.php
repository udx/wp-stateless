<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Helpers;

class CacheKeyHelper
{
    public static function sanatize(string $input): string
    {
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':' ], '', $input);
    }
}
