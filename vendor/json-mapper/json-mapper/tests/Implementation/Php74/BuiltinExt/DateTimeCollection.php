<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php74\BuiltinExt;

class DateTimeCollection
{
    /** @var DateTime[] */
    public array $items = [];

    public function __construct(DateTime ...$items)
    {
        $this->items = $items;
    }
}
