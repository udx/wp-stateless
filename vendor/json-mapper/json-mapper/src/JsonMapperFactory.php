<?php

declare(strict_types=1);

namespace JsonMapper;

use JsonMapper\Handler\PropertyMapper;
use JsonMapper\Middleware\MiddlewareInterface;

class JsonMapperFactory
{
    /** @var JsonMapperBuilder */
    private $builder;

    public function __construct(?JsonMapperBuilder $builder = null)
    {
        $this->builder = $builder ?? JsonMapperBuilder::new();
    }

    public function create(?PropertyMapper $propertyMapper = null, MiddlewareInterface ...$handlers): JsonMapperInterface
    {
        $builder = clone ($this->builder);
        $builder->withPropertyMapper($propertyMapper ?? new PropertyMapper());
        foreach ($handlers as $handler) {
            $builder->withMiddleware($handler);
        }

        return $builder->build();
    }

    public function default(): JsonMapperInterface
    {
        $builder = clone ($this->builder);
        return $builder->withDocBlockAnnotationsMiddleware()
            ->withNamespaceResolverMiddleware()
            ->build();
    }

    public function bestFit(): JsonMapperInterface
    {
        $builder = clone ($this->builder);
        return $builder->withDocBlockAnnotationsMiddleware()
            ->withTypedPropertiesMiddleware()
            ->withNamespaceResolverMiddleware()
            ->build();
    }
}
