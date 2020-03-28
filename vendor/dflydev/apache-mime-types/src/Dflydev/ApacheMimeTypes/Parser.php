<?php

/*
 * This file is a part of dflydev/apache-mime-types.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\ApacheMimeTypes;

/**
 * Parser
 *
 * @author Beau Simensen <beau@dflydev.com>
 */
class Parser
{
    /**
     * Parse Apache MIME Types
     *
     * @param string $filename Filename
     *
     * @return array
     */
    public function parse($filename)
    {
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $map = array();

        foreach ($lines as $line) {
            if (strpos($line, '#') !== 0) {
                preg_match_all('/^((\w|\/|\.|-|\+)+)(\s+)([^\n]*)$/im', $line, $match);
                $type = $match[1][0];
                $extensions = explode(' ', $match[4][0]);

                $map[$type] = $extensions;
            }
        }

        return $map;
    }
}
