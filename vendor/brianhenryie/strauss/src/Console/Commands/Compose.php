<?php

namespace BrianHenryIE\Strauss\Console\Commands;

use BrianHenryIE\Strauss\ChangeEnumerator;
use BrianHenryIE\Strauss\FileScanner;
use BrianHenryIE\Strauss\Autoload;
use BrianHenryIE\Strauss\Cleanup;
use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\ProjectComposerPackage;
use BrianHenryIE\Strauss\Copier;
use BrianHenryIE\Strauss\DependenciesEnumerator;
use BrianHenryIE\Strauss\DiscoveredFiles;
use BrianHenryIE\Strauss\DiscoveredSymbols;
use BrianHenryIE\Strauss\FileEnumerator;
use BrianHenryIE\Strauss\Licenser;
use BrianHenryIE\Strauss\Prefixer;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class Compose extends Command
{
    use LoggerAwareTrait;

    /** @var string */
    protected string $workingDir;

    /** @var StraussConfig */
    protected StraussConfig $config;

    protected ProjectComposerPackage $projectComposerPackage;

    /** @var Prefixer */
    protected Prefixer $replacer;

    protected DependenciesEnumerator $dependenciesEnumerator;

    /** @var ComposerPackage[] */
    protected array $flatDependencyTree = [];

    /**
     * ArrayAccess of \BrianHenryIE\Strauss\File objects indexed by their path relative to the output target directory.
     *
     * Each object contains the file's relative and absolute paths, the package and autoloaders it came from,
     * and flags indicating should it / has it been copied / deleted etc.
     *
     */
    protected DiscoveredFiles $discoveredFiles;
    protected DiscoveredSymbols $discoveredSymbols;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName('compose');
        $this->setDescription("Copy composer's `require` and prefix their namespace and classnames.");
        $this->setHelp('');

        $this->addOption(
            'updateCallSites',
            null,
            InputArgument::OPTIONAL,
            'Should replacements also be performed in project files? true|list,of,paths|false'
        );

        $this->addOption(
            'deleteVendorPackages',
            null,
            InputArgument::OPTIONAL,
            'Should original packages be deleted after copying? true|false'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @see Command::execute()
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setLogger(
            new ConsoleLogger(
                $output,
                [ LogLevel::INFO => OutputInterface::VERBOSITY_NORMAL ]
            )
        );

        $workingDir       = getcwd() . DIRECTORY_SEPARATOR;
        $this->workingDir = $workingDir;

        try {
            $this->loadProjectComposerPackage();
            $this->loadConfigFromComposerJson();
            $this->updateConfigFromCli($input);

            $this->buildDependencyList();

            $this->enumerateFiles();

            $this->copyFiles();

            $this->determineChanges();

            $this->performReplacements();

            $this->performReplacementsInComposerFiles();

            $this->performReplacementsInProjectFiles();

            $this->addLicenses();

            $this->generateAutoloader();

            $this->cleanUp();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        return Command::SUCCESS;
    }


    /**
     * 1. Load the composer.json.
     *
     * @throws Exception
     */
    protected function loadProjectComposerPackage(): void
    {
        $this->logger->info('Loading package...');

        $this->projectComposerPackage = new ProjectComposerPackage($this->workingDir);

        // TODO: Print the config that Strauss is using.
        // Maybe even highlight what is default config and what is custom config.
    }

    protected function loadConfigFromComposerJson(): void
    {
        $this->logger->info('Loading composer.json config...');

        $this->config = $this->projectComposerPackage->getStraussConfig();
    }

    protected function updateConfigFromCli(InputInterface $input): void
    {
        $this->logger->info('Loading cli config...');

        $this->config->updateFromCli($input);
    }

    /**
     * 2. Built flat list of packages and dependencies.
     *
     * 2.1 Initiate getting dependencies for the project composer.json.
     *
     * @see Compose::flatDependencyTree
     */
    protected function buildDependencyList(): void
    {
        $this->logger->info('Building dependency list...');

        $this->dependenciesEnumerator = new DependenciesEnumerator(
            $this->workingDir,
            $this->config
        );
        $this->flatDependencyTree = $this->dependenciesEnumerator->getAllDependencies();

        // TODO: Print the dependency tree that Strauss has determined.
    }

    protected function enumerateFiles(): void
    {
        $this->logger->info('Enumerating files...');

        $fileEnumerator = new FileEnumerator(
            $this->flatDependencyTree,
            $this->workingDir,
            $this->config
        );

        $this->discoveredFiles = $fileEnumerator->compileFileList();
    }

    // 3. Copy autoloaded files for each
    protected function copyFiles(): void
    {
        if ($this->config->getTargetDirectory() === $this->config->getVendorDirectory()) {
            // Nothing to do.
            return;
        }

        $this->logger->info('Copying files...');

        $copier = new Copier(
            $this->discoveredFiles,
            $this->workingDir,
            $this->config
        );

        $copier->prepareTarget();
        $copier->copy();
    }

    // 4. Determine namespace and classname changes
    protected function determineChanges(): void
    {
        $this->logger->info('Determining changes...');

        $fileScanner = new FileScanner($this->config);

        $this->discoveredSymbols = $fileScanner->findInFiles($this->discoveredFiles);

        $changeEnumerator = new ChangeEnumerator(
            $this->config,
            $this->workingDir
        );
        $changeEnumerator->determineReplacements($this->discoveredSymbols);
    }

    // 5. Update namespaces and class names.
    // Replace references to updated namespaces and classnames throughout the dependencies.
    protected function performReplacements(): void
    {
        $this->logger->info('Performing replacements...');

        $this->replacer = new Prefixer($this->config, $this->workingDir);

        $phpFiles = $this->discoveredFiles->getPhpFilesAndDependencyList();

        $this->replacer->replaceInFiles($this->discoveredSymbols, $phpFiles);
    }

    protected function performReplacementsInComposerFiles(): void
    {
        if ($this->config->getTargetDirectory() !== $this->config->getVendorDirectory()) {
            // Nothing to do.
            return;
        }

        $projectReplace = new Prefixer($this->config, $this->workingDir);

        $fileEnumerator = new FileEnumerator(
            $this->flatDependencyTree,
            $this->workingDir,
            $this->config
        );

        $composerPhpFileRelativePaths = $fileEnumerator->findFilesInDirectory(
            $this->workingDir,
            $this->config->getVendorDirectory() . 'composer'
        );

        $projectReplace->replaceInProjectFiles($this->discoveredSymbols, $composerPhpFileRelativePaths);
    }

    protected function performReplacementsInProjectFiles(): void
    {

        $callSitePaths =
            $this->config->getUpdateCallSites()
            ?? $this->projectComposerPackage->getFlatAutoloadKey();

        if (empty($callSitePaths)) {
            return;
        }

        $projectReplace = new Prefixer($this->config, $this->workingDir);

        $fileEnumerator = new FileEnumerator(
            $this->flatDependencyTree,
            $this->workingDir,
            $this->config
        );

        $phpFilesRelativePaths = [];
        foreach ($callSitePaths as $relativePath) {
            $absolutePath = $this->workingDir . $relativePath;
            if (is_dir($absolutePath)) {
                $phpFilesRelativePaths = array_merge($phpFilesRelativePaths, $fileEnumerator->findFilesInDirectory($this->workingDir, $relativePath));
            } elseif (is_readable($absolutePath)) {
                $phpFilesRelativePaths[] = $relativePath;
            } else {
                $this->logger->warning('Expected file not found from project autoload: ' . $absolutePath);
            }
        }

        $projectReplace->replaceInProjectFiles($this->discoveredSymbols, $phpFilesRelativePaths);
    }

    protected function writeClassAliasMap(): void
    {
    }

    protected function addLicenses(): void
    {
        $this->logger->info('Adding licenses...');

        $author = $this->projectComposerPackage->getAuthor();

        $dependencies = $this->flatDependencyTree;

        $licenser = new Licenser($this->config, $this->workingDir, $dependencies, $author);

        $licenser->copyLicenses();

        $modifiedFiles = $this->replacer->getModifiedFiles();
        $licenser->addInformationToUpdatedFiles($modifiedFiles);
    }

    /**
     * 6. Generate autoloader.
     */
    protected function generateAutoloader(): void
    {
        if ($this->config->getTargetDirectory() === $this->config->getVendorDirectory()) {
            $this->logger->info('Skipping autoloader generation as target directory is vendor directory.');
            return;
        }
        if (isset($this->projectComposerPackage->getAutoload()['classmap'])
            && in_array(
                $this->config->getTargetDirectory(),
                $this->projectComposerPackage->getAutoload()['classmap'],
                true
            )
        ) {
            $this->logger->info('Skipping autoloader generation as target directory is in Composer classmap. Run `composer dump-autoload`.');
            return;
        }

        $this->logger->info('Generating autoloader...');

        $allFilesAutoloaders = $this->dependenciesEnumerator->getAllFilesAutoloaders();
        $filesAutoloaders = array();
        foreach ($allFilesAutoloaders as $packageName => $packageFilesAutoloader) {
            if (in_array($packageName, $this->config->getExcludePackagesFromCopy())) {
                continue;
            }
            $filesAutoloaders[$packageName] = $packageFilesAutoloader;
        }

        $classmap = new Autoload($this->config, $this->workingDir, $filesAutoloaders);

        $classmap->generate();
    }

    /**
     * When namespaces are prefixed which are used by by require and require-dev dependencies,
     * the require-dev dependencies need class aliases specified to point to the new class names/namespaces.
     */
    protected function generateClassAliasList(): void
    {
    }

    /**
     * 7.
     * Delete source files if desired.
     * Delete empty directories in destination.
     */
    protected function cleanUp(): void
    {
        if ($this->config->getTargetDirectory() === $this->config->getVendorDirectory()) {
            // Nothing to do.
            return;
        }

        $this->logger->info('Cleaning up...');

        $cleanup = new Cleanup($this->config, $this->workingDir);

        $sourceFiles = array_keys($this->discoveredFiles->getAllFilesAndDependencyList());

        // TODO: For files autoloaders, delete the contents of the file, not the file itself.

        // This will check the config to check should it delete or not.
        $cleanup->cleanup($sourceFiles);
    }
}
