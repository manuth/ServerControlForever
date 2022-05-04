<?php

namespace Gizmo\ServerControlForever\Settings\Management;

use ArrayAccess;
use Gizmo\ServerControlForever\JSONC\JSONCObject;
use Gizmo\ServerControlForever\Settings\EnvironmentVariable;
use StringBackedEnum;

/**
 * Provides the functionality to store settings.
 */
class ConfigurationStore extends ConfigurationAccessor
{
    /**
     * The storage containing the settings.
     */
    private JSONCObject $storage;

    /**
     * Initializes a new instance of the {@see ConfigurationStore} class.
     *
     * @param JSONCObject $storage The storage containing the settings.
     */
    public function __construct(JSONCObject $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Gets the storage containing the settings.
     *
     * @return array|ArrayAccess The storage containing the settings.
     */
    protected function getStorage(): JSONCObject
    {
        return $this->storage;
    }

    /**
     * @inheritDoc
     */
    public function getValue($path, EnvironmentVariable $variable = null, $default = null): mixed
    {
        return parent::getValue($path, $variable, $default);
    }

    /**
     * @inheritDoc
     */
    public function hasSetting($path): bool
    {
        $result = $this->getStorage();

        foreach ($path as $key)
        {
            if (isset($result[$key->value]))
            {
                $result = $result[$key->value];
            }
            else
            {
                return false;
            }
        }
        
        return true;
    }

    /**
     * @inheritDoc
     */
    protected function getValueInternal($path): mixed
    {
        $result = $this->getStorage();

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
     * @inheritDoc
     */
    public function setValue($path, $value): void
    {
        $container = $this->getStorage();
        $containerPath = collect($path);
        /**
         * @var StringBackedEnum $lastKey
         */
        $lastKey = $containerPath->pop();
        $layers = collect();

        foreach ($containerPath as $key)
        {
            if (!isset($container[$key->value]))
            {
                $container[$key->value] = [];
            }

            $layers->push($container[$key->value]);
            $container = $container[$key->value];
        }

        $layers->pop();
        $container[$lastKey->value] = $value;

        foreach ($layers as $layer)
        {
            /**
             * @var StringBackedEnum $key
             */
            $key = $containerPath->pop();
            $layer[$key->value] = $container;
            $container = $layer;
        }
    }
}
