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

use Dflydev\ApacheMimeTypes\ArrayRepository;

/**
 * Array Repository Test
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ArrayRepositoryTest extends AbstractRepositoryTestCase
{
    protected function createDefaultRepository()
    {
        return null;
    }

    protected function createRepository()
    {
        return new ArrayRepository(array(
            'dflydev/apache-mime-types' => array('dflydevamt', 'ddevamt'),
            'dflydev/yet-another-mime-type' => array('dflydevyamt'),
        ));
    }
}
