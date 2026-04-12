<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Integration\Regression;

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Implementation\Php74\BuiltinExt\DateTime;
use JsonMapper\Tests\Implementation\Php74\BuiltinExt\DateTimeCollection;
use PHPUnit\Framework\TestCase;

class Bug102RegressionTest extends TestCase
{
    /**
     * @test
     * @requires PHP >= 7.4
     * @coversNothing
     */
    public function jsonMapperDoesNotLookupClassNameCorrectlyInSameNameSpaceForReusedBuiltinClassName(): void
    {
        $mapper = (new JsonMapperFactory())->bestFit();
        $collection = new DateTimeCollection();
        $data = (object) ['items' => [
            (object) ['date' => '2021-08-31', 'time' => '11:23'],
            (object) ['date' => '2021-08-31', 'time' => '11:24'],
        ]];

        $mapper->mapObject($data, $collection);

        self::assertEquals(
            new DateTimeCollection(
                new DateTime($data->items[0]->date, $data->items[0]->time),
                new DateTime($data->items[1]->date, $data->items[1]->time)
            ),
            $collection
        );
    }
}
