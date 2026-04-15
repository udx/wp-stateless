<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Namespaces\One;

use JsonMapper\Tests\Integration\Namespaces\Two\Sub;

class Example
{
    /** @var Sub\Item[] */
    public $items;
}
