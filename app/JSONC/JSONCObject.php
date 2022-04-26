<?php

namespace Gizmo\ServerControlForever\JSONC;

class JSONCObject extends JSONCObjectBase
{
    /**
     * Initializes a new instance of the {@see JSONCObject} class.
     */
    public function __construct()
    {
        parent::__construct(ValueType::Object);
    }
}
