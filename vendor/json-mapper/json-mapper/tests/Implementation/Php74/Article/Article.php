<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Php74\Article;

use JsonMapper\Tests\Implementation\Article\TestTag;

class Article
{
    public int $id;
    public string $title;

    /** @var Tag[] */
    public array $tags;
}
