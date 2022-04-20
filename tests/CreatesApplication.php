<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Symfony\Component\Filesystem\Path;

/**
 * Provides the functionality to build an application.
 */
trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application The newly created application.
     */
    public function createApplication()
    {
        /** @var Application */
        $app = require Path::join(__DIR__, '..', 'bootstrap', 'app.php');
        /** @var Kernel */
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        return $app;
    }
}
