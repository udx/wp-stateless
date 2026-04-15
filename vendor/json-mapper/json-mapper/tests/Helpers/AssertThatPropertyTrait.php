<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Helpers;

use JsonMapper\ValueObjects\Property;

trait AssertThatPropertyTrait
{
    public function assertThatProperty(Property $property): PropertyAssertionChain
    {
        return new PropertyAssertionChain($property);
    }
}
