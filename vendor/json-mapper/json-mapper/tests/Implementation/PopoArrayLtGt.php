<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class PopoArrayLtGt
{
    /** @var array<int, Popo> */
    private $items;

    /** @param array<int, Popo> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return array<int, Popo> */
    public function getItems(): array
    {
        return $this->items;
    }
}
