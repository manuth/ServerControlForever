<?php

namespace Gizmo\ServerControlForever\JSONC;

use ArrayAccess;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Represents a JSONC object.
 */
class JSONCObjectBase implements ArrayAccess
{
    /**
     * The type of the object.
     */
    private ValueType $type;

    /**
     * The properties of the object.
     *
     * @var Collection<int|string, mixed>
     */
    private $properties = [];

    /**
     * Initializes a new instance of the {@see JSONCObject} class.
     *
     * @param ValueType $type The type of the object.
     */
    public function __construct(ValueType $type)
    {
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return $this->properties->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->properties[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null)
        {
            if ($this->type === ValueType::Object)
            {
                throw new InvalidArgumentException('Cannot add a value to an object without a key.');
            }
            else
            {
                $this->properties[] = $value;
            }
        }
        else
        {
            $this->properties[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }

    /**
     * Gets the type of the object.
     *
     * @return ValueType The type of the object.
     */
    protected function getType(): ValueType
    {
        return $this->type;
    }

    /**
     * Gets the tokens of the object.
     *
     * @return Token[] An array of jsonc tokens.
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }
}
