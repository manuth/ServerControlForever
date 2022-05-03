<?php

namespace Gizmo\ServerControlForever\Settings;

use ArrayAccess;
use Illuminate\Support\Collection;
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
    protected function getValue(...$path)
    {
        return $this->getStore()->getValue(...$this->getPath(...$path));
    }

    /**
     * @inheritDoc
     */
    protected function setValue($value, ...$path): void
    {
        $this->getStore()->setValue($value, ...$this->getPath(...$path));
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
