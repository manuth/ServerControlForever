<?php

namespace Gizmo\ServerControlForever\Settings;

/**
 * Represents the key of a server setting.
 */
enum ServerSettingKey: string
{
    /**
     * Indicates the setting for specifying the server's address.
     */
    case Address = "address";

    /**
     * Indicates the setting for specifying the server's hostname or IP address.
     */
    case Host = "host";

    /**
     * Indicates the setting for specifying the server's port.
     */
    case Port = "port";
}
