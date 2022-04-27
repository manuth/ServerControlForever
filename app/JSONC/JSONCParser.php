<?php

namespace Gizmo\ServerControlForever\JSONC;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Tokenizers\JS;
use PHP_CodeSniffer\Util\Tokens;
use stdClass;

/**
 * Provides the functionality to parse `.jsonc`-code.
 */
class JSONCParser
{
    /**
     * Initializes a new instance of the {@see JSONCParser} class.
     */
    public function __construct()
    {
    }

    /**
     * Parses the given `.jsonc`-code.
     *
     * @param string $code The `.jsonc`-code to parse.
     * @return JSONCValue The parsed JSON-object.
     * @throws JSONCParserException Thrown if the given `.jsonc`-code is invalid.
     */
    public function parse(string $code, string $fileName = null): JSONCValue
    {
        class_exists(Tokens::class);
        /**
         * @var ClassLoader $autoLoader
         */
        define('PHP_CODESNIFFER_VERBOSITY', 0);
        $context = new ParserContext((new JS($code, new Config(['--']), "\n"))->getTokens(), $fileName);
        return $this->parseCode($context);
    }

    /**
     * Checks whether the specified {@see $token} marks the end of a comment.
     *
     * @return bool A value indicating whether the specified {@see $token} marks the end of a comment.
     */
    protected function isCommentEnd(Token $token): bool
    {
        return $token->peek(2) === '*/';
    }

    /**
     * Parses the code in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the code to parse.
     * @return object The parsed JSONC-object.
     */
    protected function parseCode(ParserContext $context): JSONCValue
    {
        $context->getCommentStack()->push(new Collection());
        $this->parseComments($context, CommentPosition::BeforeAll);
        $result = $this->parseRoot($context);
        $this->parseComments($context, CommentPosition::AfterAll, CommentPosition::AfterValue);

        foreach ($context->getComments() as $key => $comments)
        {
            $result->getComments()->put($key, $comments);
        }

        $context->getCommentStack()->pop();

        if (!$context->isFinished())
        {
            $context->throwError("Unexpected token after end of JSONC-code: " . $context->getToken()->getContent());
        }

        return $result;
    }

    /**
     * Parses the root value in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the code to parse.
     * @return JSONCValue The parsed JSONC value.
     */
    protected function parseRoot(ParserContext $context): JSONCValue
    {
        $result = $this->parseValue($context);

        if (
            is_string($result) ||
            is_int($result) ||
            is_bool($result) ||
            is_null($result)
        )
        {
            $result = new JSONCValue($result);
        }

        return $result;
    }

    /**
     * Parses the current value in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the value to parse.
     * @return object The parsed value.
     */
    protected function parseValue(ParserContext $context): JSONCObject | JSONCArray | string | int | float | bool | null
    {
        /**
         * @var object $result
         */
        $result;

        if ($context->isObject())
        {
            $result = $this->parseObject($context);
        }
        else if ($context->isArray())
        {
            $result = $this->parseArray($context);
        }
        else if ($context->isString())
        {
            $result = $this->parseString($context);
        }
        else if ($context->isNumber())
        {
            $result = $this->parseNumber($context);
        }
        else if ($context->getType() === T_NULL)
        {
            $result = null;
            $context->next();
        }
        else if ($context->getType() === T_TRUE)
        {
            $result = true;
            $context->next();
        }
        else if ($context->getType() === T_FALSE)
        {
            $result = false;
            $context->next();
        }
        else if ($context->isFinished())
        {
            $this->throwEndOfInputException($context);
        }
        else
        {
            $context->throwError("Unexpected expression `{$context->getToken()->getContent()}`.");
        }

        return $result;
    }

