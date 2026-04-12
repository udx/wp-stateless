<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

interface MiddlewareInterface
{
    public function __invoke(callable $handler): callable;
}
