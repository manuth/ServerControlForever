<?php

namespace Gizmo\ServerControlForever\Commands;

use Gizmo\ServerControlForever\Management\Server;
use Illuminate\Support\Facades\Log;
use LaravelZero\Framework\Commands\Command;

/**
 * Runs the "Server Control Forever" server.
 */
class RunCommand extends Command
{
    /**
     * @inheritDoc
     */
    protected $signature = 'run';

    /**
     * @inheritDoc
     */
    protected $description = 'Runs the "Server Control Forever" server';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::debug("Setting the default timezone…");
        date_default_timezone_set(@date_default_timezone_get());

        Log::debug("Setting the memory limit to at least 128MB…");

        if (static::toByteCount(ini_get('memory_limit')) < 128 * 1024 * 1024)
        {
            ini_set('memory_limit', '128M');
        }

        Log::debug("Setting the numeric locale to default…");
        setlocale(LC_NUMERIC, 'C');
        $server = new Server(null);
    }

    /**
     * Converts the specified {@see $text} to the corresponding number of bytes.
     *
     * @param $text The text to convert.
     * @return int The number of bytes represented by the specified {@see $text}.
     */
    protected static function toByteCount(string $text): int
    {
        switch (strtoupper(mb_substr($text, -1)))
        {
            case 'M':
                return (int)$text * 1048576;
            case 'K':
                return (int)$text * 1024;
            case 'G':
                return (int)$text * 1073741824;
            default:
                return (int)$text;
        }
    }
}
