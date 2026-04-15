<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WithConstructorPropertyPromotion
{
    public function __construct(
        private string $value,
    ) {
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
