<?php

/*
 * This file is a part of dflydev/apache-mime-types.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\ApacheMimeTypes\Test;

use Dflydev\ApacheMimeTypes\Parser;

/**
 * Parser Test
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ParserTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $parser = new Parser;

        $this->map = $parser->parse(__DIR__.'/../src/Dflydev/ApacheMimeTypes/Resources/mime.types');
    }

    public function testCss()
    {
        $this->assertArrayHasKey('text/css', $this->map);
        $this->assertCount(1, $this->map['text/css']);
        $this->assertEquals('css', $this->map['text/css'][0]);
    }

    public function testHtml()
    {
        $this->assertArrayHasKey('text/html', $this->map);
        $this->assertCount(2, $this->map['text/html']);
        $this->assertEquals('html', $this->map['text/html'][0]);
        $this->assertEquals('htm', $this->map['text/html'][1]);
    }
}
