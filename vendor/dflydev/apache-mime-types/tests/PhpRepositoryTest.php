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

use Dflydev\ApacheMimeTypes\PhpRepository;

/**
 * JSON Repository Test
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class PhpRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createDefaultRepository()
    {
        return new PhpRepository;
    }

    protected function createRepository()
    {
        return null;
    }
}
