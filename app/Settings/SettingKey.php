<?php

namespace Gizmo\ServerControlForever\Settings;

/**
 * Represents a setting key.
 */
enum SettingKey: string
{
    /**
     * Indicates the setting for enabling abbreviated commands.
     */
    case AbbreviatedCommands = "abbreviatedCommands";

    /**
     * Indicates the setting for specifying the server's connection information.
     */
    case Server = "server";

    /**
     * Indicates the setting for specifying the server's address.
     */
    case ServerAddress = "address";

    /**
     * Indicates the setting for specifying the server's hostname or IP address.
     */
    case ServerHost = "host";

    /**
     * Indicates the setting for specifying the server's port.
     */
    case ServerPort = "port";
}
