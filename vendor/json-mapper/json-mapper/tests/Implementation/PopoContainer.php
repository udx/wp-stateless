<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class PopoContainer
{
    /** @var Popo[] */
    private $items;

    /** @param Popo[] $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /** @return Popo[] */
    public function getItems(): array
    {
        return $this->items;
    }
}
