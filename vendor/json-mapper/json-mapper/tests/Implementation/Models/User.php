<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Models;

class User
{
    /** @var int */
    private $id;
    /** @var string */
    private $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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
