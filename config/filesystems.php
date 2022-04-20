<?php

return [
    'default' => 'local',
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => env('DATA_DIR', getcwd()),
        ],
    ],
];
