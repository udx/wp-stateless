<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

abstract class AbstractShape implements IShape
{
    abstract public function getCircumference(): float;

    public function getType(): string
    {
        return __CLASS__;
    }
}
