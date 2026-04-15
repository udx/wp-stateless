<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81\Foo;

class FooCollection
{
    /**
     * @param FooItem[] $items
     */
    public function __construct(public readonly array $items = [])
    {
    }
}
