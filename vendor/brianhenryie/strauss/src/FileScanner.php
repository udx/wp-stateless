<?php
/**
 * The purpose of this class is only to find changes that should be made.
 * i.e. classes and namespaces to change.
 * Those recorded are updated in a later step.
 */

namespace BrianHenryIE\Strauss;

use BrianHenryIE\Strauss\Composer\Extra\StraussConfig;
use BrianHenryIE\Strauss\Types\ClassSymbol;
use BrianHenryIE\Strauss\Types\ConstantSymbol;
use BrianHenryIE\Strauss\Types\NamespaceSymbol;

class FileScanner
{

    protected string $namespacePrefix;
    protected string $classmapPrefix;

    /** @var string[]  */
    protected array $excludeNamespacesFromPrefixing = array();

    /** @var string[]  */
    protected array $excludeFilePatternsFromPrefixing = array();

    /** @var string[]  */
    protected array $namespaceReplacementPatterns = array();

    protected DiscoveredSymbols $discoveredSymbols;

    /**
     * @var string[]
     */
    protected array $excludePackagesFromPrefixing;

    /**
     * FileScanner constructor.
     * @param \BrianHenryIE\Strauss\Composer\Extra\StraussConfig $config
     */
    public function __construct(StraussConfig $config)
    {
        $this->discoveredSymbols = new DiscoveredSymbols();

        $this->namespacePrefix = $config->getNamespacePrefix();
        $this->classmapPrefix = $config->getClassmapPrefix();

        $this->excludePackagesFromPrefixing = $config->getExcludePackagesFromPrefixing();
        $this->excludeNamespacesFromPrefixing = $config->getExcludeNamespacesFromPrefixing();
        $this->excludeFilePatternsFromPrefixing = $config->getExcludeFilePatternsFromPrefixing();

        $this->namespaceReplacementPatterns = $config->getNamespaceReplacementPatterns();
    }

    /**
     * @param DiscoveredFiles $files
     */
    public function findInFiles(DiscoveredFiles $files): DiscoveredSymbols
    {
        foreach ($files->getFiles() as $file) {
            if (!$file->isPhpFile()) {
                continue;
            }

            $this->find($file->getContents(), $file);
        }

        return $this->discoveredSymbols;
    }


