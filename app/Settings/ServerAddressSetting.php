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
        $result = parent::getSource();

        if ($result === ConfigurationSource::File)
        {
            $address = $this->getStore()->getValue($this->getPath());

            if (is_string($address))
            {
                if (parse_url($address, $this->component) !== null)
                {
                    return ConfigurationSource::File;
                }
            }
            else if ($this->getStore()->hasSetting($this->getFullPath()))
            {
                return ConfigurationSource::File;
            }

            return ConfigurationSource::None;
        }
        else
        {
            return $result;
        }
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
            $this->addressSetting = new ConfigurationSetting($this->getPath(), $this->getStore());
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
     * Gets the full path to the setting.
     *
     * @return Collection<StringBackedEnum> The full path to the setting.
     */
    protected function getFullPath(): Collection
    {
        return parent::getPath()->merge([$this->getSettingKey()]);
    }

    /**
     * @inheritDoc
     */
    public function getValue(): mixed
    {
        if ($this->getSource() === ConfigurationSource::EnvironmentVariable)
        {
            return Env::get($this->getVariable()->value);
        }
        else
        {
            $address = $this->getAddressSetting()->getValue();

            if (is_string($address))
            {
                return parse_url($address, $this->getComponent()) ?? $this->getDefault();
            }
            else
            {
                return $this->getStore()->getValue($this->getFullPath(), $this->getVariable(), $this->getDefault());
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function setValue($value): void
    {
        $address = $this->getAddressSetting()->getValue();

        if (!is_string($address))
        {
            $this->getStore()->setValue($this->getFullPath(), $value);
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
            $newPath = $this->getFullPath();

            if ($this->getComponent() === PHP_URL_HOST)
            {
                $transferComponent = PHP_URL_HOST;
                $transferPath = $this->getPath()->merge([ServerSettingKey::Host]);
            }
            else
            {
                $transferComponent = PHP_URL_PORT;
                $transferPath = $this->getPath()->merge([ServerSettingKey::Port]);
            }

            $transferSetting = new ServerAddressSetting($this->getPath(), $this->getStore(), $transferComponent);
            $transferSource = $transferSetting->getSource();
            $transferValue = $transferSetting->getValue();
            parent::setValue([]);

            if ($transferSource === ConfigurationSource::File)
            {
                parent::setValue(
                    [
                        ServerSettingKey::Host->value => null,
                        ServerSettingKey::Port->value => null
                    ]
                );

                $this->getStore()->setValue($transferPath, $transferValue);
            }

            $this->getStore()->setValue($newPath, $value);
        }
    }
}
