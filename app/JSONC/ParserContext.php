<?php

namespace Gizmo\ServerControlForever\JSONC;

use AssertionError;
use Illuminate\Support\Collection;
use JsonException;
use SplStack;

/**
 * Represents the context of a parser.
 */
class ParserContext
{
    /**
     * The position of the parser.
     */
    private int $position = 0;

    /**
     * The tokens of the input.
     * @var Token[]
     */
    private array $tokens;

    /**
     * The name of the file which is being parsed.
     *
     * @var string
     */
    private ?string $fileName;

    /**
     * The comments to assign.
     *
     * @var SplStack<Collection<int, Comment>>
     */
    private $commentStack;

    /**
     * A set of unassigned comments.
     *
     * @var Collection<int, Comment>
     */
    private $unassignedComments;

    /**
     * A value indicating whether comments should be removed.
     */
    private bool $stripComments = false;

    /**
     * Initializes a new instance of the {@see ParserContext} class.
     *
     * @param mixed[][] $tokens The tokens of the input.
     * @param string $fileName The name of the file which is being parsed.
     */
    public function __construct(array $tokens, string $fileName = null)
    {
        $this->fileName = $fileName;
        $this->commentStack = new \SplStack();
        $this->unassignedComments = new Collection();

        foreach ($tokens as $token)
        {
            $this->tokens[] = new Token($token);
        }
    }

    /**
     * Gets the position of the parser.
     *
     * @return int The position of the parser.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Gets the tokens of the input.
     *
     * @return Token[] The tokens of the input.
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Gets the name of the file which is being parsed.
     *
     * @return string The name of the file which is being parsed.
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * Gets a stack containing assigned comments.
     *
     * @return SplStack<Collection<int, CommentBase>> A stack containing assigned comments.
     */
    public function getCommentStack(): SplStack
    {
        return $this->commentStack;
    }

    /**
     * Gets the current comment target.
     *
     * @return Collection The current comment target.
     */
    public function getComments(): Collection
    {
        return $this->getCommentStack()->top();
    }

    /**
     * Gets a set of unassigned comments.
     *
     * @return Collection<int, Comment> A set of unassigned comments.
     */
    public function getUnassignedComments(): Collection
    {
        return $this->unassignedComments;
    }

    /**
     * Gets the current token.
     *
     * @return Token The current token.
     */
    public function getToken(): Token
    {
        return $this->getTokens()[$this->position];
    }

    /**
     * Gets the current token type.
     *
     * @return string The current token type.
     */
    public function getType(): mixed
    {
        return $this->getToken()->getType();
    }

    /**
     * Gets the current token content.
     *
     * @return string The current token content.
     */
    public function getContent(): string
    {
        return $this->getToken()->getContent();
    }

    /**
     * Gets a value indicating whether the current token is a whitespace character.
     *
     * @return bool A value indicating whether the current token is a whitespace character.
     */
    public function isWhitespace(): bool
    {
        return $this->getToken()->isWhitespace();
    }

    /**
     * Gets a value indicating whether the current token is a comment.
     *
     * @return bool A value indicating whether the current token is a comment.
     */
    public function isComment(): bool
    {
        return $this->getToken()->isComment();
    }

    /**
     * Gets a value indicating whether the current token indicates an object start.
     *
     * @return bool A value indicating whether the current token indicates an object start.
     */
    public function isObject(): bool
    {
        return $this->getToken()->isObject();
    }

    /**
     * Gets a value indicating whether the current token indicates an array start.
     *
     * @return bool A value indicating whether the current token indicates an array start.
     */
    public function isArray(): bool
    {
        return $this->getToken()->isArray();
    }

    /**
     * Gets a value indicating whether the current token indicates a string.
     *
     * @return bool A value indicating whether the current token indicates a string.
     */
    public function isString(): bool
    {
        return $this->getToken()->isString();
    }

    /**
     * Gets a value indicating whether the current token indicates the start of a number.
     *
     * @return bool A value indicating whether the current token indicates the start of a number.
     */
    public function isNumber(): bool
    {
        return $this->getToken()->isNumber();
    }

