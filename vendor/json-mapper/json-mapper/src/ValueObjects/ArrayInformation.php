<?php

declare(strict_types=1);

namespace JsonMapper\ValueObjects;

final class ArrayInformation implements \JsonSerializable
{
    /** @var bool */
    private $isArray;
    /** @var int */
    private $dimensions;

    private function __construct(bool $isArray, int $dimensions)
    {
        $this->isArray = $isArray;
        $this->dimensions = $dimensions;
    }

    public static function notAnArray(): self
    {
        return new self(false, 0);
    }

    public static function singleDimension(): self
    {
        return new self(true, 1);
    }

    public static function multiDimension(int $dimension): self
    {
        return new self(true, $dimension);
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function getDimensions(): int
    {
        return $this->dimensions;
    }

    public function isMultiDimensionalArray(): bool
    {
        return $this->isArray && $this->dimensions > 1;
    }

    public function jsonSerialize(): array
    {
        return [
            'isArray' => $this->isArray,
            'dimensions' => $this->dimensions
        ];
    }

    public function equals(self $other): bool
    {
        return $this->isArray === $other->isArray
            && $this->dimensions === $other->dimensions;
    }
}
