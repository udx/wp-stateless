<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php80;

class PopoWithConstructAndDocblock
{
    public function __construct(
        /** @var Popo[] $popo */
        public array $popo
    ) {
        // Intentionally left empty.
    }
}
