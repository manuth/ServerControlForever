<?php

namespace Gizmo\ServerControlForever\JSONC;

use ArrayAccess;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Represents a JSONC object.
 */
class JSONCObjectBase extends JSONCValue implements ArrayAccess
{
    /**
     * The comments associated with the object.
     *
     * @var Collection<int|string, Collection<CommentPosition, Collection<int, CommentBase>>>
     */
    private Collection $accessorComments;

    /**
     * The type of the object.
     */
    private ComplexValueType $type;

    /**
     * Initializes a new instance of the {@see JSONCObject} class.
     *
     * @param ComplexValueType $type The type of the object.
     */
    public function __construct(ComplexValueType $type)
    {
        parent::__construct(new Collection());
        $this->accessorComments = new Collection();
        $this->type = $type;
    }

    /**
     * Gets the comments associated with the object.
     *
     * @return Collection<int|string, Collection<CommentPosition, Collection<int, CommentBase>>> The comments associated with the object.
     */
    public function getAccessorComments(): Collection
    {
        return $this->accessorComments;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return $this->getProperties()->has($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->getProperties()[$offset];
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        if ($offset === null)
        {
            if ($this->type === ComplexValueType::Object)
            {
                throw new InvalidArgumentException('Cannot add a value to an object without a key.');
            }
            else
            {
                $this->getProperties()[] = $value;
            }
        }
        else
        {
            $this->getProperties()[$offset] = $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->getProperties()[$offset]);
    }

    /**
     * Gets the type of the object.
     *
     * @return ComplexValueType The type of the object.
     */
    protected function getType(): ComplexValueType
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

    /**
     * Gets the properties of the object.
     *
     * @return Collection<int|string, mixed> The properties of the object.
     */
    public function getProperties(): Collection
    {
        return $this->getValue();
    }
}
