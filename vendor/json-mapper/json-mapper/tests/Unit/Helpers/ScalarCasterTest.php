<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Helpers;

use JsonMapper\Enums\ScalarType;
use JsonMapper\Helpers\ScalarCaster;
use PHPUnit\Framework\TestCase;

class ScalarCasterTest extends TestCase
{
    /**
     * @covers \JsonMapper\Helpers\ScalarCaster
     * @dataProvider castOperationDataProvider
     *
     * @param mixed $value
     * @param mixed $expected
     */
    public function testCastOperationReturnsTheCorrectValue($value, string $castTo, $expected): void
    {
        $caster = new ScalarCaster();
        self::assertEquals($expected, $caster->cast(new ScalarType($castTo), $value));
    }

    /**
     * @covers \JsonMapper\Helpers\ScalarCaster
     */
    public function testCastOperationThrowsExceptionWhenCastOperationNotSupported(): void
    {
        $caster = new ScalarCaster();
        $extension = new class ('random') extends ScalarType {
            protected const RANDOM = 'random';

            public function __construct()
            {
                parent::__construct(self::RANDOM);
            }
        };

        $this->expectException(\LogicException::class);
        $caster->cast($extension, null);
    }

    public function castOperationDataProvider(): array
    {
        return [
            'cast to string' => [42, 'string', '42'],
            'cast to boolean true' => [1, 'bool', true],
            'cast to boolean false' => [0, 'bool', false],
            'cast to int' => ['42', 'int', 42],
            'cast to float' => ['34.567', 'float', 34.567],
            'cast to mixed' => ['34.567', 'mixed', '34.567'],
        ];
    }
}
