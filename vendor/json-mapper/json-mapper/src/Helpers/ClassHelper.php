<?php

declare(strict_types=1);

namespace JsonMapper\Helpers;

use JsonMapper\Enums\ScalarType;
use ReflectionClass;

class ClassHelper
{
    public static function isBuiltin(string $type): bool
    {
        if ($type === 'mixed' || ScalarType::isValid($type) || ! \class_exists($type)) {
            return false;
        }

        $reflection = new ReflectionClass($type);
        return $reflection->isInternal();
    }

    public static function isCustom(string $type): bool
    {
        if ($type === 'mixed' || ScalarType::isValid($type) || ! \class_exists($type)) {
            return false;
        }

        $reflection = new ReflectionClass($type);
        return !$reflection->isInternal();
    }
}
