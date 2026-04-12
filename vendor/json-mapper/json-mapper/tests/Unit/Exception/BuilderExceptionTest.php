<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Exception;

use JsonMapper\Exception\BuilderException;
use PHPUnit\Framework\TestCase;

class BuilderExceptionTest extends TestCase
{
    /** @covers \JsonMapper\Exception\BuilderException */
    public function testInvalidJsonMapperClassName(): void
    {
        $exception = BuilderException::invalidJsonMapperClassName(\DateTimeImmutable::class);

        self::assertStringContainsString(\DateTimeImmutable::class, $exception->getMessage());
    }

    /** @covers \JsonMapper\Exception\BuilderException */
    public function testForBuildingWithoutMiddleware(): void
    {
        $exception = BuilderException::forBuildingWithoutMiddleware();

        self::assertStringContainsString('without middleware', $exception->getMessage());
    }
}
