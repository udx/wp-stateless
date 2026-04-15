<?php

declare(strict_types=1);

namespace JsonMapper\Exception;

class BuilderException extends \Exception
{
    public static function invalidJsonMapperClassName(string $className): self
    {
        return new self("'$className' (or it parent classes) don't implement the JsonMapperInterface");
    }

    public static function forBuildingWithoutMiddleware(): self
    {
        return new self('Trying to build a JsonMapper instance without middleware');
    }
}
