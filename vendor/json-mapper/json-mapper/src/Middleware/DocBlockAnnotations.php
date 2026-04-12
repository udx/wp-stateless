<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\Enums\Visibility;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\LazyAnnotationMap;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use Psr\SimpleCache\CacheInterface;

class DocBlockAnnotations extends AbstractMiddleware
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $propertyMap->merge($this->fetchPropertyMapForObject($object));
    }

    private function fetchPropertyMapForObject(ObjectWrapper $object): PropertyMap
    {
        $cacheKey = \sprintf(
            '%sCache%s',
            str_replace(['{', '}', '(', ')', '/', '\\', '@', ':' ], '', __CLASS__),
            str_replace(['{', '}', '(', ')', '/', '\\', '@', ':' ], '', $object->getName())
        );
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $intermediatePropertyMap = new PropertyMap();
        foreach ($this->getObjectPropertiesIncludingParents($object) as $property) {
            $docBlock = $property->getDocComment();
            if ($docBlock === false) {
                continue;
            }

            $annotations = new LazyAnnotationMap($docBlock);
            if (! $annotations->hasVar()) {
                continue;
            }

            $property = $annotations->tagToPropertyBuilder('var')
                ->setName($property->getName())
                ->setVisibility(Visibility::fromReflectionProperty($property))
                ->build();

            $intermediatePropertyMap->addProperty($property);
        }

        $this->cache->set($cacheKey, $intermediatePropertyMap);

        return $intermediatePropertyMap;
    }

    /** @return \ReflectionProperty[] */
    private function getObjectPropertiesIncludingParents(ObjectWrapper $object): array
    {
        $properties = [];
        $reflectionClass = $object->getReflectedObject();
        do {
            $properties[] = $reflectionClass->getProperties();
        } while ($reflectionClass = $reflectionClass->getParentClass());

        return array_merge(...$properties);
    }
}
