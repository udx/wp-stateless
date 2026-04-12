<?php

namespace BrianHenryIE\Strauss\Helpers;

class Path
{
    public static function normalize(string $path): string
    {
        return rtrim(preg_replace('#[\\\/]+#', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }
}
