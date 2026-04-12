<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Middleware;

use JsonMapper\Handler\PropertyMapper;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Middleware\FinalCallback;
use JsonMapper\Tests\Implementation\SimpleObject;
use JsonMapper\ValueObjects\PropertyMap;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;

class FinalCallbackTest extends TestCase
{
    /**
     * @covers \JsonMapper\Middleware\FinalCallback
     */
    public function testCallbackIsInvoked(): void
    {
        $isCalled = false;
        $middleware = new FinalCallback(static function () use (&$isCalled) {
            $isCalled = true;
        });
        $object = new ObjectWrapper(new SimpleObject());
        $function = $middleware->__invoke(new PropertyMapper());

        $function(new \stdClass(), $object, new PropertyMap(), $this->createMock(JsonMapperInterface::class));

        self::assertTrue($isCalled);
    }
}
