<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WithConstructorReadOnlyPropertyCollection
{
    /**
     * @param WithConstructorReadOnlyPropertySimple[] $simples
     */
    public function __construct(public readonly array $simples)
    {
    }
}
