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
}
