<?php

declare(strict_types=1);

namespace JsonMapper\Builders;

use JsonMapper\Enums\Visibility;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyType;

class PropertyBuilder
{
    /** @var string */
    private $name;
    /** @var bool */
    private $isNullable;
    /** @var Visibility */
    private $visibility;
    /** @var PropertyType[] */
    private $types = [];

    private function __construct()
    {
    }

    public static function new(): self
    {
        return new self();
    }

    public function build(): Property
    {
        return new Property(
            $this->name,
            $this->visibility,
            $this->isNullable,
            ...$this->types
        );
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setTypes(PropertyType ...$types): self
    {
        $this->types = $types;
        return $this;
    }

    public function addType(string $type, ArrayInformation $arrayInformation): self
    {
        $this->types[] = new PropertyType($type, $arrayInformation);
        return $this;
    }

    public function addTypes(PropertyType ...$types): self
    {
        $this->types = array_merge($this->types, $types);
        return $this;
    }

    public function setIsNullable(bool $isNullable): self
    {
        $this->isNullable = $isNullable;
        return $this;
    }

    public function setVisibility(Visibility $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function hasAnyType(): bool
    {
        return count($this->types) !== 0;
    }

    public function getTypes(): array
    {
        return $this->types;
    }
}
