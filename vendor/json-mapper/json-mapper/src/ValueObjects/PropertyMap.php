<?php

declare(strict_types=1);

namespace JsonMapper\ValueObjects;

class PropertyMap implements \IteratorAggregate, \JsonSerializable
{
    /** @var Property[] */
    private $map = [];
    /** @var \ArrayIterator|null */
    private $iterator = null;

    public function addProperty(Property $property): void
    {
        $this->map[$property->getName()] = $property;
        $this->iterator = null;
    }

    public function hasProperty(string $name): bool
    {
        return \array_key_exists($name, $this->map);
    }

    public function getProperty(string $key): Property
    {
        if (! $this->hasProperty($key)) {
            throw new \Exception("There is no property named $key");
        }

        return $this->map[$key];
    }

    public function merge(self $other): void
    {
        /** @var Property $property */
        foreach ($other as $property) {
            if (! $this->hasProperty($property->getName())) {
                $this->addProperty($property);
                continue;
            }

            if ($property == $this->getProperty($property->getName())) {
                continue;
            }

            $current = $this->getProperty($property->getName());
            $builder = $current->asBuilder();

            $builder->setIsNullable($current->isNullable() || $property->isNullable());
            foreach ($property->getPropertyTypes() as $propertyType) {
                $builder->addType($propertyType->getType(), $propertyType->getArrayInformation());
            }

            $this->addProperty($builder->build());
        }
        $this->iterator = null;
    }

    public function getIterator(): \ArrayIterator
    {
        if (\is_null($this->iterator)) {
            $this->iterator = new \ArrayIterator($this->map);
        }

        return $this->iterator;
    }

    public function jsonSerialize(): array
    {
        return [
            'properties' => $this->map,
        ];
    }

    public function toString(): string
    {
        return (string) \json_encode($this);
    }
}
