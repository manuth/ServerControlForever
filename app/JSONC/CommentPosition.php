<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents a comment position.
 */
enum CommentPosition: string
{
    /**
     * Indicates a comment without a specific position.
     */
    case None = "none";

    /**
     * Indicates a comment which appears before all content.
     */
    case BeforeAll = "beforeAll";

    /**
     * Indicates a comment which appears after all content.
     */
    case AfterAll = "afterAll";

    /**
     * Indicates a comment which appears at the content of an object or an array.
     */
    case BeforeContent = "beforeContent";

    /**
     * Indicates a comment which appears at the end of an object or an array.
     */
    case AfterContent = "afterContent";

    /**
     * Indicates a comment which appears before a property.
     */
    case BeforeEntry = "beforeEntry";

    /**
     * Indicates a comment which appears after a property or an array entry.
     */
    case AfterEntry = "afterEntry";

    /**
     * Indicates a comment which appears after the accessor of a property.
     */
    case AfterAccessor = "afterAccessor";

    /**
     * Indicates a comment which appears before a value.
     */
    case BeforeValue = "beforeValue";

    /**
     * Indicates a comment which appears after a value.
     */
    case AfterValue = "afterValue";
}
