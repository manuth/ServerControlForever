<?php

namespace Gizmo\ServerControlForever\Settings;

use ArrayAccess;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSection;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSetting;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationStore;
use Gizmo\ServerControlForever\Settings\Management\SettingAttribute;

/**
 * Provides settings related to the TrackMania server.
 */
class ServerSettings extends ConfigurationSection
{
    /**
     * A setting containing the server's hostname or IP address.
     */
    private $serverHostSetting = null;

    /**
     * A setting containing the server's port.
     */
    private $serverPortSetting = null;

    /**
     * Initializes a new instance of the {@see ConfigurationSection} class.
     *
     * @param string[] $rootPath The path to the root of the section.
     * @param ConfigurationStore $configStore The configuration store containing this section.
     */
    public function __construct(array | ArrayAccess $rootPath, ConfigurationStore $configStore)
    {
        parent::__construct($rootPath, $configStore);
    }

    /**
     * Gets the setting containing the server's hostname or IP address.
     *
     * @return ConfigurationSetting The setting containing the server's hostname or IP address.
     */
    #[SettingAttribute]
    public function getServerHostSetting()
    {
        if ($this->serverHostSetting === null)
        {
            $this->serverHostSetting = new ServerAddressSetting($this->getPath(ServerSettingKey::Address), $this->getStore(), PHP_URL_HOST, EnvironmentVariable::Host, 'localhost');
        }

        return $this->serverHostSetting;
    }

    /**
     * Gets the setting containing the server's port.
     *
     * @return ConfigurationSetting The setting containing the server's port.
     */
    #[SettingAttribute]
    public function getServerPortSetting()
    {
        if ($this->serverPortSetting === null)
        {
            $this->serverPortSetting = new ServerAddressSetting($this->getPath(ServerSettingKey::Address), $this->getStore(), PHP_URL_PORT, EnvironmentVariable::Port, 5000);
        }

        return $this->serverPortSetting;
    }

    /**
     * Gets the server's hostname or IP address.
     *
     * @return string The server's hostname or IP address.
     */
    public function getServerHost(): string
    {
        return $this->getServerHostSetting()->getValue();
    }

    /**
     * Sets the server's hostname or IP address.
     *
     * @param string $host The server's hostname or IP address.
     */
    public function setServerHost(string $host): void
    {
        $this->getServerHostSetting()->setValue($host);
    }

    /**
     * Gets the server's port.
     *
     * @return int The server's port.
     */
    public function getServerPort(): int
    {
        return $this->getServerPortSetting()->getValue();
    }

    /**
     * Sets the server's port.
     *
     * @param int $port The server's port.
     */
    public function setServerPort(int $port): void
    {
        $this->getServerPortSetting()->setValue($port);
    }
}