    /**
     * TODO: Don't use preg_replace_callback!
     *
     * @uses self::addDiscoveredNamespaceChange()
     * @uses self::addDiscoveredClassChange()
     *
     * @param string $contents
     */
    protected function find(string $contents, File $file): void
    {
        // If the entire file is under one namespace, all we want is the namespace.
        // If there were more than one namespace, it would appear as `namespace MyNamespace { ...`,
        // a file with only a single namespace will appear as `namespace MyNamespace;`.
        $singleNamespacePattern = '/
            (<?php|\r\n|\n)                                              # A new line or the beginning of the file.
            \s*                                                          # Allow whitespace before
            namespace\s+(?<namespace>[0-9A-Za-z_\x7f-\xff\\\\]+)[\s\S]*; # Match a single namespace in the file.
        /x'; //  # x: ignore whitespace in regex.
        if (1 === preg_match($singleNamespacePattern, $contents, $matches)) {
            $this->addDiscoveredNamespaceChange($matches['namespace'], $file);
            return;
        }

        if (0 < preg_match_all('/\s*define\s*\(\s*["\']([^"\']*)["\']\s*,\s*["\'][^"\']*["\']\s*\)\s*;/', $contents, $constants)) {
            foreach ($constants[1] as $constant) {
                $constantObj = new ConstantSymbol($constant, $file);
                $this->discoveredSymbols->add($constantObj);
            }
        }

        // TODO traits

        // TODO: Is the ";" in this still correct since it's being taken care of in the regex just above?
        // Looks like with the preceding regex, it will never match.


        preg_replace_callback(
            '
			~											# Start the pattern
				[\r\n]+\s*namespace\s+([a-zA-Z0-9_\x7f-\xff\\\\]+)[;{\s\n]{1}[\s\S]*?(?=namespace|$) 
														# Look for a preceding namespace declaration, 
														# followed by a semicolon, open curly bracket, space or new line
														# up until a 
														# potential second namespace declaration or end of file.
														# if found, match that much before continuing the search on
				|										# the remainder of the string.
				\/\*[\s\S]*?\*\/ |                      # Skip multiline comments
				^\s*\/\/.*$	|   						# Skip single line comments
				\s*										# Whitespace is allowed before 
				(?:abstract\sclass|class|interface)\s+	# Look behind for class, abstract class, interface
				([a-zA-Z0-9_\x7f-\xff]+)				# Match the word until the first non-classname-valid character
				\s?										# Allow a space after
				(?:{|extends|implements|\n|$)			# Class declaration can be followed by {, extends, implements 
														# or a new line
			~x', //                                     # x: ignore whitespace in regex.
            function ($matches) use ($file) {

                // If we're inside a namespace other than the global namespace:
                if (1 === preg_match('/^\s*namespace\s+[a-zA-Z0-9_\x7f-\xff\\\\]+[;{\s\n]{1}.*/', $matches[0])) {
                    $this->addDiscoveredNamespaceChange($matches[1], $file);

                    return $matches[0];
                }

                if (count($matches) < 3) {
                    return $matches[0];
                }

                // TODO: Why is this [2] and not [1] (which seems to be always empty).
                $this->addDiscoveredClassChange($matches[2], $file);

                return $matches[0];
            },
            $contents
        );
    }

    protected function addDiscoveredClassChange(string $classname, File $file): void
    {

        if ('ReturnTypeWillChange' === $classname) {
            return;
        }
        if (in_array($classname, $this->getBuiltIns())) {
            return;
        }

        $classSymbol = new ClassSymbol($classname, $file);
        $this->discoveredSymbols->add($classSymbol);
    }

    protected function addDiscoveredNamespaceChange(string $namespace, File $file): void
    {

        foreach ($this->excludeNamespacesFromPrefixing as $excludeNamespace) {
            if (0 === strpos($namespace, $excludeNamespace)) {
                return;
            }
        }

        $namespaceObj = new NamespaceSymbol($namespace, $file);
        $this->discoveredSymbols->add($namespaceObj);
    }

    /**
     * Get a list of PHP built-in classes etc. so they are not prefixed.
     *
     * Polyfilled classes were being prefixed, but the polyfills are only active when the PHP version is below X,
     * so calls to those prefixed polyfilled classnames would fail on newer PHP versions.
     *
     * NB: This list is not exhaustive. Any unloaded PHP extensions are not included.
     *
     * @see https://github.com/BrianHenryIE/strauss/issues/79
     *
     * ```
     * array_filter(
     *   get_declared_classes(),
     *   function(string $className): bool {
     *     $reflector = new \ReflectionClass($className);
     *     return empty($reflector->getFileName());
     *   }
     * );
     * ```
     *
     * @return string[]
     */
    protected function getBuiltIns(): array
    {
        $builtins = [
                '7.4' =>
                    [
                        'classes' =>
                            [
                                'AppendIterator',
                                'ArgumentCountError',
                                'ArithmeticError',
                                'ArrayIterator',
                                'ArrayObject',
                                'AssertionError',
                                'BadFunctionCallException',
                                'BadMethodCallException',
                                'CURLFile',
                                'CachingIterator',
                                'CallbackFilterIterator',
                                'ClosedGeneratorException',
                                'Closure',
                                'Collator',
                                'CompileError',
                                'DOMAttr',
                                'DOMCdataSection',
                                'DOMCharacterData',
                                'DOMComment',
                                'DOMConfiguration',
                                'DOMDocument',
                                'DOMDocumentFragment',
                                'DOMDocumentType',
                                'DOMDomError',
                                'DOMElement',
                                'DOMEntity',
                                'DOMEntityReference',
                                'DOMErrorHandler',
                                'DOMException',
                                'DOMImplementation',
                                'DOMImplementationList',
                                'DOMImplementationSource',
                                'DOMLocator',
                                'DOMNameList',
                                'DOMNameSpaceNode',
                                'DOMNamedNodeMap',
                                'DOMNode',
                                'DOMNodeList',
                                'DOMNotation',
                                'DOMProcessingInstruction',
                                'DOMStringExtend',
                                'DOMStringList',
                                'DOMText',
                                'DOMTypeinfo',
                                'DOMUserDataHandler',
                                'DOMXPath',
                                'DateInterval',
                                'DatePeriod',
                                'DateTime',
                                'DateTimeImmutable',
                                'DateTimeZone',
                                'Directory',
                                'DirectoryIterator',
                                'DivisionByZeroError',
                                'DomainException',
                                'EmptyIterator',
                                'Error',
                                'ErrorException',
                                'Exception',
                                'FFI',
                                'FFI\\CData',
                                'FFI\\CType',
                                'FFI\\Exception',
                                'FFI\\ParserException',
                                'FilesystemIterator',
                                'FilterIterator',
                                'GMP',
                                'Generator',
                                'GlobIterator',
                                'HashContext',
                                'InfiniteIterator',
                                'IntlBreakIterator',
                                'IntlCalendar',
                                'IntlChar',
                                'IntlCodePointBreakIterator',
                                'IntlDateFormatter',
                                'IntlException',
                                'IntlGregorianCalendar',
                                'IntlIterator',
                                'IntlPartsIterator',
                                'IntlRuleBasedBreakIterator',
                                'IntlTimeZone',
                                'InvalidArgumentException',
                                'IteratorIterator',
                                'JsonException',
                                'LengthException',
                                'LibXMLError',
                                'LimitIterator',
                                'Locale',
                                'LogicException',
                                'MessageFormatter',
                                'MultipleIterator',
                                'NoRewindIterator',
                                'Normalizer',
                                'NumberFormatter',
                                'OutOfBoundsException',
                                'OutOfRangeException',
                                'OverflowException',
                                'PDO',
                                'PDOException',
                                'PDORow',
                                'PDOStatement',
                                'ParentIterator',
                                'ParseError',
                                'Phar',
                                'PharData',
                                'PharException',
                                'PharFileInfo',
                                'RangeException',
                                'RecursiveArrayIterator',
                                'RecursiveCachingIterator',
                                'RecursiveCallbackFilterIterator',
                                'RecursiveDirectoryIterator',
                                'RecursiveFilterIterator',
                                'RecursiveIteratorIterator',
                                'RecursiveRegexIterator',
                                'RecursiveTreeIterator',
                                'Reflection',
                                'ReflectionClass',
                                'ReflectionClassConstant',
                                'ReflectionException',
                                'ReflectionExtension',
                                'ReflectionFunction',
                                'ReflectionFunctionAbstract',
                                'ReflectionGenerator',
                                'ReflectionMethod',
                                'ReflectionNamedType',
                                'ReflectionObject',
                                'ReflectionParameter',
                                'ReflectionProperty',
                                'ReflectionReference',
                                'ReflectionType',
                                'ReflectionZendExtension',
                                'RegexIterator',
                                'ResourceBundle',
                                'RuntimeException',
                                'SQLite3',
                                'SQLite3Result',
                                'SQLite3Stmt',
                                'SessionHandler',
                                'SimpleXMLElement',
                                'SimpleXMLIterator',
                                'SoapClient',
                                'SoapFault',
                                'SoapHeader',
                                'SoapParam',
                                'SoapServer',
                                'SoapVar',
                                'SodiumException',
                                'SplDoublyLinkedList',
                                'SplFileInfo',
                                'SplFileObject',
                                'SplFixedArray',
                                'SplHeap',
                                'SplMaxHeap',
                                'SplMinHeap',
                                'SplObjectStorage',
                                'SplPriorityQueue',
                                'SplQueue',
                                'SplStack',
                                'SplTempFileObject',
                                'Spoofchecker',
                                'Transliterator',
                                'TypeError',
                                'UConverter',
                                'UnderflowException',
                                'UnexpectedValueException',
                                'WeakReference',
                                'XMLReader',
                                'XMLWriter',
                                'XSLTProcessor',
                                'ZipArchive',
                                '__PHP_Incomplete_Class',
                                'finfo',
                                'mysqli',
                                'mysqli_driver',
                                'mysqli_result',
                                'mysqli_sql_exception',
                                'mysqli_stmt',
                                'mysqli_warning',
                                'php_user_filter',
                                'stdClass',
                                'tidy',
                                'tidyNode',
                            ],
                        'interfaces' =>
                            [
                                'ArrayAccess',
                                'Countable',
                                'DateTimeInterface',
                                'Iterator',
                                'IteratorAggregate',
                                'JsonSerializable',
                                'OuterIterator',
                                'RecursiveIterator',
                                'Reflector',
                                'SeekableIterator',
                                'Serializable',
                                'SessionHandlerInterface',
                                'SessionIdInterface',
                                'SessionUpdateTimestampHandlerInterface',
                                'SplObserver',
                                'SplSubject',
                                'Throwable',
                                'Traversable',
                            ],
                        'traits' =>
                            [
                            ],
                    ],
                '8.1' =>
                    [
                        'classes' =>
                            [
                                'AddressInfo',
                                'Attribute',
                                'CURLStringFile',
                                'CurlHandle',
                                'CurlMultiHandle',
                                'CurlShareHandle',
                                'DeflateContext',
                                'FTP\\Connection',
                                'Fiber',
                                'FiberError',
                                'GdFont',
                                'GdImage',
                                'InflateContext',
                                'InternalIterator',
                                'IntlDatePatternGenerator',
                                'LDAP\\Connection',
                                'LDAP\\Result',
                                'LDAP\\ResultEntry',
                                'OpenSSLAsymmetricKey',
                                'OpenSSLCertificate',
                                'OpenSSLCertificateSigningRequest',
                                'PSpell\\Config',
                                'PSpell\\Dictionary',
                                'PgSql\\Connection',
                                'PgSql\\Lob',
                                'PgSql\\Result',
                                'PhpToken',
                                'ReflectionAttribute',
                                'ReflectionEnum',
                                'ReflectionEnumBackedCase',
                                'ReflectionEnumUnitCase',
                                'ReflectionFiber',
                                'ReflectionIntersectionType',
                                'ReflectionUnionType',
                                'ReturnTypeWillChange',
                                'Shmop',
                                'Socket',
                                'SysvMessageQueue',
                                'SysvSemaphore',
                                'SysvSharedMemory',
                                'UnhandledMatchError',
                                'ValueError',
                                'WeakMap',
                                'XMLParser',
                            ],
                        'interfaces' =>
                            [
                                'BackedEnum',
                                'DOMChildNode',
                                'DOMParentNode',
                                'Stringable',
                                'UnitEnum',
                            ],
                        'traits' =>
                            [
                            ],
                    ],
                '8.2' =>
                    [
                        'classes' =>
                            [
                                'AddressInfo',
                                'AllowDynamicProperties',
                                'Attribute',
                                'CURLStringFile',
                                'CurlHandle',
                                'CurlMultiHandle',
                                'CurlShareHandle',
                                'DeflateContext',
                                'FTP\\Connection',
                                'Fiber',
                                'FiberError',
                                'GdFont',
                                'GdImage',
                                'InflateContext',
                                'InternalIterator',
                                'IntlDatePatternGenerator',
                                'LDAP\\Connection',
                                'LDAP\\Result',
                                'LDAP\\ResultEntry',
                                'OpenSSLAsymmetricKey',
                                'OpenSSLCertificate',
                                'OpenSSLCertificateSigningRequest',
                                'PSpell\\Config',
                                'PSpell\\Dictionary',
                                'PgSql\\Connection',
                                'PgSql\\Lob',
                                'PgSql\\Result',
                                'PhpToken',
                                'Random\\BrokenRandomEngineError',
                                'Random\\Engine\\Mt19937',
                                'Random\\Engine\\PcgOneseq128XslRr64',
                                'Random\\Engine\\Secure',
                                'Random\\Engine\\Xoshiro256StarStar',
                                'Random\\RandomError',
                                'Random\\RandomException',
                                'Random\\Randomizer',
                                'ReflectionAttribute',
                                'ReflectionEnum',
                                'ReflectionEnumBackedCase',
                                'ReflectionEnumUnitCase',
                                'ReflectionFiber',
                                'ReflectionIntersectionType',
                                'ReflectionUnionType',
                                'ReturnTypeWillChange',
                                'SensitiveParameter',
                                'SensitiveParameterValue',
                                'Shmop',
                                'Socket',
                                'SysvMessageQueue',
                                'SysvSemaphore',
                                'SysvSharedMemory',
                                'UnhandledMatchError',
                                'ValueError',
                                'WeakMap',
                                'XMLParser',
                            ],
                        'interfaces' =>
                            [
                                'BackedEnum',
                                'DOMChildNode',
                                'DOMParentNode',
                                'Random\\CryptoSafeEngine',
                                'Random\\Engine',
                                'Stringable',
                                'UnitEnum',
                            ],
                        'traits' =>
                            [
                            ],
                    ],
                '8.3' =>
                    [
                        'classes' =>
                            [
                                'AddressInfo',
                                'AllowDynamicProperties',
                                'Attribute',
                                'CURLStringFile',
                                'CurlHandle',
                                'CurlMultiHandle',
                                'CurlShareHandle',
                                'DateError',
                                'DateException',
                                'DateInvalidOperationException',
                                'DateInvalidTimeZoneException',
                                'DateMalformedIntervalStringException',
                                'DateMalformedPeriodStringException',
                                'DateMalformedStringException',
                                'DateObjectError',
                                'DateRangeError',
                                'DeflateContext',
                                'FTP\\Connection',
                                'Fiber',
                                'FiberError',
                                'GdFont',
                                'GdImage',
                                'InflateContext',
                                'InternalIterator',
                                'IntlDatePatternGenerator',
                                'LDAP\\Connection',
                                'LDAP\\Result',
                                'LDAP\\ResultEntry',
                                'OpenSSLAsymmetricKey',
                                'OpenSSLCertificate',
                                'OpenSSLCertificateSigningRequest',
                                'Override',
                                'PSpell\\Config',
                                'PSpell\\Dictionary',
                                'PgSql\\Connection',
                                'PgSql\\Lob',
                                'PgSql\\Result',
                                'PhpToken',
                                'Random\\BrokenRandomEngineError',
                                'Random\\Engine\\Mt19937',
                                'Random\\Engine\\PcgOneseq128XslRr64',
                                'Random\\Engine\\Secure',
                                'Random\\Engine\\Xoshiro256StarStar',
                                'Random\\IntervalBoundary',
                                'Random\\RandomError',
                                'Random\\RandomException',
                                'Random\\Randomizer',
                                'ReflectionAttribute',
                                'ReflectionEnum',
                                'ReflectionEnumBackedCase',
                                'ReflectionEnumUnitCase',
                                'ReflectionFiber',
                                'ReflectionIntersectionType',
                                'ReflectionUnionType',
                                'ReturnTypeWillChange',
                                'SQLite3Exception',
                                'SensitiveParameter',
                                'SensitiveParameterValue',
                                'Shmop',
                                'Socket',
                                'SysvMessageQueue',
                                'SysvSemaphore',
                                'SysvSharedMemory',
                                'UnhandledMatchError',
                                'ValueError',
                                'WeakMap',
                                'XMLParser',
                            ],
                        'interfaces' =>
                            [
                                'BackedEnum',
                                'DOMChildNode',
                                'DOMParentNode',
                                'Random\\CryptoSafeEngine',
                                'Random\\Engine',
                                'Stringable',
                                'UnitEnum',
                            ],
                        'traits' =>
                            [
                            ],
                    ],
                '8.4' =>
                    [
                        'classes' =>
                            [
                                'DOM\\Document',
                                'DOM\\HTMLDocument',
                                'DOM\\XMLDocument',
                                'dom\\attr',
                                'dom\\cdatasection',
                                'dom\\characterdata',
                                'dom\\comment',
                                'dom\\documentfragment',
                                'dom\\documenttype',
                                'dom\\domexception',
                                'dom\\element',
                                'dom\\entity',
                                'dom\\entityreference',
                                'dom\\namednodemap',
                                'dom\\namespacenode',
                                'dom\\node',
                                'dom\\nodelist',
                                'dom\\notation',
                                'dom\\processinginstruction',
                                'dom\\text',
                                'dom\\xpath',
                            ],
                        'interfaces' =>
                            [
                                'dom\\childnode',
                                'dom\\parentnode',
                            ],
                        'traits' =>
                            [
                            ],
                    ],
                ];

        $flatArray = array();
        array_walk_recursive(
            $builtins,
            function ($array) use (&$flatArray) {
                if (is_array($array)) {
                    $flatArray = array_merge($flatArray, array_values($array));
                } else {
                    $flatArray[] = $array;
                }
            }
        );
        return $flatArray;
    }
}
