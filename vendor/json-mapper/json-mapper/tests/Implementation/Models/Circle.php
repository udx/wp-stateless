<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

class Circle extends AbstractShape
{
    public $radius;

    public function getCircumference(): float
    {
        return 2 * $this->radius * M_PI;
    }
}
