<?php

declare(strict_types=1);

namespace JsonMapper;

interface JsonMapperInterface
{
    public function setPropertyMapper(callable $propertyMapper): JsonMapperInterface;

    public function push(callable $middleware, string $name = ''): self;

    public function pop(): self;

    public function unshift(callable $middleware, string $name = ''): self;

    public function shift(): self;

    public function remove(callable $remove): self;

    public function removeByName(string $remove): self;

    /**
     * @template T of object
     * @psalm-param T $object
     * @return T
     */
    public function mapObject(\stdClass $json, $object);

    /**
     * @template T of object
     * @psalm-param class-string<T> $class
     * @return T
     */
    public function mapToClass(\stdClass $json, string $class);

    /**
     * @template T of object
     * @psalm-param T $object
     * @return array<int, T>
     */
    public function mapArray(array $json, $object): array;

    /**
     * @template T of object
     * @psalm-param class-string<T> $class
     * @return array<int, T>
     */
    public function mapToClassArray(array $json, string $class): array;

    /**
     * @template T of object
     * @psalm-param T $object
     * @return T
     */
    public function mapObjectFromString(string $json, $object);

    /**
     * @template T of object
     * @psalm-param class-string<T> $class
     * @return T
     */
    public function mapToClassFromString(string $json, string $class);

    /**
     * @template T of object
     * @psalm-param T $object
     * @return array<int, T>
     */
    public function mapArrayFromString(string $json, $object): array;

    /**
     * @template T of object
     * @psalm-param class-string<T> $class
     * @return array<int, T>
     */
    public function mapToClassArrayFromString(string $json, string $class): array;
}
