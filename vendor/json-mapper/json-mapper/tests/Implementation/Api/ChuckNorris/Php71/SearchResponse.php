<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation\Api\ChuckNorris\Php71;

class SearchResponse
{
    /** @var int */
    public $total;
    /** @var Joke[] */
    public $result;
}
