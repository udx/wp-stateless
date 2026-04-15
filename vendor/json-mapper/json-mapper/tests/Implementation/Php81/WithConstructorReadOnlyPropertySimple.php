<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php81;

class WithConstructorReadOnlyPropertySimple
{
    public function __construct(public readonly ?int $status = null)
    {
    }
}
