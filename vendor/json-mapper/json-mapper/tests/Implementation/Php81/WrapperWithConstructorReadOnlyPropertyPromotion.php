<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WrapperWithConstructorReadOnlyPropertyPromotion
{
    public function __construct(
        public readonly WithConstructorReadOnlyPropertyPromotion $value,
    ) {
    }
}
