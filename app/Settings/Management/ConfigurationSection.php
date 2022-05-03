<?php

namespace Gizmo\ServerControlForever\Settings\Management;

use ArrayAccess;
use Illuminate\Support\Collection;
use ReflectionClass;
use StringBackedEnum;

/**
 * Represents the section of a configuration.
 */
class ConfigurationSection extends ConfigurationAccessor
{
    /**
     * The path to the root of the section.
     *
     * @var Collection<StringBackedEnum>
     */
    private Collection $rootPath;

    /**
     * Gets the configuration store containing this section.
     */
    private ConfigurationAccessor $configStore;

    /**
     * A set of all configured settings in this section.
     */
    private ?Collection $configurationSettings = null;

    /**
     * Initializes a new instance of the {@see ConfigurationSection} class.
     *
     * @param string[] $rootPath The path to the root of the section.
     * @param ConfigurationAccessor $configStore The configuration store containing this section.
     */
    protected function __construct(array | ArrayAccess $rootPath, ConfigurationStore $configStore)
    {
        $this->rootPath = collect($rootPath);
        $this->configStore = $configStore;
    }

    /**
     * Gets all configured settings in this section.
     *
     * A setting is considered configured if it is exposed using the {@see SettingAttribute} attribute.
     * @return ConfigurationSetting[] The configured settings in this section.
     */
    public function getConfigurationSettings(): array
    {
        if ($this->configurationSettings === null)
        {
            $this->configurationSettings = collect();
            $reflectionClass = new ReflectionClass($this);

            $addSettings = function ($value)
            {
                if (!is_array($value))
                {
                    $value = [$value];
                }

                $this->configurationSettings->push(...$value);
            };

            foreach ($reflectionClass->getProperties() as $property)
            {
                if (count($property->getAttributes(SettingAttribute::class)) > 0)
                {
                    $addSettings($property->getValue($this));
                }
            }

            foreach ($reflectionClass->getMethods() as $method)
            {
                if (count($method->getAttributes(SettingAttribute::class)) > 0)
                {
                    $addSettings($method->invoke($this));
                }
            }
        }

        return $this->configurationSettings->toArray();
    }

    /**
     * Gets the path to the root of the section.
     *
     * @return Collection<StringBackedEnum> The path to the root of the section.
     */
    protected function getRootPath(): Collection
    {
        return $this->rootPath;
    }

    /**
     * Gets the configuration store containing this section.
     *
     * @return ConfigurationStore The configuration store containing this section.
     */
    protected function getStore(): ConfigurationStore
    {
        return $this->configStore;
    }

    /**
     * Gets a configuration path relative to the root of the section.
     *
     * @param StringBackedEnum[] $path The path to the configuration.
     */
    protected function getPath(...$path): Collection
    {
        return $this->getRootPath()->concat($path);
    }

    /**
     * @inheritDoc
     */
    protected function hasSetting($path): bool
    {
        return $this->getStore()->hasSetting($this->getPath(...$path));
    }

    /**
     * @inheritDoc
     */
    protected function getValueInternal($path): mixed
    {
        return $this->getStore()->getValue($this->getPath(...$path));
    }

    /**
     * @inheritDoc
     */
    protected function setValue($path, $value): void
    {
        $this->getStore()->setValue($this->getPath(...$path), $value);
    }

    /**
     * Creates a new section from the specified path.
     *
     * @param StringBackedEnum[] $path The path to the section.
     */
    protected function getSection(...$path): ConfigurationSection
    {
        return new ConfigurationSection($this->getPath(...$path), $this->getStore());
    }
}
