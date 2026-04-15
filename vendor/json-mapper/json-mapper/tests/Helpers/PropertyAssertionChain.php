<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Helpers;

use JsonMapper\Enums\Visibility;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\Assert;

class PropertyAssertionChain
{
    /** @var Property */
    private $property;

    public function __construct(Property $property)
    {
        $this->property = $property;
    }

    public function hasName(string $name): PropertyAssertionChain
    {
        Assert::assertSame($name, $this->property->getName());

        return $this;
    }

    public function hasType(string $type, ArrayInformation $arrayInformation): PropertyAssertionChain
    {
        $matches = array_filter(
            $this->property->getPropertyTypes(),
            static function ($p) use ($type, $arrayInformation) {
                return $p->getType() === $type && $p->getArrayInformation()->equals($arrayInformation);
            }
        );

        Assert::assertGreaterThanOrEqual(1, count($matches));

        return $this;
    }

    public function onlyHasType(string $type, ArrayInformation $isArray): PropertyAssertionChain
    {
        Assert::assertEquals([new PropertyType($type, $isArray)], $this->property->getPropertyTypes());

        return $this;
    }

    public function hasVisibility(Visibility $visibility): PropertyAssertionChain
    {
        Assert::assertTrue($this->property->getVisibility()->equals($visibility));

        return $this;
    }

    public function isNullable(): PropertyAssertionChain
    {
        Assert::assertTrue($this->property->isNullable());

        return $this;
    }

    public function isNotNullable(): PropertyAssertionChain
    {
        Assert::assertFalse($this->property->isNullable());

        return $this;
    }
}
