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
 * Composite Repository
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class CompositeRepository implements RepositoryInterface
{
    protected $repositories;

    /**
     * Constructor
     *
     * @param array $repositories Repositories
     */
    public function __construct(array $repositories = array())
    {
        $this->repositories = $repositories;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpExtensionToType()
    {
        $extensionToType = array();
        foreach ($this->repositories as $repository) {
            foreach ($repository->dumpExtensionToType() as $extension => $type) {
                if (!isset($extensionToType[$extension])) {
                    $extensionToType[$extension] = $type;
                }
            }
        }

        return $extensionToType;
    }

    /**
     * {@inheritdoc}
     */
    public function dumpTypeToExtensions()
    {
        $typeToExtensions = array();
        foreach ($this->repositories as $repository) {
            foreach ($repository->dumpTypeToExtensions() as $type => $extensions) {
                if (isset($typeToExtensions[$type])) {
                    $typeToExtensions[$type] = array_unique(array_merge($typeToExtensions[$type], $extensions));
                } else {
                    $typeToExtensions[$type] = array_unique($extensions);
                }
            }
        }

        return $typeToExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function findExtensions($type)
    {
        $typeToExtensions = $this->dumpTypeToExtensions();

        if (isset($typeToExtensions[$type])) {
            return $typeToExtensions[$type];
        }

        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function findType($extension)
    {
        $extensionToType = $this->dumpExtensionToType();

        if (isset($extensionToType[$extension])) {
            return $extensionToType[$extension];
        }

        return null;
    }
}
