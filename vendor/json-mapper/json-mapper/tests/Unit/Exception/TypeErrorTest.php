<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Exception;

use JsonMapper\Exception\TypeError;
use PHPUnit\Framework\TestCase;

class TypeErrorTest extends TestCase
{
    /**
     * @covers \JsonMapper\Exception\TypeError
     */
    public function testForObjectArgumentReturnsExpectedException(): void
    {
        $e = $this->createTypeError(__METHOD__, 'object', '', 1, '$object');

        self::assertEquals(
            sprintf(
                '%s(): Argument #1 ($object) must be of type object, string given, called in %s on line %d',
                __METHOD__,
                __FILE__,
                __LINE__ - 7
            ),
            $e->getMessage()
        );
    }

    private function createTypeError(
        string $method,
        string $expectedType,
        $argument,
        int $argumentNumber,
        string $argumentName
    ): TypeError {
        return TypeError::forArgument($method, $expectedType, $argument, $argumentNumber, $argumentName);
    }
}
