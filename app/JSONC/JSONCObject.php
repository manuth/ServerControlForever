<?php

namespace Gizmo\ServerControlForever\JSONC;

class JSONCObject extends JSONCObjectBase
{
    /**
     * Initializes a new instance of the {@see JSONCObject} class.
     *
     * @param Token[] $tokens An array of jsonc tokens.
     */
    public function __construct(array $tokens = [])
    {
        parent::__construct(ComplexValueType::Object, $tokens);
    }
}
