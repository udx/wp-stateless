<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\AbstractMiddleware;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class IsCalledMiddleware extends AbstractMiddleware
{
    /** @var bool */
    private $called = false;

    public function isCalled(): bool
    {
        return $this->called;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $this->called = true;
    }
}
