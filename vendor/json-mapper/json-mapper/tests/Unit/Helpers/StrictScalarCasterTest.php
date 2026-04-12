<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Helpers;

use JsonMapper\Enums\ScalarType;
use JsonMapper\Helpers\StrictScalarCaster;
use PHPUnit\Framework\TestCase;

class StrictScalarCasterTest extends TestCase
{
    /**
     * @covers \JsonMapper\Helpers\StrictScalarCaster
     * @dataProvider castOperationDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testCastOperationReturnsTheCorrectValue($value, string $castTo, $expected): void
    {
        $caster = new StrictScalarCaster();

        $result = $caster->cast(new ScalarType($castTo), $value);

        self::assertEquals($expected, $result);
    }

    /**
     * @covers \JsonMapper\Helpers\StrictScalarCaster
     * @dataProvider castOperationExceptionsDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testCastOperationWithMismatchedValueThrowsException($value, string $castTo, $expected): void
    {
        $caster = new StrictScalarCaster();

        $this->expectException(\Exception::class);
        $caster->cast(new ScalarType($castTo), $value);
    }

    public function castOperationDataProvider(): array
    {
        return [
            'cast to string' => ['42', 'string', '42'],
            'cast to boolean' => [true, 'bool', true],
            'cast to int' => [42, 'int', 42],
            'cast to float' => [34.567, 'float', 34.567],
            'cast to mixed' => ['34.567', 'mixed', '34.567'],
        ];
    }

    public function castOperationExceptionsDataProvider(): array
    {
        return [
            'cast to string' => [42, 'string', '42'],
            'cast to boolean true' => [1, 'bool', true],
            'cast to boolean false' => [0, 'bool', false],
            'cast to int' => ['42', 'int', 42],
            'cast to float' => ['34.567', 'float', 34.567],
        ];
    }
}
