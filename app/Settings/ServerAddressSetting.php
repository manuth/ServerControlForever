<?php

namespace Gizmo\ServerControlForever\Settings;

use Gizmo\ServerControlForever\Settings\Management\ConfigurationSetting;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationSource;
use Gizmo\ServerControlForever\Settings\Management\ConfigurationStore;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use StringBackedEnum;

/**
 * Represents the setting of a server address component.
 */
class ServerAddressSetting extends ConfigurationSetting
{
    /**
     * A setting containing the server's address.
     */
    private ?ConfigurationSetting $addressSetting = null;

    /**
     * The key of the setting.
     */
    private ServerSettingKey $settingKey;

    /**
     * The component of the address to get or set.
     */
    private int $component;

    /**
     * Initializes a new instance of the {@see ServerAddressSetting} class.
     *
     * @param StringBackedEnum[] $path The path to the server address setting.
     * @param ConfigurationStore $store The configuration store containing this setting.
     * @param int $component The component of the address to get or set.
     * @param EnvironmentVariable $variable An environment variable to use for overriding the attribute.
     */
    public function __construct($path, ConfigurationStore $store, int $component, EnvironmentVariable $variable = EnvironmentVariable::None, $default = null)
    {
        parent::__construct($path, $store, $variable, $default);
        $this->settingKey = $component === PHP_URL_HOST ? ServerSettingKey::Host : ServerSettingKey::Port;
        $this->component = $component;
    }

    /**
     * @inheritDoc
     */
    public function getSource(): ConfigurationSource
    {
        try
        {
            $address = $this->getAddressSetting()->getValue(ConfigurationSource::File);

            if (is_string($address))
            {
                if (parse_url($address, $this->component) !== null)
                {
                    return ConfigurationSource::File;
                }
            }
        }
        catch (\Exception $e)
        {
        }

        return parent::getSource();
    }

    /**
     * Gets the component of the address to get or set.
     *
     * @return int The component of the address to get or set.
     */
    public function getComponent(): int
    {
        return $this->component;
    }

    /**
     * Gets a setting containing the server's address.
     *
     * @return ConfigurationSetting A setting containing the server's address.
     */
    protected function getAddressSetting(): ConfigurationSetting
    {
        if ($this->addressSetting === null)
        {
            $this->addressSetting = new ConfigurationSetting($this->getAddressPath(), $this->getStore());
        }

        return $this->addressSetting;
    }

    /**
     * Gets the key of the setting.
     *
     * @return StringBackedEnum The key of the setting.
     */
    protected function getSettingKey(): ServerSettingKey
    {
        return $this->settingKey;
    }

    /**
     * Gets the path to the setting containing the server's address.
     *
     * @return Collection<StringBackedEnum> The path to the setting containing the server's address.
     */
    public function getAddressPath(): Collection
    {
        return parent::getPath();
    }

    /**
     * Gets the full path to the setting.
     *
     * @return Collection<StringBackedEnum> The full path to the setting.
     */
    public function getPath(): Collection
    {
        return $this->getAddressPath()->merge([$this->getSettingKey()]);
    }

    /**
     * @inheritDoc
     */
    public function setValue($value): void
    {
        $address = $this->getAddressSetting()->getValue();

        if (!is_string($address))
        {
            $this->getAddressSetting()->setValue([]);
            $this->getStore()->setValue($this->getPath(), $value);
        }
        else
        {
            /**
             * @var int $transferComponent
             */
            $transferComponent;
            /**
             * @var StringBackedEnum[] $transferPath
             */
            $transferPath;
            $newPath = $this->getPath();

            if ($this->getComponent() === PHP_URL_HOST)
            {
                $transferComponent = PHP_URL_PORT;
                $transferPath = $this->getAddressPath()->merge([ServerSettingKey::Port]);
            }
            else
            {
                $transferComponent = PHP_URL_HOST;
                $transferPath = $this->getAddressPath()->merge([ServerSettingKey::Host]);
            }

            try
            {
                $transferSetting = new ServerAddressSetting($this->getAddressPath(), $this->getStore(), $transferComponent);
                $transferValue = $transferSetting->getValue(ConfigurationSource::File);

                $this->getAddressSetting()->setValue(
                    [
                        ServerSettingKey::Host->value => null,
                        ServerSettingKey::Port->value => null
                    ]
                );

                $this->getStore()->setValue($transferPath, $transferValue);
            }
            catch (\Exception $e)
            {
                $this->getAddressSetting()->setValue([]);
            }
            finally
            {
                $this->getStore()->setValue($newPath, $value);
            }
        }
    }

    /**
     * Gets the value of the setting from the specified source.
     *
     * @param ConfigurationSource $source The source to get the value from.
     * @return mixed The value of the setting from the specified source.
     */
    protected function getValueFromSource(ConfigurationSource $source): mixed
    {
        if ($source === ConfigurationSource::File)
        {
            $address = $this->getAddressSetting()->getValue();

            if (is_string($address))
            {
                // Treat address as a url if a port is specified in the address.
                if (parse_url($address, PHP_URL_PORT))
                {
                    return parse_url($address, $this->getComponent());
                }
                else if ($this->getComponent() !== PHP_URL_PORT)
                {
                    // If the address doesn't contain a port and the host component is requested, return the full address.
                    return $address;
                }
            }
        }

        // Fall back to the default behavior if the value should not be loaded from a configuration file and
        // or if the address is not specified as a string.
        return parent::getValueFromSource($source);
    }
}
