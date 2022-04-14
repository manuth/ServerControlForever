<?php

use Tests\TestCase;

it('inspire artisans', function ()
{
    /** @var TestCase $this */
    $this->artisan('inspire')->assertExitCode(0);
});
