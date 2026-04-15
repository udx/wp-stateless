<?php

declare(strict_types=1);

namespace JsonMapper\Middleware;

use JsonMapper\JsonMapperInterface;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use Psr\Log\LoggerInterface;

class Debugger extends AbstractMiddleware
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(
        \stdClass $json,
        ObjectWrapper $object,
        PropertyMap $propertyMap,
        JsonMapperInterface $mapper
    ): void {
        $this->logger->debug(
            'Current state attributes passed through JsonMapper middleware',
            [
                'json' => \json_encode($json),
                'object' => $object->getName(),
                'propertyMap' => $propertyMap->toString()
            ]
        );
    }
}
