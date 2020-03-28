<?php

namespace MimeTyper\Repository;

interface RepositoryInterface
{
    /**
     * Find the extension matching $type mime type.
     *
     * If multiple extensions match the type, the main (prefered) is returned.
     *
     * @param  string $type.
     *
     * @return string.
     *
     * @see RepositoryInterface\findExtensions()
     */
    public function findExtension($type);

    /**
     * Does extension $extension exists?
     *
     * @param  string  $extension
     *
     * @return boolean
     */
    public function hasExtension($extension);


    /**
     * Find all types matching $extension extension.
     *
     * @param  string $extension.
     *
     * @return array.
     */
    public function findTypes($extension);

    /**
     * Does type $type exists?
     *
     * @param  string  $type
     *
     * @return boolean
     */
    public function hasType($type);
}