    /**
     * Parses the current object in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the object to parse.
     * @return JSONCObject The parsed object.
     */
    protected function parseObject(ParserContext $context): JSONCObject
    {
        $result = new JSONCObject();
        $context->next();
        $context->getCommentStack()->push($result->getComments());
        $this->parseComments($context, CommentPosition::None, CommentPosition::BeforeContent);
        $first = true;
        $empty = true;

        /**
         * @var string $propertyName
         */
        $propertyName;

        $finalizeProperty = function (ParserContext $context)
        {
            $context->getCommentStack()->pop();
        };

        while (!$context->isFinished() && $context->getType() !== T_CLOSE_OBJECT)
        {
            if (!$first)
            {
                $context->assignComments(CommentPosition::AfterValue);
                $context->consumeType(T_COMMA);
                $this->parseComments($context, CommentPosition::None, CommentPosition::AfterEntry);

                if ($context->getType() === T_CLOSE_OBJECT)
                {
                    break;
                }
                else
                {
                    $finalizeProperty($context);
                }
            }

            $empty = $first = false;
            $context->assertType(T_PROPERTY);
            $propertyName = json_decode($context->read());
            $context->next();
            $commentCollection = new Collection();
            $result->getAccessorComments()->put($propertyName, $commentCollection);
            $context->getCommentStack()->push($commentCollection);
            $context->assignComments(CommentPosition::BeforeEntry);
            $this->parseComments($context, CommentPosition::AfterAccessor);
            $context->consumeType(T_COLON);
            $this->parseComments($context, CommentPosition::BeforeValue);
            $result[$propertyName] = $this->parseValue($context);
            $this->parseComments($context, CommentPosition::None, CommentPosition::AfterValue);
        }

        if ($context->isFinished())
        {
            $this->throwEndOfInputException($context);
        }
        else
        {

            $context->next();

            if ($empty)
            {
                $context->assignComments(CommentPosition::BeforeContent);
            }
            else
            {
                $finalizeProperty($context);
                $context->assignComments(CommentPosition::AfterContent);
            }

            $context->getCommentStack()->pop();
            return $result;
        }
    }

    /**
     * Parses the current array in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the array to parse.
     * @return JSONCArray The parsed array.
     */
    protected function parseArray(ParserContext $context): JSONCArray
    {
        $result = new JSONCArray();
        $context->next();
        $context->getCommentStack()->push($result->getComments());
        $this->parseComments($context, CommentPosition::None, CommentPosition::BeforeContent);
        $first = true;
        $empty = true;

        $finalizeEntry = function (ParserContext $context)
        {
            $context->getCommentStack()->pop();
        };

        for ($index = 0; !$context->isFinished() && $context->getType() !== T_CLOSE_SQUARE_BRACKET; $index++)
        {
            if (!$first)
            {
                $context->assignComments(CommentPosition::AfterValue);
                $context->consumeType(T_COMMA);
                $this->parseComments($context, CommentPosition::None, CommentPosition::AfterEntry);

                if ($context->getType() === T_CLOSE_SQUARE_BRACKET)
                {
                    break;
                }
                else
                {
                    $finalizeEntry($context);
                }
            }

            $empty = $first = false;
            $commentCollection = new Collection();
            $result->getAccessorComments()->put($index, $commentCollection);
            $context->getCommentStack()->push($commentCollection);
            $context->assignComments(CommentPosition::BeforeValue);
            $result[$index] = $this->parseValue($context);
            $this->parseComments($context, CommentPosition::None, CommentPosition::AfterValue);
        }

        if ($context->isFinished())
        {
            $this->throwEndOfInputException($context);
        }
        else
        {
            $context->next();

            if ($empty)
            {
                $context->assignComments(CommentPosition::BeforeContent);
            }
            else
            {
                $finalizeEntry($context);
                $context->assignComments(CommentPosition::AfterContent);
            }

            $context->getCommentStack()->pop();
            return $result;
        }
    }

    /**
     * Parses the current string in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the string to parse.
     * @return string The parsed string.
     */
    protected function parseString(ParserContext $context): string
    {
        $value = json_decode($context->read());
        $context->next();
        return $value;
    }

    /**
     * Parses the current number in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the number to parse.
     * @return int|float The parsed number.
     */
    protected function parseNumber(ParserContext $context): int | float
    {
        $content = "";

        while ($context->isNumber())
        {
            $content .= $context->read();
            $context->next();
        }

        $result = json_decode($content);
        return $result;
    }

