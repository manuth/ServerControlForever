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
     * Initializes a new instance of the {@see Settings} class.
     *
     * @param JSONCObject $settings The object containing the settings.
     */
    public function __construct(JSONCObject $settings)
    {
        parent::__construct([], new ConfigurationStore($settings));
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
        $path = collect([SettingKey::Server, SettingKey::ServerAddress]);
        $result = $this->getValue(...$path);

        if (is_string($result))
        {
            return parse_url($result, $component);
        }
        else
        {
            if ($component === PHP_URL_HOST)
            {
                $path->push(SettingKey::ServerHost);
            }
            else
            {
                $path->push(SettingKey::ServerPort);
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
        $path = collect([SettingKey::Server, SettingKey::ServerAddress]);
        $result = $this->getValue(...$path);
        $hostPath = $path->concat([SettingKey::ServerHost]);
        $portPath = $path->concat([SettingKey::ServerPort]);

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
