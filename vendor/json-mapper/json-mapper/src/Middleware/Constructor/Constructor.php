<?php

declare(strict_types=1);

namespace JsonMapper\Middleware\Constructor;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Helpers\ScalarCaster;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\AbstractMiddleware;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;

class Constructor extends AbstractMiddleware
{
    /** @var FactoryRegistry */
    private $factoryRegistry;

    public function __construct(FactoryRegistry $factoryRegistry)
    {
        $this->factoryRegistry = $factoryRegistry;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        if ($this->factoryRegistry->hasFactory($object->getName())) {
            return;
        }

        $reflectedConstructor = $object->getReflectedObject()->getConstructor();
        if (\is_null($reflectedConstructor) || $reflectedConstructor->getNumberOfParameters() === 0) {
            return;
        }

        $this->factoryRegistry->addFactory(
            $object->getName(),
            new DefaultFactory(
                $object->getName(),
                $reflectedConstructor,
                $mapper,
                new ScalarCaster(), // @TODO Copy current caster ??
                $this->factoryRegistry
            )
        );
    }
}
