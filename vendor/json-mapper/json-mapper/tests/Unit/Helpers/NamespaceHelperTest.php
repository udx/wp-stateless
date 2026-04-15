<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Helpers;

use JsonMapper\Helpers\NamespaceHelper;
use JsonMapper\Parser\Import;
use PHPUnit\Framework\TestCase;

class NamespaceHelperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Helpers\NamespaceHelper
     * @dataProvider scalarTypesDataProvider
     */
    public function testResolveNamespaceLeavesScalarTypeAsIs(string $type): void
    {
        $result = NamespaceHelper::resolveNamespace($type, __NAMESPACE__, [new Import(__NAMESPACE__ . '/Test', null)]);

        self::assertSame($type, $result);
    }

    /**
     * @covers \JsonMapper\Helpers\NamespaceHelper
     */
    public function testResolveNamespaceCanMatchWithAliasedImport(): void
    {
        $classname = '\Some\Namespaces\Test';
        $alias = 'SomeBaseClass';
        $import = new Import($classname, $alias);
        $result = NamespaceHelper::resolveNamespace($alias, __NAMESPACE__, [new Import('A'), $import, new Import('B')]);

        self::assertSame($classname, $result);
    }

    /**
     * @covers \JsonMapper\Helpers\NamespaceHelper
     */
    public function testResolveNamespaceCanMatchWithCurrentClassInCurrentNamespace(): void
    {
        $type = 'NamespaceHelperTest';
        $result = NamespaceHelper::resolveNamespace($type, __NAMESPACE__, []);

        self::assertSame(__CLASS__, $result);
    }

    /**
     * @covers \JsonMapper\Helpers\NamespaceHelper
     */
    public function testResolveNamespaceWithoutMatchesWillReturnTypeAsIs(): void
    {
        $type = 'SomeFakeClass';
        $result = NamespaceHelper::resolveNamespace($type, __NAMESPACE__, []);

        self::assertSame($type, $result);
    }

    public function scalarTypesDataProvider(): array
    {
        return [
            'string' => ['string'],
            'boolean' => ['boolean'],
            'bool' => ['bool'],
            'integer' => ['integer'],
            'int' => ['int'],
            'double' => ['double'],
            'float' => ['float'],
            'mixed' => ['mixed'],
        ];
    }
}
