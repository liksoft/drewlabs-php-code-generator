<?php

declare(strict_types=1);

/*
 * This file is part of the Drewlabs package.
 *
 * (c) Sidoine Azandrew <azandrewdevelopper@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Drewlabs\CodeGenerator\Helpers;

class PHPLanguageDefifinitions
{
    public const DEFAULT_SPECIAL_CHARACTERS = ['[', ',', ':', '}', '{', '?'];

    /**
     * Helper method for identifying PHP block of expressions.
     *
     * @return bool
     */
    public static function isBlock(string $line)
    {
        return !empty($line) &&
            ((Str::startsWith(trim($line), '{') && Str::endsWith(trim($line), '{')) ||
                Str::endsWith(trim($line), '{') ||
                (Str::startsWith(trim($line), '}') && Str::endsWith(trim($line), '}')) ||
                Str::endsWith(trim($line), '}') ||
                (Str::startsWith(trim($line), ':') && (Str::endsWith(trim($line), ':'))) ||
                Str::endsWith(trim($line), ':') || (Str::startsWith(trim($line), '(') && Str::endsWith(trim($line), ')')) ||
                Str::endsWith(trim($line), '('));
    }

    /**
     * Helper method for identifying PHP comments.
     *
     * @return bool
     */
    public static function isComment(string $line)
    {
        return !empty($line) &&
            (Str::startsWith(trim($line), '*') ||
                Str::startsWith(trim($line), '/*') ||
                Str::endsWith(trim($line), '*/') ||
                Str::startsWith(trim($line), '//') ||
                Str::startsWith(trim($line), '#'));
    }

    public static function endsWithSpecialCharacters(string $line, array $characters = [])
    {
        $characters = $characters ? array_unique(array_merge($characters, self::DEFAULT_SPECIAL_CHARACTERS)) : self::DEFAULT_SPECIAL_CHARACTERS;
        foreach ($characters as $value) {
            // code...
            if (Str::endsWith(rtrim($line), $value)) {
                return true;
            }
        }

        return false;
    }

    public static function startsWithSpecialCharacters(string $line, array $characters = [])
    {
        $characters = $characters ? array_unique(array_merge($characters, self::DEFAULT_SPECIAL_CHARACTERS)) : self::DEFAULT_SPECIAL_CHARACTERS;
        foreach ($characters as $value) {
            // code...
            if (Str::startsWith(ltrim($line), $value)) {
                return true;
            }
        }

        return false;
    }
}
