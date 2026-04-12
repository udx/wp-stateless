<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class UserWithConstructor
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
    ) {
    }
}
