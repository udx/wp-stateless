<?php
/**
 * Copies license files from original folders.
 * Edits Phpdoc to record the file was changed.
 *
 * MIT states: "The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software."
 *
 * GPL states: "You must cause the modified files to carry prominent notices stating
 * that you changed the files and the date of any change."
 *
 * @see https://github.com/coenjacobs/mozart/issues/87
 *
 * @author BrianHenryIE
 */

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\ComposerPackage;
use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\Finder\Finder;

class Licenser
{
    protected string $workingDir;

    protected string $vendorDir;

    /** @var ComposerPackage[]  */
    protected array $dependencies;

    // The author of the current project who is running Strauss to make the changes to the required libraries.
    protected string $author;

    protected string $targetDirectory;

    protected bool $includeModifiedDate;

    /**
     * @see StraussConfig::isIncludeAuthor()
     * @var bool
     */
    protected bool $includeAuthor = true;

    /**
     * An array of files relative to the project vendor folder.
     *
     * @var string[]
     */
    protected array $discoveredLicenseFiles = array();

    /** @var Filesystem */
    protected $filesystem;

    /**
     * Licenser constructor.
     *
     * @param string $workingDir
     * @param ComposerPackage[] $dependencies Whose folders are searched for existing license.txt files.
     * @param string $author To add to each modified file's header
     */
    public function __construct(StraussConfig $config, string $workingDir, array $dependencies, string $author)
    {
        $this->workingDir = $workingDir;
        $this->dependencies = $dependencies;
        $this->author = $author;

        $this->targetDirectory = $config->getTargetDirectory();
        $this->vendorDir = $config->getVendorDirectory();
        $this->includeModifiedDate = $config->isIncludeModifiedDate();
        $this->includeAuthor = $config->isIncludeAuthor();

        $this->filesystem = new Filesystem(new LocalFilesystemAdapter('/'));
    }

    public function copyLicenses(): void
    {
        $this->findLicenseFiles();

        foreach ($this->getDiscoveredLicenseFiles() as $licenseFile) {
            $targetLicenseFile = $this->targetDirectory . $licenseFile;

            $targetLicenseFileDir = dirname($targetLicenseFile);

            // Don't try copy it if it's already there.
            if ($this->filesystem->fileExists($targetLicenseFile)) {
                continue;
            }

            // Don't add licenses to non-existent directories – there were no files copied there!
            if (! is_dir($targetLicenseFileDir)) {
                continue;
            }

            $this->filesystem->copy(
                $this->vendorDir . $licenseFile,
                $targetLicenseFile
            );
        }
    }

    /**
     * @see https://www.phpliveregex.com/p/A5y
     */
    public function findLicenseFiles(?Finder $finder = null): void
    {
        // Include all license files in the dependency path.
        $finder = $finder ?? new Finder();

        /** @var ComposerPackage $dependency */
        foreach ($this->dependencies as $dependency) {
            $packagePath = $dependency->getPackageAbsolutePath();

            // If packages happen to have their vendor dir, i.e. locally required packages, don't included the licenses
            // from their vendor dir (they should be included otherwise anyway).
            // $dependency->getVendorDir()
            $finder->files()->in($packagePath)->followLinks()->exclude(array( 'vendor' ))->name('/^.*licen.e.*/i');

            /** @var \SplFileInfo $foundFile */
            foreach ($finder as $foundFile) {
                $filePath = $foundFile->getPathname();

                // Replace multiple \ and/or / with OS native DIRECTORY_SEPARATOR.
                $filePath = preg_replace('#[\\\/]+#', DIRECTORY_SEPARATOR, $filePath);

                $this->discoveredLicenseFiles[$filePath] = $dependency->getPackageName();
            }
        }
    }

    /**
     * @return string[]
     */
    public function getDiscoveredLicenseFiles(): array
    {
        return array_keys($this->discoveredLicenseFiles);
    }

    /**
     * @param array<string, ComposerPackage> $modifiedFiles
     *
     * @throws \Exception
     */
    public function addInformationToUpdatedFiles(array $modifiedFiles): void
    {
        // E.g. "25-April-2021".
        $date = gmdate("d-F-Y", time());

        foreach ($modifiedFiles as $relativeFilePath => $package) {
            $filepath = $this->workingDir . $this->targetDirectory . $relativeFilePath;

            if (!$this->filesystem->fileExists($filepath)) {
                continue;
            }

            $contents = $this->filesystem->read($filepath);

            $updatedContents = $this->addChangeDeclarationToPhpString(
                $contents,
                $date,
                $package->getPackageName(),
                $package->getLicense()
            );

            if ($updatedContents !== $contents) {
                $this->filesystem->write($filepath, $updatedContents);
            }
        }
    }

