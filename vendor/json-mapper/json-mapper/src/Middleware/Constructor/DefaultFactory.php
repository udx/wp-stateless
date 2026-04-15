<?php

declare(strict_types=1);

namespace JsonMapper\Middleware\Constructor;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;
use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\ValueFactory;
use JsonMapper\Helpers\IScalarCaster;
use JsonMapper\Helpers\UseStatementHelper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\LazyAnnotationMap;
use JsonMapper\ValueObjects\PropertyMap;
use ReflectionMethod;

class DefaultFactory
{
    /** @var string */
    private $objectName;
    /** @var JsonMapperInterface */
    private $mapper;
    /** @var PropertyMap */
    private $propertyMap;
    /** @var ValueFactory */
    private $valueFactory;
    /** @var array<int, string> */
    private $parameterMap = [];
    /** @var array<string, mixed> */
    private $parameterDefaults = [];

    public function __construct(
        string $objectName,
        ReflectionMethod $reflectedConstructor,
        JsonMapperInterface $mapper,
        IScalarCaster $scalarCaster,
        FactoryRegistry $classFactoryRegistry,
        ?FactoryRegistry $nonInstantiableTypeResolver = null
    ) {
        $reflectedClass = $reflectedConstructor->getDeclaringClass();
        $this->objectName = $objectName;
        $this->mapper = $mapper;
        if ($nonInstantiableTypeResolver === null) {
            $nonInstantiableTypeResolver = new FactoryRegistry();
        }
        $this->propertyMap = new PropertyMap();
        $this->valueFactory = new ValueFactory($scalarCaster, $classFactoryRegistry, $nonInstantiableTypeResolver);

        $imports = UseStatementHelper::getImports($reflectedConstructor->getDeclaringClass()); // @todo imports in annotations
        $constructorDocBlock = $reflectedConstructor->getDocComment();
        $constructorAnnotations = null;
        if (is_string($constructorDocBlock) && !empty($constructorDocBlock)) {
            $constructorAnnotations = new LazyAnnotationMap(
                $constructorDocBlock,
                $reflectedClass->getNamespaceName(),
                $imports
            );
        }

        foreach ($reflectedConstructor->getParameters() as $param) {
            $builder = PropertyBuilder::new();
            if (!\is_null($constructorAnnotations) && $constructorAnnotations->hasParam($param->getName())) {
                $builder = $constructorAnnotations->tagToPropertyBuilder('param', $param->getName());
            }

            $builder->setName($param->getName())
                ->setVisibility(Visibility::PUBLIC());

            $reflectedType = $param->getType();
            $builder->setIsNullable(is_null($reflectedType));
            if (! \is_null($reflectedType)) {
                $type = $reflectedType->getName();
                if ($type === 'array') {
                    $builder->addType('mixed', ArrayInformation::singleDimension());
                } else {
                    $builder->addType($type, ArrayInformation::notAnArray());
                }

                $builder->setIsNullable($reflectedType->allowsNull());
            }

            if ($reflectedClass->hasProperty($param->getName())) {
                $propertyDocComment = $reflectedClass->getProperty($param->getName())->getDocComment();
                if (is_string($propertyDocComment) && !empty($propertyDocComment)) {
                    $propertyAnnotations = new LazyAnnotationMap(
                        $propertyDocComment,
                        $reflectedClass->getNamespaceName(),
                        $imports
                    );
                    $builder->addTypes(...$propertyAnnotations->tagToPropertyBuilder('var')->getTypes());
                }
            }

            if (!$builder->hasAnyType()) {
                $builder->addType('mixed', ArrayInformation::notAnArray());
            }

            $this->propertyMap->addProperty($builder->build());
            $this->parameterMap[$param->getPosition()] = $param->getName();
            $this->parameterDefaults[$param->getName()] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
        }

        ksort($this->parameterMap);
    }

    public function __invoke(\stdClass $json)
    {
        $values = [];

        foreach ($this->parameterMap as $position => $name) {
            $values[$position] = $this->valueFactory->build(
                $this->mapper,
                $this->propertyMap->getProperty($name),
                $json->$name ?? $this->parameterDefaults[$name]
            );
        }

        return new $this->objectName(...$values);
    }
}
