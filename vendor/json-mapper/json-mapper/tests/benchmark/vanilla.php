<?php

# In order to get a cache grind output file (assuming your on PHP8 with Xdebug 3.x)
# add the following lines to your php.ini:

# xdebug.mode = profile;
# xdebug.output_dir = "/Users/dannyvandersluijs/Projects/JsonMapper/build/profiles"

declare(strict_types=1);

use JsonMapper\JsonMapperFactory;
use JsonMapper\Tests\Benchmark\Joke;

chdir(__DIR__ . '/../../');
require_once 'vendor/autoload.php';

$mapper = (new JsonMapperFactory())->bestFit();

for ($x = 1; $x < 5000; $x++) {
    $joke = new Joke();
    $json = '{"id":131,"type":"general","setup":"How do you organize a space party?","punchline":"You planet."}';
    $mapper->mapObjectFromString($json, $joke);
}
