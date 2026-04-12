<?php
/**
 * Build a list of ComposerPackage objects for all dependencies.
 */

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class DependenciesEnumerator
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

    /**
     * @var string[]
     */
    protected array $requiredPackageNames;

    /** @var Filesystem */
    protected Filesystem $filesystem;

    /** @var string[]  */
    protected array $virtualPackages = array(
        'php-http/client-implementation'
    );

    /** @var array<string, ComposerPackage> */
    protected array $flatDependencyTree = array();

    /**
     * Record the files autoloaders for later use in building our own autoloader.
     *
     * Package-name: [ dir1, file1, file2, ... ].
     *
     * @var array<string, string[]>
     */
    protected array $filesAutoloaders = [];

    /**
     * @var array{}|array<string, array{files?:array<string>,classmap?:array<string>,"psr-4":array<string|array<string>>}> $overrideAutoload
     */
    protected array $overrideAutoload = array();

    /**
     * Constructor.
     *
     * @param string $workingDir
     * @param StraussConfig $config
     */
    public function __construct(
        string $workingDir,
        StraussConfig $config
    ) {
        $this->workingDir = $workingDir;
        $this->vendorDir = $config->getVendorDirectory();
        $this->overrideAutoload = $config->getOverrideAutoload();
        $this->requiredPackageNames = $config->getPackages();

        $this->filesystem = new Filesystem(new LocalFilesystemAdapter($this->workingDir));
    }

    /**
     * @return array<string, ComposerPackage> Packages indexed by package name.
     * @throws Exception
     */
    public function getAllDependencies(): array
    {
        $this->recursiveGetAllDependencies($this->requiredPackageNames);
        return $this->flatDependencyTree;
    }

    /**
     * @param string[] $requiredPackageNames
     */
    protected function recursiveGetAllDependencies(array $requiredPackageNames): void
    {
        $requiredPackageNames = array_filter($requiredPackageNames, array( $this, 'removeVirtualPackagesFilter' ));

        foreach ($requiredPackageNames as $requiredPackageName) {
            // Avoid infinite recursion.
            if (isset($this->flatDependencyTree[$requiredPackageName])) {
                continue;
            }

            $packageComposerFile = $this->workingDir . $this->vendorDir
                                   . $requiredPackageName . DIRECTORY_SEPARATOR . 'composer.json';

            $overrideAutoload = $this->overrideAutoload[ $requiredPackageName ] ?? null;

            if (file_exists($packageComposerFile)) {
                $requiredComposerPackage = ComposerPackage::fromFile($packageComposerFile, $overrideAutoload);
            } else {
                $fileContents           = file_get_contents($this->workingDir . 'composer.lock');
                if (false === $fileContents) {
                    throw new Exception('Failed to read contents of ' . $this->workingDir . 'composer.lock');
                }
                $composerLock           = json_decode($fileContents, true);
                $requiredPackageComposerJson = null;
                foreach ($composerLock['packages'] as $packageJson) {
                    if ($requiredPackageName === $packageJson['name']) {
                        $requiredPackageComposerJson = $packageJson;
                        break;
                    }
                }
                if (is_null($requiredPackageComposerJson)) {
                    // e.g. composer-plugin-api.
                    continue;
                }

                $requiredComposerPackage = ComposerPackage::fromComposerJsonArray($requiredPackageComposerJson, $overrideAutoload);
            }

            $this->flatDependencyTree[$requiredComposerPackage->getPackageName()] = $requiredComposerPackage;
            $nextRequiredPackageNames                                             = $requiredComposerPackage->getRequiresNames();

            $this->recursiveGetAllDependencies($nextRequiredPackageNames);
        }
    }

    /**
     * Get the recorded files autoloaders.
     *
     * @return array<string, array<string>>
     */
    public function getAllFilesAutoloaders(): array
    {
        $filesAutoloaders = array();
        foreach ($this->flatDependencyTree as $packageName => $composerPackage) {
            if (isset($composerPackage->getAutoload()['files'])) {
                $filesAutoloaders[$packageName] = $composerPackage->getAutoload()['files'];
            }
        }
        return $filesAutoloaders;
    }

    /**
     * Unset PHP, ext-*, ...
     *
     * @param string $requiredPackageName
     */
    protected function removeVirtualPackagesFilter(string $requiredPackageName): bool
    {
        return ! (
            0 === strpos($requiredPackageName, 'ext')
            || 'php' === $requiredPackageName
            || in_array($requiredPackageName, $this->virtualPackages)
        );
    }
}
