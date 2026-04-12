<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Api\ChuckNorris\Php71;

class Joke
{
    /** @var string[] */
    public $categories;
    /** @var \DateTimeImmutable */
    public $created_at;
    /** @var string */
    public $icon_url;
    /** @var string */
    public $id;
    /** @var \DateTimeImmutable */
    public $updated_at;
    /** @var string */
    public $url;
    /** @var string */
    public $value;
}
