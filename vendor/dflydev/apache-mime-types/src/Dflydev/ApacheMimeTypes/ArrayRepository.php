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
 * Array Repository
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class ArrayRepository extends AbstractRepository
{
    protected $data;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    protected function internalInit()
    {
        $this->setFromMap($this->data);
    }

    public function addType($type, array $extensions)
    {
        $this->reset();

        if (isset($this->data[$type])) {
            $this->data[$type] = array_unique($extensions, array_merge($this->data[$type]));
        } else {
            $this->data[$type] = array_unique($extensions);
        }
    }
}
