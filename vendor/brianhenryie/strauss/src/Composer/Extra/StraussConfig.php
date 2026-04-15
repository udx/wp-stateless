<?php
/**
 * The extra/strauss key in composer.json.
 */

namespace BrianHenryIE\Strauss\Composer\Extra;

use Composer\Composer;
use Exception;
use JsonMapper\JsonMapperFactory;
use JsonMapper\Middleware\Rename\Rename;
use Symfony\Component\Console\Input\InputInterface;

class StraussConfig
{
    /**
     * The output directory.
     *
     * Probably `strauss/` or `src/strauss/`.
     *
     * @var string
     */
    protected $targetDirectory = 'vendor-prefixed';

    /**
     * The vendor directory.
     *
     * Probably 'vendor/'
     *
     * @var string
     */
    protected $vendorDirectory = 'vendor';

    /**
     * `namespacePrefix` is the prefix to be given to any namespaces.
     * Presumably this will take the form `My_Project_Namespace\dep_directory`.
     *
     * @link https://www.php-fig.org/psr/psr-4/
     *
     * @var string
     */
    protected string $namespacePrefix;

    /**
     * @var string
     */
    protected string $classmapPrefix;


    /**
     * @var ?string
     */
    protected ?string $constantsPrefix = null;

    /**
     * Should replacements be performed in project files?
     *
     * When null, files in the project's `autoload` key are scanned and changes which have been performed on the
     * vendor packages are reflected in the project files.
     *
     * When an array of relative file paths are provided, the files in those directories are updated.
     *
     * An empty array disables updating project files.
     *
     * @var ?string[]
     */
    protected ?array $updateCallSites = null;

    /**
     * Packages to copy and (maybe) prefix.
     *
     * If this is empty, the "requires" list in the project composer.json is used.
     *
     * @var string[]
     */
    protected array $packages = [];

    /**
     * Back-compatibility with Mozart.
     *
     * @var string[]
     */
    private array $excludePackages;

    /**
     * 'exclude_from_copy' in composer/extra config.
     *
     * @var array{packages: string[], namespaces: string[], file_patterns: string[]}
     */
    protected array $excludeFromCopy = array('file_patterns'=>array(),'namespaces'=>array(),'packages'=>array());

    /**
     * @var array{packages: string[], namespaces: string[], file_patterns: string[]}
     */
    protected array $excludeFromPrefix = array('file_patterns'=>array(),'namespaces'=>array(),'packages'=>array());

    /**
     * An array of autoload keys to replace packages' existing autoload key.
     *
     * e.g. when
     * * A package has no autoloader
     * * A package specified both a PSR-4 and a classmap but only needs one
     * ...
     *
     * @var array<string, array{files?:array<string>,classmap?:array<string>,"psr-4":array<string|array<string>>}>|array{} $overrideAutoload
     */
    protected array $overrideAutoload = [];

    /**
     * After completing prefixing should the source files be deleted?
     * This does not affect symlinked directories.
     */
    protected bool $deleteVendorFiles = false;

    /**
     * After completing prefixing should the source packages be deleted?
     * This does not affect symlinked directories.
     */
    protected bool $deleteVendorPackages = false;

    protected bool $classmapOutput;

    /**
     * A dictionary of regex captures => regex replacements.
     *
     * E.g. used to avoid repetition of the plugin vendor name in namespaces.
     * `"~BrianHenryIE\\\\(.*)~" : "BrianHenryIE\\WC_Cash_App_Gateway\\\\$1"`.
     *
     * @var array<string, string> $namespaceReplacementPatterns
     */
    protected array $namespaceReplacementPatterns = array();

    /**
     * Should a modified date be included in the header for modified files?
     *
     * @var bool
     */
    protected $includeModifiedDate = true;

    /**
     * Should the author name be included in the header for modified files?
     *
     * @var bool
     */
    protected $includeAuthor = true;

