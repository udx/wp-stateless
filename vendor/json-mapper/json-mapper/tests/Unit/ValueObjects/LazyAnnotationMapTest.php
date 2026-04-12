<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\ValueObjects;

use JsonMapper\Enums\Visibility;
use JsonMapper\Parser\Import;
use JsonMapper\Tests\Implementation\Popo;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\LazyAnnotationMap;
use JsonMapper\ValueObjects\Property;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class LazyAnnotationMapTest extends TestCase
{
    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     */
    public function testHasVarReturnsTrueWhenInputHasVarTag(): void
    {
        $map = new LazyAnnotationMap('/** @var string */');

        $this->assertTrue($map->hasVar());
    }

    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     */
    public function testHasVarReturnsFalseWhenInputHasNoVarTag(): void
    {
        $map = new LazyAnnotationMap('/** @param string $test */');

        $this->assertFalse($map->hasVar());
    }

    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     */
    public function testHasParamReturnsTrueWhenInputHasParamTag(): void
    {
        $map = new LazyAnnotationMap('/** @param string $test */');

        $this->assertTrue($map->hasParam('test'));
    }

    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     */
    public function testHasParamReturnsFalseWhenInputHasNoParamTag(): void
    {
        $map = new LazyAnnotationMap('/** @var string */');

        $this->assertFalse($map->hasParam('test'));
    }

    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     */
    public function testThrowsForUnknownTagWhenCallingTagToPropertyBuilder(): void
    {
        $map = new LazyAnnotationMap('/**  */');

        $this->expectException(\RuntimeException::class);
        $map->tagToPropertyBuilder('var');
    }

    /**
     * @covers \JsonMapper\ValueObjects\LazyAnnotationMap
     * @dataProvider parseDataProvider
     *
     * @param array<Import> $imports
     */
    public function testCorrectlyParsesInput(string $input, array $imports, string $tagName, ?string $variableName, Property $expected): void
    {
        $map = new LazyAnnotationMap($input, __NAMESPACE__, $imports);

        $property = $map->tagToPropertyBuilder($tagName, $variableName)
            ->setName('')
            ->setVisibility(Visibility::PUBLIC())
            ->build();

        self::assertEquals($expected, $property);
    }

    public function parseDataProvider(): \Generator
    {
        yield 'Simple string' => [
            'input' => '/** @var string */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('string', ArrayInformation::notAnArray()))
        ];
        yield 'Simple integer' => [
            'input' => '/** @var int */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('int', ArrayInformation::notAnArray()))
        ];
        yield 'Simple float' => [
            'input' => '/** @var float */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('float', ArrayInformation::notAnArray()))
        ];
        yield 'Mixed' => [
            'input' => '/** @var mixed */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('mixed', ArrayInformation::notAnArray()))
        ];
        yield 'Array of strings' => [
            'input' => '/** @var array<int, string> */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('string', ArrayInformation::singleDimension()))
        ];
        yield 'Nullable string' => [
            'input' => '/** @var ?string */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), true, new PropertyType('string', ArrayInformation::notAnArray()))
        ];
        yield 'true pseudo type' => [
            'input' => '/** @var ?true */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), true, new PropertyType('bool', ArrayInformation::notAnArray()))
        ];
        yield 'Simple int string union' => [
            'input' => '/** @var string|int */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType('string', ArrayInformation::notAnArray()), new PropertyType('int', ArrayInformation::notAnArray()))
        ];
        yield 'Simple int null union' => [
            'input' => '/** @var int|null */',
            'imports' => [],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), true, new PropertyType('int', ArrayInformation::notAnArray()))
        ];
        yield 'Object with imports' => [
            'input' => '/** @var Popo */',
            'imports' => [new Import(Popo::class)],
            'tagName' => 'var',
            'variableName' => null,
            new Property('', Visibility::PUBLIC(), false, new PropertyType(Popo::class, ArrayInformation::notAnArray()))
        ];
    }
}
