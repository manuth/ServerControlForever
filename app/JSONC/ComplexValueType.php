<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the type of a complex value.
 */
enum ComplexValueType
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
