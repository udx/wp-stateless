<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class PopoList
{
    /** @var list<Popo> */
    private $items;

    /** @param list<Popo> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return list<Popo> */
    public function getItems(): array
    {
        return $this->items;
    }
}
