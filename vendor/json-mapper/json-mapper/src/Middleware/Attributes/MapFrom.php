<?php

declare(strict_types=1);

namespace JsonMapper\Middleware\Attributes;

use Attribute;

#[Attribute]
class MapFrom
{
    /** @var string */
    public $source;

    public function __construct(string $source)
    {
        $this->source = $source;
    }
}
