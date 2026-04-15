<?php

declare(strict_types=1);

namespace JsonMapper\Helpers;

use JsonMapper\Exception\PhpFileParseException;
use JsonMapper\Parser\Import;
use JsonMapper\Parser\UseNodeVisitor;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

class UseStatementHelper
{
    /** @var string */
    private static $evaldCodeFileNameEnding = "eval()'d code";

    /** @return Import[] */
    public static function getImports(\ReflectionClass $class): array
    {
        if (!$class->isUserDefined()) {
            return [];
        }

        $filename = $class->getFileName();
        if ($filename === false || \substr($filename, -13) === self::$evaldCodeFileNameEnding) {
            throw new \RuntimeException("Class {$class->getName()} has no filename available");
        }

        if ($class->getParentClass() === false) {
            return self::getImportsForFileName($filename);
        }

        return array_unique(
            array_merge(self::getImportsForFileName($filename), self::getImports($class->getParentClass())),
            SORT_REGULAR
        );
    }

    /** @return Import[] */
    private static function getImportsForFileName(string $filename): array
    {
        if (! \is_readable($filename)) {
            throw new \RuntimeException("Unable to read {$filename}");
        }

        $contents = \file_get_contents($filename);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read {$filename}");
        }

        $parser = method_exists(ParserFactory::class, 'createForNewestSupportedVersion')
          ? (new ParserFactory())->createForNewestSupportedVersion()
          : (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        try {
            $ast = $parser->parse($contents);
            if (\is_null($ast)) {
                throw new PhpFileParseException("Failed to parse {$filename}");
            }
        } catch (\Throwable $e) {
            throw new PhpFileParseException("Failed to parse {$filename}");
        }

        $traverser = new NodeTraverser();
        $visitor = new UseNodeVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getImports();
    }
}
