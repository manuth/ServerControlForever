<?php

namespace Gizmo\ServerControlForever\Settings;

use ArrayAccess;
use Gizmo\ServerControlForever\JSONC\JSONCObject;
use StringBackedEnum;

/**
 * Provides the functionality to store settings.
 */
class ConfigurationStore extends ConfigurationAccessor
{
    /**
     * The object containing the actual settings.
     */
    private JSONCObject $settings;

    /**
     * Initializes a new instance of the {@see ConfigurationStore} class.
     *
     * @param JSONCObject $settings The object containing the settings.
     */
    public function __construct(JSONCObject $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Gets the object containing the settings.
     *
     * @return array|ArrayAccess The object containing the settings.
     */
    protected function getSettings(): JSONCObject
    {
        return $this->settings;
    }

    /**
     * Gets the value of the setting located at the specified path.
     *
     * @param StringBackedEnum $path The path to the setting.
     */
    public function getValue(...$path)
    {
        $result = $this->getSettings();

        foreach ($path as $key)
        {
            if (isset($result[$key->value]))
            {
                $result = $result[$key->value];
            }
            else
            {
                return null;
            }
        }

        return $result;
    }

    /**
     * Sets the value of the setting located at the specified path.
     *
     * @param mixed $value The value to set.
     * @param StringBackedEnum $path The path to the setting to set.
     */
    public function setValue($value, ...$path): void
    {
        $container = $this->getSettings();
        $containerPath = collect($path);
        /**
         * @var StringBackedEnum $lastKey
         */
        $lastKey = $containerPath->pop();
        $layers = collect();

        foreach ($containerPath as $key)
        {
            $layers->push($container[$key->value]);
            $container = $container[$key->value];
        }

        $layers->pop();
        $container[$lastKey->value] = $value;

        foreach ($layers as $layer)
        {
            /**
             * @var StringBackedEnum $key
             */
            $key = $containerPath->pop();
            $layer[$key->value] = $container;
            $container = $layer;
        }
    }
}
