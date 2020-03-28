<?php

namespace MimeTyper\Symfony;

if (class_exists("Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface")) {
    class_alias(
        "Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesserInterface",
        "Mimetyper\Symfony\ExtensionGuesserInterface"
    );
} else {
    /*
     * Some project with PHP 5.3+ compatibility forced the fork of symfony/http-foundation.
     * Look away, don't judge, bisous.
     */
    class_alias(
        "Madhouse\HttpFoundation\File\MimeType\ExtensionGuesserInterface",
        "Mimetyper\Symfony\ExtensionGuesserInterface"
    );
}

/**
 * Wrapper class for Symfony / Laravel UploadedFile extension guessing.
 *
 * ExtensionGuesser being a singleton, you can register this ExtensionGuesser
 * like this:
 *
 *   use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;
 *
 *   use MimeTyper\Repository\ExtendedRepository;
 *   use MimeTyper\Symfony\ExtraMimeTypeExtensionGuesser;
 *
 *   $symfonyGuesser = ExtensionGuesser::getInstance();
 *   $extraGuesser = new ExtraMimeTypeExtensionGuesser(
 *     new ExtendedRepository()
 *   );
 *
 *   $symfonyGuesser->register($extraGuesser);
 *
 * That way, you can enjoy the extended mapping, way more complete than default
 * php array from symfony.
 *
 * @since 0.1.0
 * @see Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser
 */
class ExtraMimeTypeExtensionGuesser implements ExtensionGuesserInterface
{
    /**
     * Repository instance for mime type / extension mapping.
     *
     * @var Madhouse\Mime\Repository\MimeRepositoryInterface
     */
    protected $repository;

    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    public function guess($type)
    {
        return $this->repository->findExtension($type);
    }
}