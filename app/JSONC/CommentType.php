<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents the type of a comment.
 */
enum CommentType
{
    /**
     * Indicates a single line comment.
     */
    case Inline;

    /**
     * Indicates a multi-line comment.
     */
    case Block;

    /**
     * Indicates a documentation comment.
     */
    case Doc;
}
