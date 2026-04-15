<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81\Foo;

class BarItem
{
    public function __construct(
        public readonly ?int $id,
    ) {
    }
}
