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

use Dflydev\ApacheMimeTypes\JsonRepository;

/**
 * JSON Repository Test
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class JsonRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createDefaultRepository()
    {
        return new JsonRepository;
    }

    protected function createRepository()
    {
        return new JsonRepository(__DIR__.'/fixtures/mime.types.json');
    }
}
