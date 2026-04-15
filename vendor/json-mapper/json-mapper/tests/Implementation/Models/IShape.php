<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

interface IShape
{
    public function getType(): string;
    public function getCircumference(): float;
}
