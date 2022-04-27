<?php

namespace Gizmo\ServerControlForever\JSONC;

/**
 * Represents a token.
 */
class Token
{
    /**
     * The data of the token.
     *
     * @var mixed[]
     */
    private array $token;

    /**
     * The reading position of the token.
     *
     * @var int
     */
    private int $position = 0;

    /**
     * Initializes a new instance of the {@see Token} class.
     *
     * @param mixed[] $token The data of the token.
     */
    public function __construct(array $token)
    {
        $this->token = $token;
    }

    /**
     * Gets the data of the token.
     *
     * @return mixed[] The data of the token.
     */
    public function getToken(): array
    {
        return $this->token;
    }

    /**
     * Gets the reading position of the token.
     *
     * @return int The reading position of the token.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Gets the type of the token.
     *
     * @return mixed The type of the token.
     */
    public function getType(): mixed
    {
        return $this->getToken()[TokenKey::Type->value];
    }

    /**
     * Gets the line of the token.
     *
     * @return int The line of the token.
     */
    public function getLine(): int
    {
        return $this->getToken()[TokenKey::Line->value];
    }

    /**
     * Gets the column of the token.
     *
     * @return int The column of the token.
     */
    public function getColumn(): int
    {
        return $this->getToken()[TokenKey::Column->value];
    }

    /**
     * Gets the length of the token.
     *
     * @return int The length of the token.
     */
    public function getLength(): int
    {
        return mb_strlen($this->getContent());
    }

    /**
     * Gets the content of the token.
     *
     * @return string The content of the token.
     */
    public function getContent(): string
    {
        return $this->getToken()[TokenKey::Content->value];
    }

    /**
     * Gets a value indicating whether the token has been read.
     *
     * @return boolean A value indicating whether the token has been read.
     */
    public function isFinished(): bool
    {
        return $this->position >= $this->getLength();
    }

    /**
     * Gets a value indicating whether the token is a whitespace character.
     *
     * @return bool A value indicating whether the token is a whitespace character.
     */
    public function isWhitespace(): bool
    {
        $tokenType = $this->getType();

        return $tokenType === T_OPEN_TAG ||
            $tokenType === T_CLOSE_TAG ||
            $tokenType === T_WHITESPACE;
    }

    /**
     * Gets a value indicating whether the token is a comment.
     *
     * @return bool A value indicating whether the token is a comment.
     */
    public function isComment(): bool
    {
        $tokenType = $this->getType();

        return $tokenType === T_COMMENT ||
            $tokenType === T_DOC_COMMENT ||
            $tokenType === T_DOC_COMMENT_OPEN_TAG ||
            $tokenType === T_DOC_COMMENT_CLOSE_TAG ||
            $tokenType === T_DOC_COMMENT_STRING ||
            $tokenType === T_DOC_COMMENT_WHITESPACE;
    }

    /**
     * Gets a value indicating whether the current token indicates an object start.
     *
     * @return bool A value indicating whether the current token indicates an object start.
     */
    public function isObject(): bool
    {
        $tokenType = $this->getType();
        return $tokenType === T_OBJECT ||
            $tokenType === T_OPEN_CURLY_BRACKET;
    }

    /**
     * Gets a value indicating whether the current token indicates an array start.
     *
     * @return bool A value indicating whether the current token indicates an array start.
     */
    public function isArray(): bool
    {
        return $this->getType() === T_OPEN_SQUARE_BRACKET;
    }

    /**
     * Gets a value indicating whether the current token indicates a string.
     *
     * @return bool A value indicating whether the current token indicates a string.
     */
    public function isString(): bool
    {
        return $this->getType() === T_CONSTANT_ENCAPSED_STRING;
    }

    /**
     * Gets a value indicating whether the current token indicates the start of a number.
     *
     * @return bool A value indicating whether the current token indicates the start of a number.
     */
    public function isNumber(): bool
    {
        $tokenType = $this->getType();
        return $tokenType === T_DNUMBER ||
            $tokenType === T_LNUMBER ||
            $tokenType === T_STRING ||
            $tokenType === T_MINUS;
    }

    /**
     * Reads the specified amount of upcoming characters without moving the reading position.
     *
     * @param int $count The amount of upcoming characters to read.
     * @return string The characters read from the token.
     */
    public function peek(int $count = null): string
    {
        return mb_substr($this->getContent(), $this->getPosition(), $count);
    }

    /**
     * Reads the specified number of characters from the token and moves the reading position accordingly.
     *
     * @param int $count The number of characters to read.
     * @return string The characters read from the token.
     */
    public function read(int $count = null): string
    {
        $result = $this->peek($count);
        $this->position += $count;
        return $result;
    }
}
