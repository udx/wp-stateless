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
 * JSON Repository
 *
 * Reads a JSON file.
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class JsonRepository extends AbstractRepository
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
            $filename = __DIR__.'/Resources/mime.types.json';
        }

        $this->filename = $filename;
    }

    protected function internalInit()
    {
        $this->setFromMap(json_decode(file_get_contents($this->filename), true));
    }
}
