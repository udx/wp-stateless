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
 * Repository
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
interface RepositoryInterface
{
    /**
     * Dump extension to type map
     *
     * @return array
     */
    public function dumpExtensionToType();

    /**
     * Dump type to extensions map
     *
     * @return array
     */
    public function dumpTypeToExtensions();

    /**
     * Find all extensions for a type
     *
     * @param string $type
     *
     * @return array
     */
    public function findExtensions($type);

    /**
     * Find a type for an extension
     *
     * @param string $extension
     *
     * @return string
     */
    public function findType($extension);
}
