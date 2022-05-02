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
        return $this->settings[SettingKey::AbbreviatedCommands];
    }

    /**
     * Sets a value indicating whether command abbreviations are enabled.
     *
     * @param bool $enabled A value indicating whether command abbreviations are enabled.
     */
    public function setCommandAbbreviationsEnabled(bool $value)
    {
        $this->settings[SettingKey::AbbreviatedCommands] = $value;
    }
}
