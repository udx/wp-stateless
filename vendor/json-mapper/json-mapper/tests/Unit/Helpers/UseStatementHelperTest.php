<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Helpers;

use JsonMapper\Exception\PhpFileParseException;
use JsonMapper\Helpers\UseStatementHelper;
use JsonMapper\Parser\Import;
use JsonMapper\Tests\Implementation\Foo\MetaModel\Meta;
use JsonMapper\Tests\Implementation\Foo\Model\BarModel;
use JsonMapper\Tests\Implementation\Foo\Test;
use JsonMapper\Tests\Integration\Namespaces\One\Example;
use PHPUnit\Framework\TestCase;

class UseStatementHelperTest extends TestCase
{
    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testCanGetImports(): void
    {
        $imports = UseStatementHelper::getImports(new \ReflectionClass(Test::class));

        self::assertEquals(
            [
                new Import(BarModel::class, null),
                new Import(Meta::class, null),
            ],
            $imports
        );
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsForReflectedClassWithoutFileThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        eval('class ClassWithoutFile {}');
        UseStatementHelper::getImports(new \ReflectionClass(new \ClassWithoutFile()));
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithFileNotReadableThrowsException(): void
    {
        $fileName = '/some/non/readable/path';
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->method('isUserDefined')->willReturn(true);
        $reflectionMock->method('getFileName')->willReturn($fileName);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Unable to read {$fileName}");
        UseStatementHelper::getImports($reflectionMock);
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithFileNotProvidingValidAstThrowsException(): void
    {
        $fileName = tempnam(sys_get_temp_dir(), __METHOD__);
        $handle = fopen($fileName, 'wb');
        fwrite($handle, "<?php some invalid php code");
        fclose($handle);
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->method('isUserDefined')->willReturn(true);
        $reflectionMock->method('getFileName')->willReturn($fileName);

        $this->expectException(PhpFileParseException::class);
        $this->expectExceptionMessage("Failed to parse {$fileName}");
        UseStatementHelper::getImports($reflectionMock);

        unlink($fileName);
    }

    /**
     * @covers \JsonMapper\Helpers\UseStatementHelper
     */
    public function testGettingImportsWithBuiltinClassReturnsEmptyArray(): void
    {
        $imports = UseStatementHelper::getImports(new \ReflectionClass(\stdClass::class));

        self::assertEquals([], $imports);
    }
}
