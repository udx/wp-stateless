<?php

declare(strict_types=1);

namespace JsonMapper\Builders;

use JsonMapper\Handler\FactoryRegistry;
use JsonMapper\Handler\PropertyMapper;
use JsonMapper\Helpers\IScalarCaster;

class PropertyMapperBuilder
{
    /** @var FactoryRegistry|null */
    private $classFactoryRegistry;
    /** @var FactoryRegistry|null */
    private $nonInstantiableTypeResolver;
    /** @var IScalarCaster|null */
    private $scalarCaster;

    public static function new(): PropertyMapperBuilder
    {
        return new PropertyMapperBuilder();
    }

    public function build(): PropertyMapper
    {
        return new PropertyMapper($this->classFactoryRegistry, $this->nonInstantiableTypeResolver, $this->scalarCaster);
    }

    public function withClassFactoryRegistry(FactoryRegistry $classFactoryRegistry): PropertyMapperBuilder
    {
        $this->classFactoryRegistry = $classFactoryRegistry;

        return $this;
    }

    public function withNonInstantiableTypeResolver(FactoryRegistry $nonInstantiableTypeResolver): PropertyMapperBuilder
    {
        $this->nonInstantiableTypeResolver = $nonInstantiableTypeResolver;

        return $this;
    }

    public function withScalarCaster(IScalarCaster $scalarCaster): PropertyMapperBuilder
    {
        $this->scalarCaster = $scalarCaster;

        return $this;
    }
}
