<?php

namespace Gizmo\ServerControlForever\Settings;

/**
 * A set of environment variable names for overriding settings.
 */
enum EnvironmentVariable: string
{
    /**
     * Indicates no environment variable.
     */
    case None = "";

    /**
     * Indicates the environment variable for overriding the setting.
     */
    case AbbreviatedCommands = "ABBREVIATED_COMMANDS";

    /**
     * Indicates the environment variable for specifying the server's hostname or IP address.
     */
    case Host = "SERVER_HOST";

    /**
     * Indicates the environment variable for specifying the server's port.
     */
    case Port = "SERVER_PORT";
}