    /**
     * Parses the current comments in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the comments to parse.
     * @param CommentPosition $position The position to save the comments to.
     * @param CommentPosition $inlinePosition The comment position to save inline comments to.
     */
    protected function parseComments(ParserContext $context, CommentPosition $position = CommentPosition::None, CommentPosition $inlinePosition = null): void
    {
        $inlinePosition = $inlinePosition ?? $position;
        /**
         * @var Comment[] $comments
         */
        $comments = [];
        /**
         * @var Comment[] $comments
         */
        $inlineComments = [];
        $inline = $inlinePosition !== null;

        while (!$context->isFinished() && ($context->isWhitespace() || $context->isComment()))
        {
            if ($inline)
            {
                $currentLine ??= $context->getToken()->getLine();
                $currentPosition ??= $context->getToken()->getColumn();

                if ($context->isWhitespace())
                {
                    $this->skipWhitespace($context);
                }

                if (!$context->isFinished())
                {
                    if (
                        ($currentLine === $context->getToken()->getLine()) ||
                        ($currentPosition === $context->getToken()->getColumn())
                    )
                    {
                        $currentLine = $context->getToken()->getLine() + count(explode("\n", trim($context->getContent()))) - 1;
                        $currentPosition = $context->getToken()->getColumn();
                    }
                    else
                    {
                        $inline = false;
                    }
                }
            }
            else
            {
                $this->skipWhitespace($context);
            }

            if (!$context->isFinished() && $context->isComment())
            {
                /**
                 * @var Comment $comment
                 */
                $comment;
                $chars = $context->peek(2);

                if ($chars === '/*')
                {
                    $comment = $this->parseBlockComment($context);
                }
                else if ($chars === '//')
                {
                    $comment = $this->parseLineComment($context);
                }
                else
                {
                    $context->throwError('Malformed comment.');
                }

                if ($inline)
                {
                    $inlineComments[] = $comment;
                }
                else
                {
                    $comments[] = $comment;
                }
            }
        }

        $context->pushComments($inlinePosition, ...$inlineComments);
        $context->pushComments($position, ...$comments);
    }

    /**
     * Parses the current block comment in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the block comment to parse.
     * @return Comment The parsed block comment.
     */
    protected function parseBlockComment(ParserContext $context): Comment
    {
        $isDocComment = $context->getType() === T_DOC_COMMENT_OPEN_TAG;
        $context->read($isDocComment ? 3 : 2);
        $content = "";

        while (!$this->isCommentEnd($context->getToken()))
        {
            if ($context->getToken()->isFinished())
            {
                $context->next();
            }

            if ($context->isComment())
            {
                while (!$context->getToken()->isFinished() && !$this->isCommentEnd($context->getToken()))
                {
                    $content .= $this->parseCommentLine($context) . "\n";
                }
            }
            else
            {
                $context->throwError('Unterminated comment.');
            }
        }

        $context->next();
        return new Comment($isDocComment ? CommentType::Doc : CommentType::Block, trim($content));
    }

    /**
     * Parses the current inline comment in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the inline comment to parse.
     * @return Comment The parsed inline comment.
     */
    protected function parseLineComment(ParserContext $context): Comment
    {
        $content = trim(mb_substr($context->getContent(), 2));
        $context->next();
        return new Comment(CommentType::Inline, $content);
    }

    /**
     * Parses the current comment line in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the comment line to parse.
     * @return string The parsed comment line.
     */
    protected function parseCommentLine(ParserContext $context): string
    {
        $result = "";

        for (
            $tokenType = $context->getType();
            $tokenType === T_DOC_COMMENT_WHITESPACE || $tokenType === T_DOC_COMMENT_STAR;
            $tokenType = $context->getType()
        )
        {
            $context->next();
        }

        while (!$this->isCommentEnd($context->getToken()))
        {
            $char = $context->read(1);

            if ($char === "\n")
            {
                break;
            }
            else
            {
                $result .= $char;
            }

            if ($context->getToken()->isFinished())
            {
                $context->next();
            }
        }

        return trim($result);
    }

    /**
     * Skips whitespace characters in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the whitespace characters to skip.
     */
    protected function skipWhitespace(ParserContext $context)
    {
        while (!$context->isFinished() && $context->isWhitespace())
        {
            $context->next();
        }
    }

    /**
     * Throws an exception stating that the end of the input has been reached unexpectedly.
     *
     * @param ParserContext $context The context containing the unexpected end of input.
     */
    protected function throwEndOfInputException(ParserContext $context): void
    {
        $context->throwError("Unexpected end of JSONC input.");
    }
}
