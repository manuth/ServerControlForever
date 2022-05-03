<?php

namespace Gizmo\ServerControlForever\Settings\Management;

/**
 * Represents the source of a configuration.
 */
enum ConfigurationSource
{
    /**
     * Indicates no source.
     */
    case None;

    /**
     * Indicates an environment variable.
     */
    case EnvironmentVariable;

    /**
     * Indicates a configuration file.
     */
    case File;
}
