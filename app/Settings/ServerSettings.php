<?php

namespace Gizmo\ServerControlForever\Settings;

use ArrayAccess;

/**
 * Provides settings related to the TrackMania server.
 */
class ServerSettings extends ConfigurationSection
{
    /**
     * Initializes a new instance of the {@see ConfigurationSection} class.
     *
     * @param string[] $rootPath The path to the root of the section.
     * @param ConfigurationAccessor $configStore The configuration store containing this section.
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
        return $this->getServerAddressComponent(PHP_URL_HOST);
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
        return $this->getServerAddressComponent(PHP_URL_PORT);
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
     * @return mixed The component of the server's address.
     */
    protected function getServerAddressComponent(int $component)
    {
        $path = collect([ServerSettingKey::Address]);
        $result = $this->getValue(...$path);

        if (is_string($result))
        {
            return parse_url($result, $component);
        }
        else
        {
            if ($component === PHP_URL_HOST)
            {
                $path->push(ServerSettingKey::Host);
            }
            else
            {
                $path->push(ServerSettingKey::Port);
            }

            return $this->getValue(...$path);
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
        $result = $this->getValue(...$path);
        $hostPath = $path->concat([ServerSettingKey::Host]);
        $portPath = $path->concat([ServerSettingKey::Port]);

        if (!is_string($result))
        {
            $this->setValue($value, ...($component === PHP_URL_HOST ? $hostPath : $portPath));
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
            $this->setValue([], ...$path);
            $this->setValue($existingValue, ...$existingPath);
            $this->setValue($value, ...$newPath);
        }
    }
}
