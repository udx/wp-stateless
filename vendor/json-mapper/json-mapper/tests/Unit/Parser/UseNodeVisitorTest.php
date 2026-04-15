<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Parser;

use JsonMapper\Parser\Import;
use JsonMapper\Parser\UseNodeVisitor;
use JsonMapper\Tests\Implementation\ComplexObject;
use JsonMapper\Tests\Implementation\SimpleObject;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PHPUnit\Framework\TestCase;

class UseNodeVisitorTest extends TestCase
{
    /**
     * @covers \JsonMapper\Parser\UseNodeVisitor
     */
    public function testKeepsSingleImportsFromNodeForRetrieval(): void
    {
        $visitor = new UseNodeVisitor();
        $uses = [
            new Import(\DateTime::class, null),
            new Import(\stdClass::class, null)
        ];
        $node = new Use_(array_map(static function (Import $use) {
            return new UseUse(new Name($use->getImport()));
        }, $uses));

        $result = $visitor->enterNode($node);
        $imports = $visitor->getImports();

        self::assertNull($result);
        self::assertEquals($uses, $imports);
    }

    /**
     * @covers \JsonMapper\Parser\UseNodeVisitor
     */
    public function testKeepsGroupedImportsFromNodeForRetrieval(): void
    {
        $visitor = new UseNodeVisitor();
        $uses = ['ComplexObject', 'SimpleObject'];
        $node = new GroupUse(new Name('JsonMapper\Tests\Implementation'), array_map(static function ($use) {
            return new UseUse(new Name($use));
        }, $uses));

        $result = $visitor->enterNode($node);
        $imports = $visitor->getImports();

        self::assertNull($result);
        self::assertEquals([new Import(ComplexObject::class, null), new Import(SimpleObject::class, null)], $imports);
    }
}
