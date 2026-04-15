<?php

declare(strict_types=1);

namespace JsonMapper\Tests\benchmark;

use JsonMapper\JsonMapperFactory;
use JsonMapper\JsonMapperInterface;
use JsonMapper\Tests\Implementation\Api\ChuckNorris\Php71\SearchResponse;

class JsonMapperBench
{
    /** @var JsonMapperInterface */
    private $mapper;
    /** @var string*/
    private $content;

    public function __construct()
    {
        $this->mapper = (new JsonMapperFactory())->bestFit();
        $this->content = file_get_contents(__DIR__ . '/../resources/chucknorris/kick.json');
    }

    /**
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchMapSimpleObject(): void
    {
        $this->mapper->mapObjectFromString(
            '{"id":131,"type":"general","setup":"How do you organize a space party?","punchline":"You planet."}',
            new Joke()
        );
    }

    /**
     * @Revs(100)
     * @Iterations(5)
     */
    public function benchMapComplexObject(): void
    {
        $this->mapper->mapObjectFromString($this->content, new SearchResponse());
    }
}
