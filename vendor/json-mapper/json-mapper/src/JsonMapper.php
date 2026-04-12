<?php

declare(strict_types=1);

namespace JsonMapper;

use JsonMapper\Dto\NamedMiddleware;
use JsonMapper\Exception\TypeError;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class JsonMapper implements JsonMapperInterface
{
    /** @var callable|null */
    private $propertyMapper;
    /** @var NamedMiddleware[] */
    private $stack = [];
    /** @var callable|null */
    private $cached;

    public function __construct(?callable $propertyMapper = null)
    {
        $this->propertyMapper = $propertyMapper;
    }

    public function setPropertyMapper(callable $propertyMapper): JsonMapperInterface
    {
        $this->propertyMapper = $propertyMapper;
        $this->cached = null;

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
        \array_pop($this->stack);
        $this->cached = null;

        return $this;
    }

    public function unshift(callable $middleware, string $name = ''): JsonMapperInterface
    {
        \array_unshift($this->stack, new NamedMiddleware($middleware, $name));
        $this->cached = null;

        return $this;
    }

    public function shift(): JsonMapperInterface
    {
        \array_shift($this->stack);
        $this->cached = null;

        return $this;
    }

    public function remove(callable $remove): JsonMapperInterface
    {
        $this->stack = \array_values(\array_filter(
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
        $this->stack = \array_values(\array_filter(
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
            throw TypeError::forArgument(__METHOD__, 'class-string', $class, 2, '$class');
        }

        $propertyMap = new PropertyMap();

        $handler = $this->resolve();
        $wrapper = new ObjectWrapper(null, $class);
        $handler($json, $wrapper, $propertyMap, $this);

        return $wrapper->getObject();
    }

    public function mapObject(\stdClass $json, $object)
    {
        if (! \is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $propertyMap = new PropertyMap();

        $handler = $this->resolve();
        $handler($json, new ObjectWrapper($object), $propertyMap, $this);

        return $object;
    }

    public function mapArray(array $json, $object): array
    {
        if (! \is_object($object)) {
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

    public function mapObjectFromString(string $json, $object)
    {
        if (! \is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $data = $this->decodeJsonString($json);

        if (! $data instanceof \stdClass) {
            throw new \RuntimeException('Provided string is not a json encoded object');
        }

        $this->mapObject($data, $object);

        return $object;
    }

    public function mapArrayFromString(string $json, $object): array
    {
        if (! \is_object($object)) {
            throw TypeError::forArgument(__METHOD__, 'object', $object, 2, '$object');
        }

        $data = $this->decodeJsonString($json);

        if (! \is_array($data)) {
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
        return \json_decode($json, false, 512, JSON_THROW_ON_ERROR);
    }

    private function resolve(): callable
    {
        if (!$this->cached) {
            $prev = $this->propertyMapper;
            if (\is_null($prev)) {
                throw new \RuntimeException('Property mapper has not been defined');
            }
            foreach (\array_reverse($this->stack) as $namedMiddleware) {
                $prev = $namedMiddleware->getMiddleware()($prev);
            }

            $this->cached = $prev;
        }

        return $this->cached;
    }
}
