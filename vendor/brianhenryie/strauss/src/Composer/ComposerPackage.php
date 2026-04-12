<?php
/**
 * Object for getting typed values from composer.json.
 *
 * Use this for dependencies. Use ProjectComposerPackage for the primary composer.json.
 */

namespace BrianHenryIE\Strauss\Composer;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;

/**
 * @phpstan-type AutoloadKey array{files?:array<string>,classmap?:array<string>,"psr-4"?:array<string,string|array<string>>}
 */
class ComposerPackage
{
    /**
     * The composer.json file as parsed by Composer.
     *
     * @see \Composer\Factory::create
     *
     * @var \Composer\Composer
     */
    protected \Composer\Composer $composer;

    /**
     * The name of the project in composer.json.
     *
     * e.g. brianhenryie/my-project
     *
     * @var string
     */
    protected string $packageName;

    /**
     * Virtual packages and meta packages do not have a composer.json.
     * Some packages are installed in a different directory name than their package name.
     *
     * @var ?string
     */
    protected ?string $relativePath = null;

    /**
     * Packages can be symlinked from outside the current project directory.
     *
     * @var ?string
     */
    protected ?string $packageAbsolutePath = null;

    /**
     * The discovered files, classmap, psr0 and psr4 autoload keys discovered (as parsed by Composer).
     *
     * @var AutoloadKey
     */
    protected array $autoload = [];

    /**
     * The names in the composer.json's "requires" field (without versions).
     *
     * @var string[]
     */
    protected array $requiresNames = [];

    protected string $license;

    /**
     * @param string $absolutePath The absolute path to the vendor folder with the composer.json "name",
     *          i.e. the domain/package definition, which is the vendor subdir from where the package's
     *          composer.json should be read.
     * @param ?array{files?:array<string>, classmap?:array<string>, psr?:array<string,string|array<string>>} $overrideAutoload Optional configuration to replace the package's own autoload definition with
     *                                    another which Strauss can use.
     * @return ComposerPackage
     */
    public static function fromFile(string $absolutePath, array $overrideAutoload = null): ComposerPackage
    {
        if (is_dir($absolutePath)) {
            $absolutePath = rtrim($absolutePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer.json';
        }

        $composer = Factory::create(new NullIO(), $absolutePath, true);

        return new ComposerPackage($composer, $overrideAutoload);
    }

    /**
     * This is used for virtual packages, which don't have a composer.json.
     *
     * @param array{name?:string, license?:string, requires?:array<string,string>, autoload?:AutoloadKey} $jsonArray composer.json decoded to array
     * @param ?AutoloadKey $overrideAutoload New autoload rules to replace the existing ones.
     */
    public static function fromComposerJsonArray($jsonArray, array $overrideAutoload = null): ComposerPackage
    {
        $factory = new Factory();
        $io = new NullIO();
        $composer = $factory->createComposer($io, $jsonArray, true);

        return new ComposerPackage($composer, $overrideAutoload);
    }

    /**
     * Create a PHP object to represent a composer package.
     *
     * @param Composer $composer
     * @param ?AutoloadKey $overrideAutoload Optional configuration to replace the package's own autoload definition with another which Strauss can use.
     */
    public function __construct(Composer $composer, array $overrideAutoload = null)
    {
        $this->composer = $composer;

        $this->packageName = $composer->getPackage()->getName();

        $composerJsonFileAbsolute = $composer->getConfig()->getConfigSource()->getName();

        $absolutePath = realpath(dirname($composerJsonFileAbsolute));
        if (false !== $absolutePath) {
            $this->packageAbsolutePath = $absolutePath . DIRECTORY_SEPARATOR;
        }

        $vendorDirectory = $this->composer->getConfig()->get('vendor-dir');
        if (file_exists($vendorDirectory . DIRECTORY_SEPARATOR . $this->packageName)) {
            $this->relativePath = $this->packageName;
            $this->packageAbsolutePath = realpath($vendorDirectory . DIRECTORY_SEPARATOR . $this->packageName) . DIRECTORY_SEPARATOR;
        // If the package is symlinked, the path will be outside the working directory.
        } elseif (0 !== strpos($absolutePath, getcwd()) && 1 === preg_match('/.*[\/\\\\]([^\/\\\\]*[\/\\\\][^\/\\\\]*)[\/\\\\][^\/\\\\]*/', $vendorDirectory, $output_array)) {
            $this->relativePath = $output_array[1];
        } elseif (1 === preg_match('/.*[\/\\\\]([^\/\\\\]+[\/\\\\][^\/\\\\]+)[\/\\\\]composer.json/', $composerJsonFileAbsolute, $output_array)) {
        // Not every package gets installed to a folder matching its name (crewlabs/unsplash).
            $this->relativePath = $output_array[1];
        }

        if (!is_null($overrideAutoload)) {
            $composer->getPackage()->setAutoload($overrideAutoload);
        }

        $this->autoload = $composer->getPackage()->getAutoload();

        foreach ($composer->getPackage()->getRequires() as $_name => $packageLink) {
            $this->requiresNames[] = $packageLink->getTarget();
        }

        // Try to get the license from the package's composer.json, assume proprietary (all rights reserved!).
        $this->license = !empty($composer->getPackage()->getLicense())
            ? implode(',', $composer->getPackage()->getLicense())
            : 'proprietary?';
    }

    /**
     * Composer package project name.
     *
     * vendor/project-name
     *
     * @return string
     */
    public function getPackageName(): string
    {
        return $this->packageName;
    }

    public function getRelativePath(): ?string
    {
        return $this->relativePath . DIRECTORY_SEPARATOR;
    }

    public function getPackageAbsolutePath(): ?string
    {
        return $this->packageAbsolutePath;
    }

    /**
     *
     * e.g. ['psr-4' => [ 'BrianHenryIE\Project' => 'src' ]]
     * e.g. ['psr-4' => [ 'BrianHenryIE\Project' => ['src','lib] ]]
     * e.g. ['classmap' => [ 'src', 'lib' ]]
     * e.g. ['files' => [ 'lib', 'functions.php' ]]
     *
     * @return AutoloadKey
     */
    public function getAutoload(): array
    {
        return $this->autoload;
    }

    /**
     * The names of the packages in the composer.json's "requires" field (without version).
     *
     * Excludes PHP, ext-*, since we won't be copying or prefixing them.
     *
     * @return string[]
     */
    public function getRequiresNames(): array
    {
        // Unset PHP, ext-*.
        $removePhpExt = function ($element) {
            return !( 0 === strpos($element, 'ext') || 'php' === $element );
        };

        return array_filter($this->requiresNames, $removePhpExt);
    }

    public function getLicense():string
    {
        return $this->license;
    }
}
