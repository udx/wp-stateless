<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Enums;

use JsonMapper\Enums\Visibility;
use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    /**
     * @covers \JsonMapper\Enums\Visibility
     */
    public function testDetectsPublicPropertyAsPublic(): void
    {
        $reflectionClass = new \ReflectionClass(
            new class {
                /** @var int */
                public $id;
            }
        );
        $reflectionProperty = $reflectionClass->getProperty('id');

        $visibility = Visibility::fromReflectionProperty($reflectionProperty);

        self::assertEquals(Visibility::PUBLIC(), $visibility);
    }

    /**
     * @covers \JsonMapper\Enums\Visibility
     */
    public function testDetectsProtectedPropertyAsProtected(): void
    {
        $reflectionClass = new \ReflectionClass(
            new class {
                /** @var int */
                protected $id;
            }
        );
        $reflectionProperty = $reflectionClass->getProperty('id');

        $visibility = Visibility::fromReflectionProperty($reflectionProperty);

        self::assertEquals(Visibility::PROTECTED(), $visibility);
    }

    /**
     * @covers \JsonMapper\Enums\Visibility
     */
    public function testDetectsPrivatePropertyAsPrivate(): void
    {
        $reflectionClass = new \ReflectionClass(
            new class {
                /** @var int */
                private $id;
            }
        );
        $reflectionProperty = $reflectionClass->getProperty('id');

        $visibility = Visibility::fromReflectionProperty($reflectionProperty);

        self::assertEquals(Visibility::PRIVATE(), $visibility);
    }
}
