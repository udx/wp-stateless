<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WithConstructorReadOnlyPropertyPromotion
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}
