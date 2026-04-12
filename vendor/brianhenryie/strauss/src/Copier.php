<?php
/**
 * Prepares the destination by deleting any files about to be copied.
 * Copies the files.
 *
 * TODO: Exclude files list.
 *
 * @author CoenJacobs
 * @author BrianHenryIE
 *
 * @license MIT
 */

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class Copier
{
    /**
     * The only path variable with a leading slash.
     * All directories in project end with a slash.
     *
     * @var string
     */
    protected string $workingDir;

    protected string $absoluteTargetDir;

    protected DiscoveredFiles $files;

    /** @var Filesystem */
    protected Filesystem $filesystem;

    /**
     * Copier constructor.
     *
     * @param DiscoveredFiles $files
     * @param string $workingDir
     * @param StraussConfig $config
     */
    public function __construct(DiscoveredFiles $files, string $workingDir, StraussConfig $config)
    {
        $this->files = $files;

        $this->absoluteTargetDir = $workingDir . $config->getTargetDirectory();

        $this->filesystem = new Filesystem(new LocalFilesystemAdapter('/'));
    }

    /**
     * If the target dir does not exist, create it.
     * If it already exists, delete any files we're about to copy.
     *
     * @return void
     */
    public function prepareTarget(): void
    {
        if (! is_dir($this->absoluteTargetDir)) {
            $this->filesystem->createDirectory($this->absoluteTargetDir);
            $this->filesystem->setVisibility($this->absoluteTargetDir, 'public');
        } else {
            foreach ($this->files->getFiles() as $file) {
                $targetAbsoluteFilepath = $this->absoluteTargetDir . $file->getTargetRelativePath();

                if ($this->filesystem->fileExists($targetAbsoluteFilepath)) {
                    $this->filesystem->delete($targetAbsoluteFilepath);
                }
            }
        }
    }

    public function copy(): void
    {
        /**
         * @var File $file
         */
        foreach ($this->files->getFiles() as $file) {
            $sourceAbsoluteFilepath = $file->getSourcePath();

            $targetAbsolutePath = $this->absoluteTargetDir . $file->getTargetRelativePath();

            $this->filesystem->copy($sourceAbsoluteFilepath, $targetAbsolutePath);
        }
    }
}
