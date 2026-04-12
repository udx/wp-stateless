<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class FinalCallback implements MiddlewareInterface
{
    /** @var int */
    private static $nestingLevel = 0;

    /** @var callable */
    private $callback;
    /** @var bool */
    private $onlyApplyCallBackOnTopLevel;

    public function __construct(callable $callback, bool $onlyApplyCallBackOnTopLevel = true)
    {
        $this->callback = $callback;
        $this->onlyApplyCallBackOnTopLevel = $onlyApplyCallBackOnTopLevel;
    }

    public function __invoke(callable $handler): callable
    {
        return function (
            \stdClass $json,
            ObjectWrapper $object,
            PropertyMap $map,
            JsonMapperInterface $mapper
        ) use (
            $handler
        ) {
            self::$nestingLevel++;
            try {
                $handler($json, $object, $map, $mapper);
            } finally {
                self::$nestingLevel--;
            }

            if (! $this->onlyApplyCallBackOnTopLevel || self::$nestingLevel === 0) {
                \call_user_func($this->callback, $json, $object, $map, $mapper);
            }
        };
    }
}
