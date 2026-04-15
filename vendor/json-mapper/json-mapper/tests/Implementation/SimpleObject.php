<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

class SimpleObject
{
    /** @var string */
    private $name;

    public function __construct(string $name = '')
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
