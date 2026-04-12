<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Dto;

use JsonMapper\Dto\NamedMiddleware;
use JsonMapper\Middleware\AbstractMiddleware;
use PHPUnit\Framework\TestCase;

class NamedMiddlewareTest extends TestCase
{
    /** @covers \JsonMapper\Dto\NamedMiddleware */
    public function testCanHoldProperties(): void
    {
        $middleware = $this->createMock(AbstractMiddleware::class);
        $namedMiddleware = new NamedMiddleware($middleware, 'some-name');

        self::assertSame($middleware, $namedMiddleware->getMiddleware());
        self::assertSame('some-name', $namedMiddleware->getName());
    }
}
