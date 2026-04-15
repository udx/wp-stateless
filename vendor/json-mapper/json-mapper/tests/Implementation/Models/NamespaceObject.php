<?php

namespace JsonMapper\Tests\Implementation\Models;

use JsonMapper\Tests\Implementation\Models\Sub\AnotherValueHolder;

class NamespaceObject
{
    /** @var ValueHolder */
    public $valueHolder;
    /** @var AnotherValueHolder */
    public $anotherValueHolder;
}
