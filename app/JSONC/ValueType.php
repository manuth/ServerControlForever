<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the type of a JSONC value.
 */
enum ValueType
{
    /**
     * Indicates an object.
     */
    case Object;

    /**
     * Indicates an array.
     */
    case Array;
}
