<?php

declare(strict_types=1);

namespace JsonMapper\Exception;

class ClassFactoryException extends \Exception
{
    public static function forDuplicateClassname(string $className): self
    {
        return new self("A factory for $className has already been registered");
    }

    public static function forMissingClassname(string $className): self
    {
        return new self("A factory for $className has not been registered");
    }
}