    /**
     * A value indicating whether the parser is at the end of the input.
     *
     * @return boolean A value indicating whether the parser is at the end of the input.
     */
    public function isFinished(): bool
    {
        return $this->position >= count($this->tokens);
    }

    /**
     * Gets a value indicating whether comments should be removed.
     *
     * @return bool A value indicating whether comments should be removed.
     */
    public function getStripComments(): bool
    {
        return $this->stripComments;
    }

    /**
     * Sets a value indicating whether comments should be removed.
     *
     * @param bool $stripComments A value indicating whether comments should be removed.
     */
    public function setStripComments(bool $stripComments): void
    {
        $this->stripComments = $stripComments;
    }

    /**
     * Pushes the specified {@see $comments} to the corresponding comment stack.
     *
     * @param CommentPosition $position The position of the comments.
     * @param Comment[] $comments The comments to push.
     */
    public function pushComments(CommentPosition $position, Comment ...$comments): void
    {
        if ($position === CommentPosition::None)
        {
            $this->getUnassignedComments()->push(...$comments);
        }
        else
        {
            $this->getComments()->push(...$comments);
        }
    }

    /**
     * Assigns all comments, which haven't been assigned so far, to the current comment target.
     *
     * @param CommentPosition $position The position of the comments.
     */
    public function assignComments(CommentPosition $position): void
    {
        foreach ($this->getUnassignedComments() as $comment)
        {
            $comment->setPosition($position);
        }

        $this->getCommentStack()->push($this->getUnassignedComments());
        $this->getUnassignedComments()->splice(0);
        $this->getUnassignedComments()->count();
    }

    /**
     * Assigns all inline comments at the beginning of the set of unassigned comments to the current comment target.
     *
     * @param CommentPosition $position The position of the comments.
     */
    public function assignInlineComments(CommentPosition $position): void
    {
        $count = 0;

        foreach ($this->getUnassignedComments() as $comment)
        {
            if ($comment->getType() === CommentType::Inline)
            {
                $comment->setPosition($position);
                $count++;
            }
            else
            {
                break;
            }
        }

        $this->getUnassignedComments()->splice(0, $count);
    }

    /**
     * Reads the specified amount of characters from the current token without moving the reading position.
     *
     * @return string The characters read from the token.
     */
    public function peek(int $count = null): string
    {
        return $this->getToken()->peek($count);
    }

    /**
     * Reads the specified amount of characters from the current token and moves the reading position.
     *
     * @return string The characters read from the token.
     */
    public function read(int $count = null): string
    {
        return $this->getToken()->read($count);
    }

    /**
     * Increments the position of the parser.
     */
    public function next(): void
    {
        $this->position++;
    }

    /**
     * Consumes the current token if it matches the specified type.
     *
     * @param mixed $type The type of the token to consume.
     * @return string The content of the consumed token.
     */
    public function consumeType(mixed $type): string
    {
        $this->assertType($type);
        $result = $this->getContent();
        $this->next();
        return $result;
    }

    /**
     * Asserts the type of the current token and throws an exception if the type mismatches.
     *
     * @param mixed $type The asserted type.
     */
    public function assertType(mixed $type): void
    {
        if ($this->getType() !== $type)
        {
            throw new JsonException("Unexpected expression: `{$this->getContent()}`.");
        }
    }

    /**
     * Throws an exception with the specified {@see $message}.
     *
     * @param string $message The message of the exception.
     */
    public function throwError(string $message): void
    {
        $exceptionMessage = "An unexpected error occurred while parsing the JSON-code";

        if (!$this->isFinished())
        {
            $exceptionMessage .= " at line {$this->getToken()->getLine()}, column {$this->getToken()->getColumn()}";
        }

        $exceptionMessage .= ":" . PHP_EOL . $message;

        if ($this->getFileName() !== null)
        {
            $exceptionMessage .= PHP_EOL . "Location: {$this->getFileName()}";

            if (!$this->isFinished())
            {
                $exceptionMessage .= ":{$this->getToken()->getLine()}:{$this->getToken()->getColumn()}";
            }
        }

        throw new JsonException($exceptionMessage);
    }
}
