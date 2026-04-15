<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Implementation;

use JsonException;
use JsonMapper\Dto\NamedMiddleware;
use JsonMapper\Exception\TypeError;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class JsonMapper implements JsonMapperInterface
{
    /** @var callable */
    public $handler;
    /** @var NamedMiddleware[] */
    public $stack = [];
    /** @var callable|null */
    public $cached;

    public function setPropertyMapper(callable $handler): JsonMapperInterface
    {
        $this->handler = $handler;

        return $this;
    }

    public function push(callable $middleware, string $name = ''): JsonMapperInterface
    {
        $this->stack[] = new NamedMiddleware($middleware, $name);
        $this->cached = null;

        return $this;
    }

    public function pop(): JsonMapperInterface
    {
        array_pop($this->stack);
        $this->cached = null;

        return $this;
    }

    public function unshift(callable $middleware, string $name = ''): JsonMapperInterface
    {
        array_unshift($this->stack, new NamedMiddleware($middleware, $name));
        $this->cached = null;

        return $this;
    }

    public function shift(): JsonMapperInterface
    {
        array_shift($this->stack);
        $this->cached = null;

        return $this;
    }

    public function remove(callable $remove): JsonMapperInterface
    {
        $this->stack = array_values(array_filter(
            $this->stack,
            static function (NamedMiddleware $namedMiddleware) use ($remove) {
                return $namedMiddleware->getMiddleware() !== $remove;
            }
        ));
        $this->cached = null;

        return $this;
    }

    public function removeByName(string $remove): JsonMapperInterface
    {
        $this->stack = array_values(array_filter(
            $this->stack,
            static function (NamedMiddleware $namedMiddleware) use ($remove) {
                return $namedMiddleware->getName() !== $remove;
            }
        ));
        $this->cached = null;

        return $this;
    }

    public function mapToClass(\stdClass $json, string $class)
    {
        if (! \class_exists($class)) {
            throw new \Exception(); // @todo proper exception message
        }

        $propertyMap = new PropertyMap();

        $handler = $this->resolve();
        $placeholder = new \stdClass(); // @todo remove placeholder
        $wrapper = new ObjectWrapper($placeholder, $class);
        $handler($json, $wrapper, $propertyMap, $this);

        return $wrapper->getObject();
    }

    /** @param object $object */
    public function mapObject(\stdClass $json, $object): void
    {
        if (!is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $propertyMap = new PropertyMap();

        $handler = $this->resolve();
        $handler($json, new ObjectWrapper($object), $propertyMap, $this);
    }

    /** @param object $object */
    public function mapArray(array $json, $object): array
    {
        if (!is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $results = [];
        foreach ($json as $key => $value) {
            $results[$key] = clone $object;
            $this->mapObject($value, $results[$key]);
        }

        return $results;
    }

    public function mapToClassArray(array $json, string $class): array
    {
        if (! \class_exists($class)) {
            throw TypeError::forArgument(__METHOD__, 'class-string', $class, 2, '$class');
        }

        return array_map(
            function (\stdClass $value) use ($class) {
                return $this->mapToClass($value, $class);
            },
            $json
        );
    }

    public function mapToClassFromString(string $json, string $class)
    {
        if (! \class_exists($class)) {
            throw TypeError::forArgument(__METHOD__, 'class-string', $class, 2, '$class');
        }

        $data = $this->decodeJsonString($json);
        if (! $data instanceof \stdClass) {
            throw new \RuntimeException('Provided string is not a json encoded object');
        }

        return $this->mapToClass($data, $class);
    }

    /** @param object $object */
    public function mapObjectFromString(string $json, $object): void
    {
        if (!is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $data = $this->decodeJsonString($json);

        if (! $data instanceof \stdClass) {
            throw new \RuntimeException('Provided string is not a json encoded object');
        }

        $this->mapObject($data, $object);
    }

    /** @param object $object */
    public function mapArrayFromString(string $json, $object): array
    {
        if (!is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $data = $this->decodeJsonString($json);

        if (! is_array($data)) {
            throw new \RuntimeException('Provided string is not a json encoded array');
        }

        $results = [];
        foreach ($data as $key => $value) {
            $results[$key] = clone $object;
            $this->mapObject($value, $results[$key]);
        }

        return $results;
    }

    public function mapToClassArrayFromString(string $json, string $class): array
    {
        if (! \class_exists($class)) {
            throw TypeError::forArgument(__METHOD__, 'class-string', $class, 2, '$class');
        }

        $data = $this->decodeJsonString($json);
        if (! \is_array($data)) {
            throw new \RuntimeException('Provided string is not a json encoded array');
        }

        return $this->mapToClassArray($data, $class);
    }

    /** @return \stdClass|\stdClass[] */
    private function decodeJsonString(string $json)
    {
        if (PHP_VERSION_ID >= 70300) {
            $data = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = json_decode($json, false);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new JsonException(json_last_error_msg(), json_last_error());
            }
        }

        return $data;
    }

    private function resolve(): callable
    {
        if (!$this->cached) {
            $prev = $this->handler;
            foreach (array_reverse($this->stack) as $namedMiddleware) {
                $prev = $namedMiddleware->getMiddleware()($prev);
            }

            $this->cached = $prev;
        }

        return $this->cached;
    }
}
