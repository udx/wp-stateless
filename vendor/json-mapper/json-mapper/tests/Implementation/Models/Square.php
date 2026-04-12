<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

class Square extends AbstractShape
{
    /** @var int */
    public $width;
    /** @var int */
    public $length;

    public function __construct(?int $width = null, ?int $length = null)
    {
        $this->width = $width;
        $this->length = $length;
    }

    public function getCircumference(): float
    {
        return ($this->length * 2) + ($this->width * 2);
    }
}
