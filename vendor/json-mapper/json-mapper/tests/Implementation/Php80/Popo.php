<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php80;

class Popo
{
    public string $name;

    public array $friends;

    public mixed $mixedParam;

    public float | int $amount;

    public string | int | float | array $complexUnionWithArray;
}
