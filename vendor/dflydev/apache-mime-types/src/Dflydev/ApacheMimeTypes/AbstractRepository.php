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
 * Abstract Repository
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
abstract class AbstractRepository implements RepositoryInterface
{
    protected $typeToExtensions;
    protected $extensionToType;
    private $isInitialized = false;

    protected function init()
    {
        if (true === $this->isInitialized) {
            return;
        }

        $this->internalInit();

        $this->isInitialized = true;
    }

    protected function reset()
    {
        $this->isInitialized = false;
    }

    /**
     * Set from map
     *
     * Convenience method supplied in order to make it easier for subclasses
     * to set data from a type => extensions array mapping.
     *
     * @param array $map
     */
    protected function setFromMap(array $map)
    {
        $this->typeToExtensions = $map;

        $this->extensionToType = array();
        foreach ($this->typeToExtensions as $type => $extensions) {
            foreach ($extensions as $extension) {
                if (!isset($this->extensionToType[$extension])) {
                    $this->extensionToType[$extension] = $type;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dumpTypeToExtensions()
    {
        $this->init();

        return $this->typeToExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpExtensionToType()
    {
        $this->init();

        return $this->extensionToType;
    }

    /**
     * {@inheritdoc}
     */
    public function findExtensions($type)
    {
        $this->init();

        if (isset($this->typeToExtensions[$type])) {
            return $this->typeToExtensions[$type];
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function findType($extension)
    {
        $this->init();

        if (isset($this->extensionToType[$extension])) {
            return $this->extensionToType[$extension];
        }

        return null;
    }

    /**
     * Internal initialization
     *
     * Subclasses should extend this in order to execute code exactly
     * once to initialize the repository.
     */
    abstract protected function internalInit();
}
