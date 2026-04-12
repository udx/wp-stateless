<?php

declare(strict_types=1);

namespace JsonMapper\ValueObjects;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;

class Property implements \JsonSerializable
{
    /** @var string */
    private $name;
    /** @var PropertyType[] */
    private $propertyTypes;
    /** @var Visibility */
    private $visibility;
    /** @var bool */
    private $isNullable;

    public function __construct(
        string $name,
        Visibility $visibility,
        bool $isNullable,
        PropertyType ...$types
    ) {
        $this->name = $name;
        $this->visibility = $visibility;
        $this->isNullable = $isNullable;
        $this->propertyTypes = $types;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return PropertyType[] */
    public function getPropertyTypes(): array
    {
        return $this->propertyTypes;
    }

    public function getVisibility(): Visibility
    {
        return $this->visibility;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isUnion(): bool
    {
        return \count($this->propertyTypes) > 1;
    }

    public function asBuilder(): PropertyBuilder
    {
        return PropertyBuilder::new()
            ->setName($this->name)
            ->setTypes(...$this->propertyTypes)
            ->setIsNullable($this->isNullable())
            ->setVisibility($this->visibility);
    }

    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'types' => $this->propertyTypes,
            'visibility' => $this->visibility,
            'isNullable' => $this->isNullable,
        ];
    }
}
