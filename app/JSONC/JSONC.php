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
     * @param int $width The width of the indentation. Specifying the width implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param bool $includeComments A value indicating whether to dump comments. Enabling comments implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param int $flags A set of flags for controlling the behavior of the dumper.
     * @return string A string representing the specified value.
     */
    public static function dumpFile(JSONCValue $value, string $fileName, ?int $width = null, ?bool $includeComments = null, ?int $flags = null): void
    {
        file_put_contents($fileName, self::dump($value, $width, $includeComments, $flags));
    }

    /**
     * Dumps the specified jsonc object.
     *
     * @param mixed $value The value to dump.
     * @param int $width The width of the indentation. Specifying the width implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param bool $includeComments A value indicating whether to dump comments. Enabling comments implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param int $flags A set of flags for controlling the behavior of the dumper.
     * @return string The dumped jsonc object.
     */
    public static function dump($value, ?int $width = null, ?bool $includeComments = null, ?int $flags = null): string
    {
        return (new JSONCDumper())->dump($value, $width, $includeComments, $flags);
    }
}
