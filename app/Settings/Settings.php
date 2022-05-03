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
     * @param SettingKey $path The path to the setting.
     */
    protected function getValue(...$path)
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
     * @param SettingKey $path The path to the setting to set.
     */
    protected function setValue($value, ...$path): void
    {
        $container = $this->getSettings();
        $containerPath = collect($path);
        /**
         * @var SettingKey $lastKey
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
             * @var SettingKey $key
             */
            $key = $containerPath->pop();
            $layer[$key->value] = $container;
            $container = $layer;
        }
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
