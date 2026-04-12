<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class IsCalledHandler
{
    /** @var bool */
    private $called = false;

    public function __invoke(): void
    {
        $this->called = true;
    }

    public function isCalled(): bool
    {
        return $this->called;
    }
}
