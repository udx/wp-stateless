<?php

namespace MimeTyper\Symfony;

if (class_exists("Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface")) {
    class_alias(
        "Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface",
        "Mimetyper\Symfony\MimeTypeGuesserInterface"
    );
} else {
    /*
     * Some project with PHP 5.3+ compatibility forced the fork of symfony/http-foundation.
     * Look away, don't judge, bisous.
     */
    class_alias(
        "Madhouse\HttpFoundation\File\MimeType\MimeTypeGuesserInterface",
        "Mimetyper\Symfony\MimeTypeGuesserInterface"
    );
}

/**
 * Guesses the mime type using the deprecated – but yet useful sometimes,
 * mime_content_type.
 *
 * @author Hussard <adrien.ricartnoblet@gmail.com>
 * @since  0.2.0
 */
class MimeContentMimeTypeGuesser implements MimeTypeGuesserInterface
{
    /**
     * Constructor.
     *
     * @link http://php.net/manual/fr/function.mime-content-type.php
     */
    public function __construct()
    {
    }

    /**
     * Returns whether this guesser is supported on the current OS/PHP setup.
     *
     * @return bool
     */
    public static function isSupported()
    {
        return function_exists('mime_content_type');
    }

    /**
     * {@inheritdoc}
     */
    public function guess($path)
    {
        if (!is_file($path)) {
            throw new FileNotFoundException($path);
        }

        if (!is_readable($path)) {
            throw new AccessDeniedException($path);
        }

        if (!self::isSupported()) {
            return;
        }

        return mime_content_type($path);
    }
}
