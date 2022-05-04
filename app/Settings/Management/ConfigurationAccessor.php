<?php

namespace Gizmo\ServerControlForever\Settings\Management;

use Gizmo\ServerControlForever\Settings\EnvironmentVariable;
use StringBackedEnum;

/**
 * Provides the functionality to access configuration values.
 */
abstract class ConfigurationAccessor
{
    /**
     * Gets the value of the setting located at the specified {@see $path}.
     *
     * @param StringBackedEnum[] $path The path to the setting.
     * @return mixed The value of the setting located at the specified {@see $path}.
     */
    abstract protected function getValue($path): mixed;

    /**
     * Checks whether a setting at the specified {@see $path} exists.
     *
     * @param StringBackedEnum[] $path The path to the setting.
     * @return bool A value indicating whether a setting at the specified {@see $path} exists.
     */
    abstract protected function hasSetting($path): bool;

    /**
     * Sets the value of the setting located at the specified path.
     *
     * @param mixed $value The value to set.
     * @param StringBackedEnum[] $path The path to the setting to set.
     */
    abstract protected function setValue($path, $value): void;
}
