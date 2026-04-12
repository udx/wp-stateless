<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Builders;

use JsonMapper\Builders\PropertyBuilder;
use JsonMapper\Enums\Visibility;
use JsonMapper\Tests\Helpers\AssertThatPropertyTrait;
use JsonMapper\ValueObjects\ArrayInformation;
use JsonMapper\ValueObjects\PropertyType;
use PHPUnit\Framework\TestCase;

class PropertyBuilderTest extends TestCase
{
    use AssertThatPropertyTrait;

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanBuildPropertyWithAllPropertiesSet(): void
    {
        $property = PropertyBuilder::new()
            ->setName('enabled')
            ->addType('boolean', ArrayInformation::notAnArray())
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();

        $this->assertThatProperty($property)
            ->hasName('enabled')
            ->hasType('boolean', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PRIVATE())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanBuildPropertyWithAllPropertiesSetUsingSetTypes(): void
    {
        $property = PropertyBuilder::new()
            ->setName('enabled')
            ->setTypes(
                new PropertyType('string', ArrayInformation::singleDimension()),
                new PropertyType('int', ArrayInformation::notAnArray())
            )
            ->setIsNullable(true)
            ->setVisibility(Visibility::PRIVATE())
            ->build();

        $this->assertThatProperty($property)
            ->hasName('enabled')
            ->hasType('string', ArrayInformation::singleDimension())
            ->hasType('int', ArrayInformation::notAnArray())
            ->hasVisibility(Visibility::PRIVATE())
            ->isNullable();
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testHasAnyTypeReturnFalseWhenNoTypeIsSet(): void
    {
        $builder = PropertyBuilder::new();

        self::assertFalse($builder->hasAnyType());
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testHasAnyTypeReturnFalseWhenATypeIsSet(): void
    {
        $builder = PropertyBuilder::new()
            ->addType('mixed', ArrayInformation::notAnArray());

        self::assertTrue($builder->hasAnyType());
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanAddMultipleTypes(): void
    {
        $property = PropertyBuilder::new()
            ->setName('test')
            ->setVisibility(Visibility::PRIVATE())
            ->setIsNullable(false)
            ->addTypes(
                new PropertyType('int', ArrayInformation::notAnArray()),
                new PropertyType('string', ArrayInformation::notAnArray())
            )->addTypes(
                new PropertyType('float', ArrayInformation::notAnArray())
            )->build();

        $this->assertThatProperty($property)
            ->hasType('int', ArrayInformation::notAnArray())
            ->hasType('string', ArrayInformation::notAnArray())
            ->hasType('float', ArrayInformation::notAnArray());
    }

    /**
     * @covers \JsonMapper\Builders\PropertyBuilder
     */
    public function testCanGetTypesFromBuilder(): void
    {
        $builder = PropertyBuilder::new()
            ->addTypes(
                new PropertyType('int', ArrayInformation::notAnArray()),
                new PropertyType('string', ArrayInformation::notAnArray())
            )->addTypes(
                new PropertyType('float', ArrayInformation::notAnArray())
            );

        $result = $builder->getTypes();

        self::assertEquals(
            [
                new PropertyType('int', ArrayInformation::notAnArray()),
                new PropertyType('string', ArrayInformation::notAnArray()),
                new PropertyType('float', ArrayInformation::notAnArray()),
            ],
            $result
        );
    }


}
