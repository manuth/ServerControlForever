<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents a jsonc array.
 */
class JSONCArray extends JSONCObjectBase
{
    /**
     * Initializes a new instance of the {@see JSONCObject} class.
     */
    public function __construct()
    {
        parent::__construct(ComplexValueType::Array);
    }
}
