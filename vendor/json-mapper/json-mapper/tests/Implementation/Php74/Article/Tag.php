<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php74\Article;

class Tag
{
    private string $configuration = 'Doesnt work';

    public int $id;
    public string $name;

    public function __construct()
    {
        $this->configuration = "Works?";
    }

    public function getConfiguration(): string
    {
        return $this->configuration;
    }
}
