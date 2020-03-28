<?php

/*
 * This file is a part of dflydev/apache-mime-types.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../vendor/autoload.php';

if (!class_exists('Twig_Environment')) {
    echo 'You must set up the project dev dependencies, run the following commands:'.PHP_EOL.
        'php composer.phar install --dev'.PHP_EOL;
    exit(1);
}

$repository = new Dflydev\ApacheMimeTypes\FlatRepository;

$typeToExtensions = $repository->dumpTypeToExtensions();
$extensionToType = $repository->dumpExtensionToType();

file_put_contents(__DIR__.'/../src/Dflydev/ApacheMimeTypes/Resources/mime.types.json', json_encode($typeToExtensions));

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(__DIR__.'/../resources'),
    array(
        'autoescape'  => false,
        'auto_reload' => true,
    )
);

file_put_contents(
    __DIR__ . '/../src/Dflydev/ApacheMimeTypes/PhpRepository.php',
    $twig->render('PhpRepository.twig', array(
        'extensionToType' => $extensionToType,
        'typeToExtensions' => $typeToExtensions,
    ))
);

$parser = new Dflydev\ApacheMimeTypes\Parser;

$fixturesMap = $parser->parse(__DIR__.'/../tests/Dflydev/ApacheMimeTypes/Fixtures/mime.types');
file_put_contents(__DIR__.'/../tests/Dflydev/ApacheMimeTypes/Fixtures/mime.types.json', json_encode($fixturesMap));

