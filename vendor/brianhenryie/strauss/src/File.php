<?php

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\ComposerPackage;

class File
{
    /**
     * The project dependency that this file belongs to.
     */
    protected ComposerPackage $dependency;

    /**
     * @var string The path to the file relative to the package root.
     */
    protected string $packageRelativePath;

    /**
     * @var string The absolute path to the file on disk.
     */
    protected string $sourceAbsolutePath;

    /**
     * @var string[] The autoloader types that this file is included in.
     */
    protected array $autoloaderTypes = [];

    /**
     * Should this file be copied to the target directory?
     */
    protected bool $doCopy = true;

    /**
     * Should this file be deleted from the source directory?
     */
    protected bool $doDelete = false;

    /** @var DiscoveredSymbol[] */
    protected array $discoveredSymbols = [];

    public function __construct(ComposerPackage $dependency, string $packageRelativePath, string $sourceAbsolutePath)
    {
        $this->packageRelativePath = $packageRelativePath;
        $this->dependency          = $dependency;
        $this->sourceAbsolutePath  = $sourceAbsolutePath;
    }

    public function getDependency(): ComposerPackage
    {
        return $this->dependency;
    }

    public function getSourcePath(string $relativeTo = ''): string
    {
        return str_replace($relativeTo, '', $this->sourceAbsolutePath);
    }

    public function getTargetRelativePath(): string
    {
        return $this->dependency->getRelativePath() . $this->packageRelativePath;
    }

    public function isPhpFile(): bool
    {
        return substr($this->sourceAbsolutePath, -4) === '.php';
    }

    public function addNamespace(string $namespaceName): void
    {
    }
    public function addClass(string $className): void
    {
    }
    public function addTrait(string $traitName): void
    {
    }
    // isTrait();

    public function setDoCopy(bool $doCopy): void
    {
        $this->doCopy = $doCopy;
    }
    public function isDoCopy(): bool
    {
        return $this->doCopy;
    }

    public function setDoPrefix(bool $doPrefix): void
    {
    }
    public function isDoPrefix(): bool
    {
        return true;
    }

    /**
     * Used to mark files that are symlinked as not-to-be-deleted.
     *
     * @param bool $doDelete
     *
     * @return void
     */
    public function setDoDelete(bool $doDelete): void
    {
        $this->doDelete = $doDelete;
    }

    /**
     * Should file be deleted?
     *
     * NB: Also respect the "delete_vendor_files"|"delete_vendor_packages" settings.
     */
    public function isDoDelete(): bool
    {
        return $this->doDelete;
    }

    public function setDidDelete(bool $didDelete): void
    {
    }
    public function getDidDelete(): bool
    {
        return false;
    }

    /**
     * Record the autoloader it is found in. Which could be all of them.
     */
    public function addAutoloader(string $autoloaderType): void
    {
        $this->autoloaderTypes = array_unique(array_merge($this->autoloaderTypes, array($autoloaderType)));
    }

    public function isFilesAutoloaderFile(): bool
    {
        return in_array('files', $this->autoloaderTypes, true);
    }

    public function addDiscoveredSymbol(DiscoveredSymbol $symbol): void
    {
        $this->discoveredSymbols[$symbol->getOriginalSymbol()] = $symbol;
    }

    public function getContents(): string
    {

        // TODO: use flysystem
        // $contents = $this->filesystem->read($targetFile);

        $contents = file_get_contents($this->sourceAbsolutePath);
        if (false === $contents) {
            throw new \Exception("Failed to read file at {$this->sourceAbsolutePath}");
        }

        return $contents;
    }
}
