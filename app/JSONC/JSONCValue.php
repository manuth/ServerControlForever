<?php

namespace Gizmo\ServerControlForever\JSONC;

use Illuminate\Support\Collection;
{
    /**
     * Represents a JSONC value.
     */
    class JSONCValue
    {
        /**
         * The comments associated with the value.
         *
         * @var Collection<CommentPosition, Collection<int, CommentBase>>
         */
        private Collection $comments;

        /**
         * The actual value.
         *
         * @var mixed
         */
        private mixed $value;

        /**
         * Initializes a new instance of the {@see JSONCValueBase} class.
         *
         * @param ValueType $type The type of the value.
         * @param mixed $value The actual value.
         */
        public function __construct($value)
        {
            $this->comments = new Collection();
            $this->value = $value;
        }

        /**
         * Gets the comments associated with the value.
         *
         * @return Collection<CommentPosition, Collection<int, CommentBase>> The comments associated with the value.
         */
        public function getComments(): Collection
        {
            return $this->comments;
        }

        /**
         * Gets the actual value.
         *
         * @return mixed The actual value.
         */
        public function getValue(): mixed
        {
            return $this->value;
        }

        /**
         * Sets the actual value.
         *
         * @param mixed $value The actual value.
         */
        public function setValue($value): void
        {
            $this->value = $value;
        }
    }
}
