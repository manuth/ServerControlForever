<?php

namespace Gizmo\ServerControlForever\Settings\Management;

use Gizmo\ServerControlForever\Settings\EnvironmentVariable;
use Illuminate\Support\Collection;
use Illuminate\Support\Env;
use StringBackedEnum;

/**
 * Represents the setting of a configuration.
 */
class ConfigurationSetting
{
    /**
     * The path to the setting.
     *
     * @var Collection<StringBackedEnum>
     */
    private $path;

    /**
     * The configuration store containing this setting.
     */
    private ConfigurationStore $store;

    /**
     * An environment variable to use for overriding the attribute.
     */
    private EnvironmentVariable $variable;

    /**
     * The default value of the setting.
     */
    private $default;

    /**
     * Initializes a new instance of the {@see ConfigurationSetting} class.
     *
     * @param StringBackedEnum[] $path The path to the setting.
     * @param ConfigurationStore $store The configuration store containing this setting.
     * @param EnvironmentVariable $variable An environment variable to use for overriding the attribute.
     */
    public function __construct($path, ConfigurationStore $store, EnvironmentVariable $variable = EnvironmentVariable::None, $default = null)
    {
        $this->path = collect($path);
        $this->store = $store;
        $this->variable = $variable;
        $this->default = $default;
    }

    /**
     * Gets the path to the setting.
     *
     * @return Collection<StringBackedEnum> The path to the setting.
     */
    public function getPath(): Collection
    {
        return collect($this->path);
    }

    /**
     * Gets the source of the value of the setting.
     *
     * @return ConfigurationStore The source of the value of the setting.
     */
    public function getSource(): ConfigurationSource
    {
        if (Env::getRepository()->has($this->getVariable()->value))
        {
            return ConfigurationSource::EnvironmentVariable;
        }
        else if ($this->getStore()->hasSetting($this->getPath()))
        {
            return ConfigurationSource::File;
        }
        else
        {
            return ConfigurationSource::None;
        }
    }

    /**
     * Gets the environment variable to use for overriding the attribute.
     *
     * @return EnvironmentVariable The environment variable to use for overriding the attribute.
     */
    public function getVariable(): EnvironmentVariable
    {
        return $this->variable;
    }

    /**
     * Gets the default value of the setting.
     *
     * @return mixed The default value of the setting.
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Gets the value of the setting.
     *
     * @return mixed The value of the setting.
     */
    public function getValue(): mixed
    {
        return $this->store->getValue($this->getPath(), $this->getVariable(), $this->getDefault());
    }

    /**
     * Sets the value of the setting.
     *
     * @param mixed $value The value to set.
     */
    public function setValue($value): void
    {
        $this->getStore()->setValue($this->getPath(), $value);
    }

    /**
     * Gets the configuration store containing this setting.
     *
     * @return ConfigurationStore The configuration store containing this setting.
     */
    protected function getStore(): ConfigurationStore
    {
        return $this->store;
    }
}
