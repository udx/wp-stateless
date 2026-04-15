<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Exception;

use JsonMapper\Exception\ClassFactoryException;
use PHPUnit\Framework\TestCase;

class ClassFactoryExceptionTest extends TestCase
{
    /**
     * @covers \JsonMapper\Exception\ClassFactoryException
     */
    public function testForDuplicateClassnameReturnsCorrectException(): void
    {
        $exception = ClassFactoryException::forDuplicateClassname(__CLASS__);

        self::assertStringContainsString(__CLASS__, $exception->getMessage());
    }

    /**
     * @covers \JsonMapper\Exception\ClassFactoryException
     */
    public function testForMissingClassnameReturnsCorrectException(): void
    {
        $exception = ClassFactoryException::forMissingClassname(__CLASS__);

        self::assertStringContainsString(__CLASS__, $exception->getMessage());
    }
}
