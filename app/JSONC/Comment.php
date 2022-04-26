<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents a comment.
 */
class Comment
{
    /**
     * The position of the comment.
     */
    private CommentPosition $position;

    /**
     * The type of the comment.
     */
    private CommentType $type;

    /**
     * The content of the comment.
     */
    private string $content;

    /**
     * Initializes a new instance of the {@see Comment} class.
     *
     * @param CommentPosition $position The position of the comment.
     * @param CommentType $type The type of the comment.
     * @param string $content The content of the comment.
     */
    public function __construct(CommentPosition $position, CommentType $type, string $content)
    {
        $this->position = $position;
        $this->type = $type;
        $this->content = $content;
    }

    /**
     * Gets the position of the comment.
     *
     * @return CommentPosition The position of the comment.
     */
    public function getPosition(): CommentPosition
    {
        return $this->position;
    }

    /**
     * Sets the position of the comment.
     *
     * @param CommentPosition $position The position of the comment.
     */
    public function setPosition(CommentPosition $position): void
    {
        $this->position = $position;
    }

    /**
     * Gets the type of the comment.
     *
     * @return CommentType The type of the comment.
     */
    public function getType(): CommentType
    {
        return $this->type;
    }

    /**
     * Gets a value indicating whether the comment is a block comment.
     *
     * @return bool A value indicating whether the comment is a block comment.
     */
    public function isBlockComment(): bool
    {
        return $this->getType() === CommentType::Block;
    }

    /**
     * Gets a value indicating whether the comment is a doc comment.
     *
     * @return bool A value indicating whether the comment is a doc comment.
     */
    public function isDocComment(): bool
    {
        return $this->getType() === CommentType::Doc;
    }

    /**
     * Gets the content of the comment.
     *
     * @return string The content of the comment.
     */
    public function getContent(): string
    {
        return $this->content;
    }
}
