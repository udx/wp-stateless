<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81\Foo;

class FooItem
{
    /**
     * @param BarItem[] $orders
     */
    public function __construct(public readonly string $name, public readonly array $orders = [],)
    {
    }
}
