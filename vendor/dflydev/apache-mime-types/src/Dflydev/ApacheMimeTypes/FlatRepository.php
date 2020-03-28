<?php

/*
 * This file is a part of dflydev/apache-mime-types.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\ApacheMimeTypes;

/**
 * Flat Repository
 *
 * Reads a standard flat Apache mime.types file.
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class FlatRepository extends AbstractRepository
{
    protected $filename;

    /**
     * Constructor
     *
     * @param string $filename
     */
    public function __construct($filename = null)
    {
        if (null === $filename) {
            $filename = __DIR__.'/Resources/mime.types';
        }

        $this->filename = $filename;
    }

    protected function internalInit()
    {
        $parser = new Parser;

        $this->setFromMap($parser->parse($this->filename));
    }
}
