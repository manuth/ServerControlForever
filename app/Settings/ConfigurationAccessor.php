<?php

namespace Gizmo\ServerControlForever\Settings;

/**
 * Provides the functionality to access configuration values.
 */
abstract class ConfigurationAccessor
{
    /**
     * Gets the value of the setting located at the specified {@see $path}.
     *
     * @param SettingKey $path The path to the setting.
     * @return mixed The value of the setting located at the specified {@see $path}.
     */
    abstract protected function getValue(...$path): mixed;

    /**
     * Sets the value of the setting located at the specified path.
     *
     * @param mixed $value The value to set.
     * @param SettingKey $path The path to the setting to set.
     */
    abstract protected function setValue($value, ...$path): void;
}
