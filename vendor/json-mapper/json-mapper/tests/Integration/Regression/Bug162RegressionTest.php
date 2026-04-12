<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\Cache\NullCache;
use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Tests\Implementation\Php74\Article\Article;
use JsonMapper\Tests\Implementation\Php74\Article\Tag;
use JsonMapper\ValueObjects;
use JsonMapper\Wrapper\ObjectWrapper;
use PHPUnit\Framework\TestCase;
use JsonMapper\Middleware;
use JsonMapper\Middleware as MiddlewareUsingAnAlias;

class Bug162RegressionTest extends TestCase
{
    /**
     * @test
     * @coversNothing
     * @requires PHP >= 7.4
     */
    public function testConstructorIsCalledWhenAvailable()
    {
        $data = [
            (object) [
                'id' => 1,
                'title' => 'Article 1',
                'tags' => [(object) ['id' => 1, 'name' => 'Tag 1'], (object) ['id' => 2, 'name' => 'Tag 2']]
            ],
            (object) [
                'id' => 2,
                'title' => 'Article 2',
                'tags' => [(object) ['id' => 1, 'name' => 'Tag 1'], (object) ['id' => 3, 'name' => 'Tag 3']]
            ]
        ];
        $mapper = (new JsonMapperFactory())->bestFit();

        $result = $mapper->mapArray($data, new Article());

        self::assertEquals((new Tag())->getConfiguration(), $result[0]->tags[0]->getConfiguration());
        self::assertEquals((new Tag())->getConfiguration(), $result[0]->tags[1]->getConfiguration());
        self::assertEquals((new Tag())->getConfiguration(), $result[1]->tags[0]->getConfiguration());
        self::assertEquals((new Tag())->getConfiguration(), $result[1]->tags[1]->getConfiguration());
    }
}
