<?php

namespace Gizmo\ServerControlForever\Settings;

use Gizmo\ServerControlForever\JSONC\JSONCObject;
use Gizmo\ServerControlForever\JSONC\JSONCObjectBase;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSection;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSetting;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationStore;
use Gizmo\ServerControlForever\Settings\Management\SettingAttribute;
use Gizmo\ServerControlForever\Settings\SettingKey;
use Illuminate\Support\Collection;

/**
 * Provides the functionality to load settings.
 */
class Settings extends ConfigurationSection
{
    /**
     * The setting containing a value indicating whether command abbreviations are enabled.
     */
    private ?ConfigurationSetting $abbreviatedCommandsSetting = null;

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
     * Gets the individual settings in this section.
     *
     * @return mixed The individual settings in this section.
     */
    public function getSettings(): array
    {
        return collect(
            [
                $this->getAbbreviatedCommandsSetting(),
                $this->server->getServerHostSetting(),
                $this->server->getServerPortSetting()
            ]
        )->merge($this->getConfigurationSettings())->unique()->toArray();
    }

    /**
     * Gets the setting containing a value indicating whether command abbreviations are enabled.
     *
     * @return ConfigurationSetting The setting containing a value indicating whether command abbreviations are enabled.
     */
    #[SettingAttribute]
    public function getAbbreviatedCommandsSetting()
    {
        if ($this->abbreviatedCommandsSetting === null)
        {
            $this->abbreviatedCommandsSetting = new ConfigurationSetting($this->getPath(SettingKey::AbbreviatedCommands), $this->getStore(), EnvironmentVariable::AbbreviatedCommands, false);
        }

        return $this->abbreviatedCommandsSetting;
    }

    /**
     * Gets the settings of the server section.
     *
     * @return ConfigurationSetting[] The settings of the server section.
     */
    #[SettingAttribute]
    public function getServerSettings(): array
    {
        return $this->server->getConfigurationSettings();
    }

    /**
     * Gets a value indicating whether command abbreviations are enabled.
     *
     * @return bool A value indicating whether command abbreviations are enabled.
     */
    public function getCommandAbbreviationsEnabled(): bool
    {
        return $this->getAbbreviatedCommandsSetting()->getValue();
    }

    /**
     * Sets a value indicating whether command abbreviations are enabled.
     *
     * @param bool $enabled A value indicating whether command abbreviations are enabled.
     */
    public function setCommandAbbreviationsEnabled(bool $value)
    {
        $this->getAbbreviatedCommandsSetting()->setValue($value);
    }
}
