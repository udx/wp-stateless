<?php

namespace BrianHenryIE\Strauss;

use ArrayAccess;
use BrianHenryIE\Strauss\Composer\ComposerPackage;

class DiscoveredFiles
{
    /** @var array<string,File> */
    protected array $files = [];

    /**
     * @param File $file
     */
    public function add(File $file): void
    {
        $this->files[$file->getTargetRelativePath()] = $file;
    }

    /**
     * @return File[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * Returns all found files.
     *
     * @return array<string,array{dependency:ComposerPackage,sourceAbsoluteFilepath:string,targetRelativeFilepath:string}>
     */
    public function getAllFilesAndDependencyList(): array
    {
        $allFiles = [];
        foreach ($this->files as $file) {
            if (!$file->isDoCopy()) {
                continue;
            }
            $allFiles[ $file->getTargetRelativePath() ] = [
                'dependency'             => $file->getDependency(),
                'sourceAbsoluteFilepath' => $file->getSourcePath(),
                'targetRelativeFilepath' => $file->getTargetRelativePath(),
            ];
        }
        return $allFiles;
    }


    /**
     * Returns found PHP files.
     *
     * @return array<string,array{dependency:ComposerPackage,sourceAbsoluteFilepath:string,targetRelativeFilepath:string}>
     */
    public function getPhpFilesAndDependencyList(): array
    {
        // Filter out non .php files by checking the key.
        return array_filter($this->getAllFilesAndDependencyList(), function ($value, $key) {
            return false !== strpos($key, '.php');
        }, ARRAY_FILTER_USE_BOTH);
    }
}
