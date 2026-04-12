<?php
/**
 * Build a list of files from the composer autoloaders.
 *
 * Also record the `files` autoloaders.
 */

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use BrianHenryIE\Strauss\Helpers\Path;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Symfony\Component\Finder\Finder;

class FileEnumerator
{
    /**
     * The only path variable with a leading slash.
     * All directories in project end with a slash.
     *
     * @var string
     */
    protected string $workingDir;

    /** @var string */
    protected string $vendorDir;

    /** @var ComposerPackage[] */
    protected array $dependencies;

    /** @var string[]  */
    protected array $excludePackageNames = array();

    /** @var string[]  */
    protected array $excludeNamespaces = array();

    /** @var string[]  */
    protected array $excludeFilePatterns = array();

    /** @var Filesystem */
    protected Filesystem $filesystem;

    protected DiscoveredFiles $discoveredFiles;

    /**
     * Record the files autoloaders for later use in building our own autoloader.
     *
     * Package-name: [ dir1, file1, file2, ... ].
     *
     * @var array<string, string[]>
     */
    protected array $filesAutoloaders = [];

    /**
     * Copier constructor.
     * @param ComposerPackage[] $dependencies
     * @param string $workingDir
     */
    public function __construct(
        array $dependencies,
        string $workingDir,
        StraussConfig $config
    ) {
        $this->discoveredFiles = new DiscoveredFiles();

        $this->workingDir = $workingDir;
        $this->vendorDir = $config->getVendorDirectory();

        $this->dependencies = $dependencies;

        $this->excludeNamespaces = $config->getExcludeNamespacesFromCopy();
        $this->excludePackageNames = $config->getExcludePackagesFromCopy();
        $this->excludeFilePatterns = $config->getExcludeFilePatternsFromCopy();

        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->workingDir));
    }

    /**
     * Read the autoload keys of the dependencies and generate a list of the files referenced.
     *
     * Includes all files in the directories and subdirectories mentioned in the autoloaders.
     */
    public function compileFileList(): DiscoveredFiles
    {
        foreach ($this->dependencies as $dependency) {
            if (in_array($dependency->getPackageName(), $this->excludePackageNames)) {
                continue;
            }

            /**
             * Where $dependency->autoload is ~
             *
             * [ "psr-4" => [ "BrianHenryIE\Strauss" => "src" ] ]
             * Exclude "exclude-from-classmap"
             * @see https://getcomposer.org/doc/04-schema.md#exclude-files-from-classmaps
             */
            $autoloaders = array_filter($dependency->getAutoload(), function ($type) {
                return 'exclude-from-classmap' !== $type;
            }, ARRAY_FILTER_USE_KEY);

            foreach ($autoloaders as $type => $value) {
                // Might have to switch/case here.

                if ('files' === $type) {
                    $this->filesAutoloaders[$dependency->getRelativePath()] = $value;
                }

                foreach ($value as $namespace => $namespace_relative_paths) {
                    if (!empty($namespace) && in_array($namespace, $this->excludeNamespaces)) {
                        continue;
                    }

                    if (! is_array($namespace_relative_paths)) {
                        $namespace_relative_paths = array( $namespace_relative_paths );
                    }

                    foreach ($namespace_relative_paths as $namespaceRelativePath) {
                        $sourceAbsolutePath = $dependency->getPackageAbsolutePath() . $namespaceRelativePath;

                        if (is_file($sourceAbsolutePath)) {
                            $this->addFile($dependency, $namespaceRelativePath, $type);
                        } elseif (is_dir($sourceAbsolutePath)) {
                            // trailingslashit(). (to remove duplicates).
                            $sourcePath = Path::normalize($sourceAbsolutePath);

//                          $this->findFilesInDirectory()
                            $finder = new Finder();
                            $finder->files()->in($sourcePath)->followLinks();

                            foreach ($finder as $foundFile) {
                                $sourceAbsoluteFilepath = $foundFile->getPathname();

                                // No need to record the directory itself.
                                if (is_dir($sourceAbsoluteFilepath)) {
                                    continue;
                                }

                                $namespaceRelativePath = Path::normalize($namespaceRelativePath);

                                $this->addFile(
                                    $dependency,
                                    $namespaceRelativePath . str_replace($sourcePath, '', $sourceAbsoluteFilepath),
                                    $type
                                );
                            }
                        }
                    }
                }
            }
        }

        return $this->discoveredFiles;
    }

    /**
     * @uses \BrianHenryIE\Strauss\DiscoveredFiles::add()
     *
     * @param ComposerPackage $dependency
     * @param string $packageRelativePath
     * @param string $autoloaderType
     * @throws \League\Flysystem\FilesystemException
     */
    protected function addFile(ComposerPackage $dependency, string $packageRelativePath, string $autoloaderType): void
    {
        $sourceAbsoluteFilepath = $dependency->getPackageAbsolutePath() . $packageRelativePath;
        $outputRelativeFilepath = $dependency->getRelativePath() . $packageRelativePath;
        $projectRelativePath    = $this->vendorDir . $outputRelativeFilepath;
        $isOutsideProjectDir    = 0 !== strpos($sourceAbsoluteFilepath, $this->workingDir);

        $f = $this->discoveredFiles->getFiles()[$outputRelativeFilepath]
            ?? new File($dependency, $packageRelativePath, $sourceAbsoluteFilepath);

        $f->addAutoloader($autoloaderType);
        $f->setDoDelete($isOutsideProjectDir);

        foreach ($this->excludeFilePatterns as $excludePattern) {
            if (1 === preg_match($excludePattern, $outputRelativeFilepath)) {
                $f->setDoCopy(false);
            }
        }

        if ('<?php // This file was deleted by {@see https://github.com/BrianHenryIE/strauss}.'
            ===
            $this->filesystem->read($projectRelativePath)
        ) {
            $f->setDoCopy(false);
        }

        $this->discoveredFiles->add($f);
    }

    /**
     * @param string $workingDir Absolute path to the working directory, results will be relative to this.
     * @param string $relativeDirectory
     * @param string $regexPattern Default to PHP files.
     *
     * @return string[]
     */
    public function findFilesInDirectory(string $workingDir, string $relativeDirectory = '.', string $regexPattern = '/.+\.php$/'): array
    {
        $dir = new RecursiveDirectoryIterator($workingDir . $relativeDirectory);
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $regexPattern, RegexIterator::GET_MATCH);
        $fileList = array();
        foreach ($files as $file) {
            $fileList = array_merge($fileList, str_replace($workingDir, '', $file));
        }
        return $fileList;
    }
}
