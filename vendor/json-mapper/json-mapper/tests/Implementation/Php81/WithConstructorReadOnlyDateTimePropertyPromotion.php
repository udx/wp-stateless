<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WithConstructorReadOnlyDateTimePropertyPromotion
{
    public function __construct(
        public readonly \DateTimeImmutable $date
    ) {
    }
}
