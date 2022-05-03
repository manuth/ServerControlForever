<?php

namespace Gizmo\ServerControlForever\Settings;

use Gizmo\ServerControlForever\JSONC\JSONCObject;
use SettingKey;

/**
 * Provides the functionality to load settings.
 */
class SettingsLoader
{
    /**
     * The object containing the settings.
     *
     * @var JSONCObject
     */
    private JSONCObject $settings;

    /**
     * Initializes a new instance of the {@see SettingsLoader} class.
     *
     * @param JSONCObject $settings The object containing the settings.
     */
    private function __construct(JSONCObject $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Gets a value indicating whether command abbreviations are enabled.
     *
     * @return bool A value indicating whether command abbreviations are enabled.
     */
    public function getCommandAbbreviationsEnabled(): bool
    {
        return $this->getValue(SettingKey::AbbreviatedCommands);
    }

    /**
     * Sets a value indicating whether command abbreviations are enabled.
     *
     * @param bool $enabled A value indicating whether command abbreviations are enabled.
     */
    public function setCommandAbbreviationsEnabled(bool $value)
    {
        $this->setValue(SettingKey::AbbreviatedCommands, $value);
    }
    
    /**
     * Gets the object containing the settings.
     *
     * @return JSONCObject The object containing the settings.
     */
    protected function getSettings(): JSONCObject
    {
        return $this->settings;
    }

    /**
     * Gets the value of the setting located at the specified path.
     *
     * @param string[] $path The path to the setting.
     */
    protected function getValue(array ...$path)
    {
        $result = $this->getSettings();

        foreach ($path as $key)
        {
            if (isset($result[$key]))
            {
                $result = $result[$key];
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
     * @param string[] $path The path to the setting to set.
     */
    protected function setValue($value, array ...$path): void
    {
        $container = $this->getSettings();
        $containerPath = collect($path);
        $lastKey = $containerPath->pop();

        foreach ($containerPath as $key)
        {
            if (!isset($container[$key]))
            {
                $container[$key] = [];
            }

            $container = $container[$key];
        }

        $container[$lastKey] = $value;
    }
}
