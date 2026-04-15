<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Middleware;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Debugger;
use JsonMapper\Tests\Implementation\ComplexObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class DebuggerTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\Debugger
     */
    public function testDebuggerLogsDetails(): void
    {
        $mapper = (new JsonMapperFactory())->default();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                'Current state attributes passed through JsonMapper middleware',
                $this->logicalAnd(
                    $this->arrayHasKey('json'),
                    $this->arrayHasKey('object'),
                    $this->arrayHasKey('propertyMap')
                )
            );
        $mapper->push(new Debugger($logger));
        $json = (object) ['User' => (object) ['Name' => __METHOD__]];
        $object = new ComplexObject();

        $mapper->mapObject($json, $object);
    }
}
