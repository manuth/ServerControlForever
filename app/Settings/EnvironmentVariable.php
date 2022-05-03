<?php

namespace Gizmo\ServerControlForever\Settings;

/**
 * A set of environment variable names for overriding settings.
 */
enum EnvironmentVariable: string
{
    /**
     * Indicates the environment variable for specifying the server's hostname or IP address.
     */
    case Host = "SERVER_HOST";

    /**
     * Indicates the environment variable for specifying the server's port.
     */
    case Port = "SERVER_PORT";
}
