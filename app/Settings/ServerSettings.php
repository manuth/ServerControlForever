<?php

namespace Gizmo\ServerControlForever\Settings;

use ArrayAccess;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSection;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationStore;

/**
 * Provides settings related to the TrackMania server.
 */
class ServerSettings extends ConfigurationSection
{
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
     * Gets the server's hostname or IP address.
     *
     * @return string The server's hostname or IP address.
     */
    public function getServerHost(): string
    {
        return $this->getServerAddressComponent(PHP_URL_HOST, 'localhost');
    }

    /**
     * Sets the server's hostname or IP address.
     *
     * @param string $host The server's hostname or IP address.
     */
    public function setServerHost(string $host): void
    {
        $this->setServerAddressComponent(PHP_URL_HOST, $host);
    }

    /**
     * Gets the server's port.
     *
     * @return int The server's port.
     */
    public function getServerPort(): int
    {
        return $this->getServerAddressComponent(PHP_URL_PORT, 5000);
    }

    /**
     * Sets the server's port.
     *
     * @param int $port The server's port.
     */
    public function setServerPort(int $port): void
    {
        $this->setServerAddressComponent(PHP_URL_PORT, $port);
    }

    /**
     * Gets a component of the server's address.
     *
     * @param int $component The component of the server's address to get.
     * @param mixed $default The default value to return if the component is not found.
     * @return mixed The component of the server's address.
     */
    protected function getServerAddressComponent(int $component, $default = null)
    {
        $path = collect([ServerSettingKey::Address]);
        $address = $this->getValue($path);

        if (is_string($address))
        {
            parse_url($address, $component) ?? $default;
        }
        else
        {
            /**
             * @var EnvironmentVariable $variable
             */
            $variable;

            if ($component === PHP_URL_HOST)
            {
                $path->push(ServerSettingKey::Host);
                $variable = EnvironmentVariable::Host;
            }
            else
            {
                $path->push(ServerSettingKey::Port);
                $variable = EnvironmentVariable::Port;
            }

            return $this->getValue($path, $variable, $default);
        }
    }

    /**
     * Sets the component of the server's address.
     *
     * @param int $component The component of the server's address to set.
     * @param mixed $value The value to set.
     */
    protected function setServerAddressComponent(int $component, $value): void
    {
        $path = collect([ServerSettingKey::Address]);
        $result = $this->getValue($path);
        $hostPath = $path->concat([ServerSettingKey::Host]);
        $portPath = $path->concat([ServerSettingKey::Port]);

        if (!is_string($result))
        {
            $this->setValue($component === PHP_URL_HOST ? $hostPath : $portPath, $value);
        }
        else
        {
            /**
             * @var int $existingComponent
             */
            $existingComponent;
            /**
             * @var string[] $existingPath
             */
            $existingPath;
            /**
             * @var string[] $newPath
             */
            $newPath;

            if ($component === PHP_URL_HOST)
            {
                $existingComponent = PHP_URL_PORT;
                $existingPath = $portPath;
                $newPath = $hostPath;
            }
            else
            {
                $existingComponent = PHP_URL_HOST;
                $existingPath = $hostPath;
                $newPath = $portPath;
            }

            $existingValue = $this->getServerAddressComponent($existingComponent);
            $this->setValue($path, []);
            $this->setValue($existingPath, $existingValue);
            $this->setValue($newPath, $value);
        }
    }
}
