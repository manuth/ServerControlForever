<?php

namespace Gizmo\ServerControlForever\Settings;

use Gizmo\ServerControlForever\JSONC\JSONCObject;
use Gizmo\ServerControlForever\JSONC\JSONCObjectBase;
use Gizmo\ServerControlForever\Settings\SettingKey;

/**
 * Provides the functionality to load settings.
 */
class Settings extends ConfigurationSection
{
    /**
     * An object containing settings related to the TrackMania server.
     */
    public readonly ServerSettings $server;

    /**
     * Initializes a new instance of the {@see Settings} class.
     *
     * @param JSONCObject $settings The object containing the settings.
     */
    public function __construct(JSONCObject $settings)
    {
        parent::__construct([], new ConfigurationStore($settings));
        $this->server = new ServerSettings([SettingKey::Server], $this->getStore());
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
        $this->setValue($value, SettingKey::AbbreviatedCommands);
    }
}
