<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Provides the functionality to parse and dump jsonc objects.
 */
class JSONC
{
    /**
     * Parses the specified file.
     *
     * @param string $fileName The name of the file to parse.
     * @return JSONCValue The parsed jsonc object.
     */
    public static function parseFile(string $fileName): JSONCValue
    {
        return self::parse(file_get_contents($fileName), $fileName);
    }

    /**
     * Parses the specified string.
     *
     * @param string $string The string to parse.
     * @param string $fileName The name of the file to parse.
     * @return JSONCValue The parsed jsonc object.
     */
    public static function parse(string $code, string $fileName = null): JSONCValue
    {
        return (new JSONCParser())->parse($code, $fileName);
    }

    /**
     * Dumps the specified value.
     *
     * @param JSONCValue $value The value to dump.
     * @param string $fileName The name of the file to dump the output to.
     * @return string A string representing the specified value.
     */
    public static function dumpFile(JSONCValue $value, string $fileName): void
    {
        file_put_contents($fileName, self::dump($value));
    }

    /**
     * Dumps the specified jsonc object.
     *
     * @param mixed $value The value to dump.
     * @return string The dumped jsonc object.
     */
    public static function dump($value): string
    {
        return (new JSONCDumper())->dump($value);
    }
}
