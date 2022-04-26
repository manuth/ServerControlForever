<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents a comment position.
 */
enum CommentPosition
{
    /**
     * Indicates a comment without a specific position.
     */
    case None;

    /**
     * Indicates a comment which appears before all content.
     */
    case BeforeAll;

    /**
     * Indicates a comment which appears after all content.
     */
    case AfterAll;

    /**
     * Indicates a comment which appears at the content of an object or an array.
     */
    case BeforeContent;

    /**
     * Indicates a comment which appears at the end of an object or an array.
     */
    case AfterContent;

    /**
     * Indicates a comment which appears before a property.
     */
    case BeforeEntry;

    /**
     * Indicates a comment which appears after a property or an array entry.
     */
    case AfterEntry;

    /**
     * Indicates a comment which appears after the accessor of a property.
     */
    case AfterAccessor;

    /**
     * Indicates a comment which appears before a value.
     */
    case BeforeValue;

    /**
     * Indicates a comment which appears after a value.
     */
    case AfterValue;
}
