<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the key of a property of a token.
 */
enum TokenKey: string
{
    /**
     * Indicates the key of the property holding the content.
     */
    case Content = 'content';

    /**
     * Indicates the key of the property holding the type.
     */
    case Type = 'code';

    /**
     * Indicates the key of the property holding the name of the type.
     */
    case TypeName = 'type';

    /**
     * Indicates the key of the property holding the line number.
     */
    case Line = 'line';

    /**
     * Indicates the key of the property holding the column number.
     */
    case Column = 'column';

    /**
     * Indicates the key of the property holding the length.
     */
    case Length = 'length';
}