    /**
     * Given a php file as a string, edit its header phpdoc, or add a header, to include:
     *
     * "Modified by {author} on {date} using Strauss.
     * @see https://github.com/BrianHenryIE/strauss"
     *
     * Should probably include the original license in each file since it'll often be a mix, with the parent
     * project often being a GPL WordPress plugin.
     *
     * Find the string between the end of php-opener and the first valid code.
     * First valid code will be a line whose first non-whitespace character is not / or * ?... NO!
     * If the first non whitespace string after php-opener is multiline-comment-opener, find the
     * closing multiline-comment-closer
     * / If there's already a comment, work within that comment
     * If there is no mention in the header of the license already, add it.
     * Add a note that changes have been made.
     *
     * @param string $phpString Code.
     */
    public function addChangeDeclarationToPhpString(
        string $phpString,
        string $modifiedDate,
        string $packageName,
        string $packageLicense
    ) : string {

        $author = $this->author;

        $licenseDeclaration = "@license {$packageLicense}";
        $modifiedDeclaration = 'Modified';
        if ($this->includeAuthor) {
            $modifiedDeclaration .= " by {$author}";
        }
        if ($this->includeModifiedDate) {
            $modifiedDeclaration .= " on {$modifiedDate}";
        }
        $straussLink = 'https://github.com/BrianHenryIE/strauss';
        $modifiedDeclaration .= " using {@see {$straussLink}}.";

        $startOfFileArray = [];
        $tokenizeString =  token_get_all($phpString);

        foreach ($tokenizeString as $token) {
            if (is_array($token) && !in_array($token[1], ['namespace', '/*', ' /*'])) {
                $startOfFileArray[] = $token[1];
                $token = array_shift($tokenizeString);

                if (is_array($token) && stristr($token[1], 'strauss')) {
                    return $phpString;
                }
            } elseif (!is_array($token)) {
                $startOfFileArray[] = $token;
            }
        }
        // Not in use yet (because all tests are passing) but the idea of capturing the file header and only editing
        // that seems more reasonable than searching the whole file.
        $startOfFile = implode('', $startOfFileArray);

        // php-open followed by some whitespace and new line until the first ...
        $noCommentBetweenPhpOpenAndFirstCodePattern = '~<\?php[\s\n]*[\w\\\?]+~';

        $multilineCommentCapturePattern = '
            ~                        # Start the pattern
            (
            <\?php[\S\s]*            #  match the beginning of the files php-open and following whitespace
            )
            (
            \*[\S\s.]*               # followed by a multiline-comment-open
            )
            (
            \*/                      # Capture the multiline-comment-close separately
            )
            ~Ux';                          // U: Non-greedy matching, x: ignore whitespace in pattern.

        $replaceInMultilineCommentFunction = function ($matches) use (
            $licenseDeclaration,
            $modifiedDeclaration
        ) {
            // Find the line prefix and use it, i.e. could be none, asterisk or space-asterisk.
            $commentLines = explode("\n", $matches[2]);

            if (isset($commentLines[1])&& 1 === preg_match('/^([\s\\\*]*)/', $commentLines[1], $output_array)) {
                $lineStart = $output_array[1];
            } else {
                $lineStart = ' * ';
            }

            $appendString = "*\n";

            // If the license is not already specified in the header, add it.
            if (false === strpos($matches[2], 'licen')) {
                $appendString .= "{$lineStart}{$licenseDeclaration}\n";
            }

            $appendString .= "{$lineStart}{$modifiedDeclaration}\n";

            $commentEnd =  rtrim(rtrim($lineStart, ' '), '*').'*/';

            $replaceWith = $matches[1] . $matches[2] . $appendString . $commentEnd;

            return $replaceWith;
        };

        // If it's a simple case where there is no existing header, add the existing license.
        if (1 === preg_match($noCommentBetweenPhpOpenAndFirstCodePattern, $phpString)) {
            $modifiedComment = "/**\n * {$licenseDeclaration}\n *\n * {$modifiedDeclaration}\n */";
            $updatedPhpString = preg_replace('~<\?php~', "<?php\n". $modifiedComment, $phpString, 1);
        } else {
            $updatedPhpString = preg_replace_callback(
                $multilineCommentCapturePattern,
                $replaceInMultilineCommentFunction,
                $phpString,
                1
            );
        }

        /**
         * In some cases `preg_replace_callback` returns `null` instead of the string. If that happens, return
         * the original, unaltered string.
         *
         * @see https://github.com/BrianHenryIE/strauss/issues/115
         */
        return $updatedPhpString ?? $phpString;
    }
}