    /**
     * Read any existing Mozart config.
     * Overwrite it with any Strauss config.
     * Provide sensible defaults.
     *
     * @param Composer $composer
     *
     * @throws Exception
     */
    public function __construct(Composer $composer)
    {

        $configExtraSettings = null;

        // Backwards compatibility with Mozart.
        if (isset($composer->getPackage()->getExtra()['mozart'])) {
            $configExtraSettings = (object)$composer->getPackage()->getExtra()['mozart'];

            // Default setting for Mozart.
            $this->setDeleteVendorFiles(true);
        }

        if (isset($composer->getPackage()->getExtra()['strauss'])) {
            $configExtraSettings = (object)$composer->getPackage()->getExtra()['strauss'];
        }

        if (!is_null($configExtraSettings)) {
            $mapper = (new JsonMapperFactory())->bestFit();

            $rename = new Rename();
            $rename->addMapping(StraussConfig::class, 'dep_directory', 'targetDirectory');
            $rename->addMapping(StraussConfig::class, 'dep_namespace', 'namespacePrefix');

            $rename->addMapping(StraussConfig::class, 'exclude_packages', 'excludePackages');
            $rename->addMapping(StraussConfig::class, 'delete_vendor_files', 'deleteVendorFiles');
            $rename->addMapping(StraussConfig::class, 'delete_vendor_packages', 'deleteVendorPackages');

            $rename->addMapping(StraussConfig::class, 'exclude_prefix_packages', 'excludePackagesFromPrefixing');

            $mapper->unshift($rename);
            $mapper->push(new \JsonMapper\Middleware\CaseConversion(
                \JsonMapper\Enums\TextNotation::UNDERSCORE(),
                \JsonMapper\Enums\TextNotation::CAMEL_CASE()
            ));

            $mapper->mapObject($configExtraSettings, $this);
        }

        // Defaults.
        // * Use PSR-4 autoloader key
        // * Use PSR-0 autoloader key
        // * Use the package name
        if (! isset($this->namespacePrefix)) {
            if (isset($composer->getPackage()->getAutoload()['psr-4'])) {
                $this->setNamespacePrefix(array_key_first($composer->getPackage()->getAutoload()['psr-4']));
            } elseif (isset($composer->getPackage()->getAutoload()['psr-0'])) {
                $this->setNamespacePrefix(array_key_first($composer->getPackage()->getAutoload()['psr-0']));
            } elseif ('__root__' !== $composer->getPackage()->getName()) {
                $packageName = $composer->getPackage()->getName();
                $namespacePrefix = preg_replace('/[^\w\/]+/', '_', $packageName);
                $namespacePrefix = str_replace('/', '\\', $namespacePrefix) . '\\';
                $namespacePrefix = preg_replace_callback('/(?<=^|_|\\\\)[a-z]/', function ($match) {
                    return strtoupper($match[0]);
                }, $namespacePrefix);
                $this->setNamespacePrefix($namespacePrefix);
            } elseif (isset($this->classmapPrefix)) {
                $namespacePrefix = rtrim($this->getClassmapPrefix(), '_');
                $this->setNamespacePrefix($namespacePrefix);
            }
        }

        if (! isset($this->classmapPrefix)) {
            if (isset($composer->getPackage()->getAutoload()['psr-4'])) {
                $autoloadKey = array_key_first($composer->getPackage()->getAutoload()['psr-4']);
                $classmapPrefix = str_replace("\\", "_", $autoloadKey);
                $this->setClassmapPrefix($classmapPrefix);
            } elseif (isset($composer->getPackage()->getAutoload()['psr-0'])) {
                $autoloadKey = array_key_first($composer->getPackage()->getAutoload()['psr-0']);
                $classmapPrefix = str_replace("\\", "_", $autoloadKey);
                $this->setClassmapPrefix($classmapPrefix);
            } elseif ('__root__' !== $composer->getPackage()->getName()) {
                $packageName = $composer->getPackage()->getName();
                $classmapPrefix = preg_replace('/[^\w\/]+/', '_', $packageName);
                $classmapPrefix = str_replace('/', '\\', $classmapPrefix);
                // Uppercase the first letter of each word.
                $classmapPrefix = preg_replace_callback('/(?<=^|_|\\\\)[a-z]/', function ($match) {
                    return strtoupper($match[0]);
                }, $classmapPrefix);
                $classmapPrefix = str_replace("\\", "_", $classmapPrefix);
                $this->setClassmapPrefix($classmapPrefix);
            } elseif (isset($this->namespacePrefix)) {
                $classmapPrefix = preg_replace('/[^\w\/]+/', '_', $this->getNamespacePrefix());
                $classmapPrefix = rtrim($classmapPrefix, '_') . '_';
                $this->setClassmapPrefix($classmapPrefix);
            }
        }

        if (!isset($this->namespacePrefix) || !isset($this->classmapPrefix)) {
            throw new Exception('Prefix not set. Please set `namespace_prefix`, `classmap_prefix` in composer.json/extra/strauss.');
        }

        if (empty($this->packages)) {
            $this->packages = array_map(function (\Composer\Package\Link $element) {
                return $element->getTarget();
            }, $composer->getPackage()->getRequires());
        }

        // If the bool flag for classmapOutput wasn't set in the Json config.
        if (!isset($this->classmapOutput)) {
            $this->classmapOutput = true;
            // Check each autoloader.
            foreach ($composer->getPackage()->getAutoload() as $autoload) {
                // To see if one of its paths.
                foreach ($autoload as $entry) {
                    $paths = (array) $entry;
                    foreach ($paths as $path) {
                        // Matches the target directory.
                        if (trim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR === $this->getTargetDirectory()) {
                            $this->classmapOutput = false;
                            break 3;
                        }
                    }
                }
            }
        }

        // TODO: Throw an exception if any regex patterns in config are invalid.
        // https://stackoverflow.com/questions/4440626/how-can-i-validate-regex
        // preg_match('~Valid(Regular)Expression~', null) === false);

        if (isset($configExtraSettings->updateCallSites)) {
            if (true === $configExtraSettings->updateCallSites) {
                $this->updateCallSites = null;
            } elseif (false === $configExtraSettings->updateCallSites) {
                $this->updateCallSites = array();
            } elseif (is_array($configExtraSettings->updateCallSites)) {
                $this->updateCallSites = $configExtraSettings->updateCallSites;
            } else {
                // uh oh.
            }
        }
    }

