<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the type of a JSONC value.
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
