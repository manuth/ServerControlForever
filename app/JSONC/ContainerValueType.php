<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the type of a container JSONC value.
 */
enum ContainerValueType
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