    /**
     * `target_directory` will always be returned without a leading slash and with a trailing slash.
     *
     * @return string
     */
    public function getTargetDirectory(): string
    {
        return trim($this->targetDirectory, DIRECTORY_SEPARATOR . '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $targetDirectory
     */
    public function setTargetDirectory(string $targetDirectory): void
    {
        $this->targetDirectory = trim(
            preg_replace(
                '/[\/\\\\]+/',
                DIRECTORY_SEPARATOR,
                $targetDirectory
            ),
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR ;
    }

    /**
     * @return string
     */
    public function getVendorDirectory(): string
    {
        return trim($this->vendorDirectory, DIRECTORY_SEPARATOR . '\\/') . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $vendorDirectory
     */
    public function setVendorDirectory(string $vendorDirectory): void
    {
        $this->vendorDirectory = $vendorDirectory;
    }

    /**
     * @return string
     */
    public function getNamespacePrefix(): string
    {
        return trim($this->namespacePrefix, '\\');
    }

    /**
     * @param string $namespacePrefix
     */
    public function setNamespacePrefix(string $namespacePrefix): void
    {
        $this->namespacePrefix = $namespacePrefix;
    }

    /**
     * @return string
     */
    public function getClassmapPrefix(): string
    {
        return $this->classmapPrefix;
    }

    /**
     * @param string $classmapPrefix
     */
    public function setClassmapPrefix(string $classmapPrefix): void
    {
        $this->classmapPrefix = $classmapPrefix;
    }

    /**
     * @return string
     */
    public function getConstantsPrefix(): ?string
    {
        return $this->constantsPrefix;
    }

    /**
     * @param string $constantsPrefix
     */
    public function setConstantsPrefix(string $constantsPrefix): void
    {
        $this->constantsPrefix = $constantsPrefix;
    }

    /**
     * List of files and directories to update call sites in. Empty to disable. Null infers from the project's autoload key.
     *
     * @return string[]|null
     */
    public function getUpdateCallSites(): ?array
    {
        return $this->updateCallSites;
    }

    /**
     * @param string[]|null $updateCallSites
     */
    public function setUpdateCallSites(?array $updateCallSites): void
    {
        $this->updateCallSites = $updateCallSites;
    }

    /**
     * @param array{packages?:array<string>, namespaces?:array<string>, file_patterns?:array<string>} $excludeFromCopy
     */
    public function setExcludeFromCopy(array $excludeFromCopy): void
    {
        foreach (array( 'packages', 'namespaces', 'file_patterns' ) as $key) {
            if (isset($excludeFromCopy[$key])) {
                $this->excludeFromCopy[$key] = $excludeFromCopy[$key];
            }
        }
    }

    /**
     * @return string[]
     */
    public function getExcludePackagesFromCopy(): array
    {
        return $this->excludeFromCopy['packages'] ?? array();
    }

    /**
     * @return string[]
     */
    public function getExcludeNamespacesFromCopy(): array
    {
        return $this->excludeFromCopy['namespaces'] ?? array();
    }

    /**
     * @return string[]
     */
    public function getExcludeFilePatternsFromCopy(): array
    {
        return $this->excludeFromCopy['file_patterns'] ?? array();
    }

    /**
     * @param array{packages?:array<string>, namespaces?:array<string>, file_patterns?:array<string>} $excludeFromPrefix
     */
    public function setExcludeFromPrefix(array $excludeFromPrefix): void
    {
        if (isset($excludeFromPrefix['packages'])) {
            $this->excludeFromPrefix['packages'] = $excludeFromPrefix['packages'];
        }
        if (isset($excludeFromPrefix['namespaces'])) {
            $this->excludeFromPrefix['namespaces'] = $excludeFromPrefix['namespaces'];
        }
        if (isset($excludeFromPrefix['file_patterns'])) {
            $this->excludeFromPrefix['file_patterns'] = $excludeFromPrefix['file_patterns'];
        }
    }

    /**
     * When prefixing, do not prefix these packages (which have been copied).
     *
     * @return string[]
     */
    public function getExcludePackagesFromPrefixing(): array
    {
        return $this->excludeFromPrefix['packages'] ?? array();
    }

    /**
     * @param string[] $excludePackagesFromPrefixing
     */
    public function setExcludePackagesFromPrefixing(array $excludePackagesFromPrefixing): void
    {
        $this->excludeFromPrefix['packages'] = $excludePackagesFromPrefixing;
    }

    /**
     * @return string[]
     */
    public function getExcludeNamespacesFromPrefixing(): array
    {
        return $this->excludeFromPrefix['namespaces'] ?? array();
    }

    /**
     * @return string[]
     */
    public function getExcludeFilePatternsFromPrefixing(): array
    {
        return $this->excludeFromPrefix['file_patterns'] ?? array();
    }


    /**
     * @return array{}|array<string, array{files?:array<string>,classmap?:array<string>,"psr-4":array<string|array<string>>}> $overrideAutoload Dictionary of package name: autoload rules.
     */
    public function getOverrideAutoload(): array
    {
        return $this->overrideAutoload;
    }

    /**
     * @param array<string, array{files?:array<string>,classmap?:array<string>,"psr-4":array<string|array<string>>}> $overrideAutoload Dictionary of package name: autoload rules.
     */
    public function setOverrideAutoload(array $overrideAutoload): void
    {
        $this->overrideAutoload = $overrideAutoload;
    }

    /**
     * @return bool
     */
    public function isDeleteVendorFiles(): bool
    {
        return $this->deleteVendorFiles;
    }

    /**
     * @return bool
     */
    public function isDeleteVendorPackages(): bool
    {
        return $this->deleteVendorPackages;
    }

    /**
     * @param bool $deleteVendorFiles
     */
    public function setDeleteVendorFiles(bool $deleteVendorFiles): void
    {
        $this->deleteVendorFiles = $deleteVendorFiles;
    }

    /**
     * @param bool $deleteVendorPackages
     */
    public function setDeleteVendorPackages(bool $deleteVendorPackages): void
    {
        $this->deleteVendorPackages = $deleteVendorPackages;
    }

    /**
     * @return string[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * @param string[] $packages
     */
    public function setPackages(array $packages): void
    {
        $this->packages = $packages;
    }

    /**
     * @return bool
     */
    public function isClassmapOutput(): bool
    {
        return $this->classmapOutput;
    }

    /**
     * @param bool $classmapOutput
     */
    public function setClassmapOutput(bool $classmapOutput): void
    {
        $this->classmapOutput = $classmapOutput;
    }

    /**
     * Backwards compatibility with Mozart.
     *
     * @param string[] $excludePackages
     */
    public function setExcludePackages(array $excludePackages): void
    {
        $this->excludeFromPrefix['packages'] = $excludePackages;
    }


    /**
     * @return array<string,string>
     */
    public function getNamespaceReplacementPatterns(): array
    {
        return $this->namespaceReplacementPatterns;
    }

    /**
     * @param array<string,string> $namespaceReplacementPatterns
     */
    public function setNamespaceReplacementPatterns(array $namespaceReplacementPatterns): void
    {
        $this->namespaceReplacementPatterns = $namespaceReplacementPatterns;
    }

    /**
     * @return bool
     */
    public function isIncludeModifiedDate(): bool
    {
        return $this->includeModifiedDate;
    }

    /**
     * @param bool $includeModifiedDate
     */
    public function setIncludeModifiedDate(bool $includeModifiedDate): void
    {
        $this->includeModifiedDate = $includeModifiedDate;
    }


    /**
     * @return bool
     */
    public function isIncludeAuthor(): bool
    {
        return $this->includeAuthor;
    }

    /**
     * @param bool $includeAuthor
     */
    public function setIncludeAuthor(bool $includeAuthor): void
    {
        $this->includeAuthor = $includeAuthor;
    }

    /**
     * @param InputInterface $input To access the command line options.
     */
    public function updateFromCli(InputInterface $input): void
    {

        // strauss --updateCallSites=false (default)
        // strauss --updateCallSites=true
        // strauss --updateCallSites=src,input,extra

        if ($input->hasOption('updateCallSites') && $input->getOption('updateCallSites') !== null) {
            $updateCallSitesInput = $input->getOption('updateCallSites');

            if ('false' === $updateCallSitesInput) {
                $this->updateCallSites = array();
            } elseif ('true' === $updateCallSitesInput) {
                $this->updateCallSites = null;
            } elseif (! is_null($updateCallSitesInput)) {
                $this->updateCallSites = explode(',', $updateCallSitesInput);
            }
        }

        if ($input->hasOption('deleteVendorPackages') && $input->getOption('deleteVendorPackages') !== null) {
            $isDeleteVendorPackagesCommandLine = $input->getOption('deleteVendorPackages') === 'true';
            $this->setDeleteVendorPackages($isDeleteVendorPackagesCommandLine);
        } elseif ($input->hasOption('delete_vendor_packages') && $input->getOption('delete_vendor_packages') !== null) {
            $isDeleteVendorPackagesCommandLine = $input->getOption('delete_vendor_packages') === 'true';
            $this->setDeleteVendorPackages($isDeleteVendorPackagesCommandLine);
        }
    }
}
