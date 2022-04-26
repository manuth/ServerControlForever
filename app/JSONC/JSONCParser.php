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
     * Gets the current comment target.
     *
     * @return Collection The current comment target.
     */
    protected function getComments(): Collection
    {
        return $this->commentStack->top();
    }

    /**
     * Parses the given `.jsonc`-code.
     *
     * @param string $code The `.jsonc`-code to parse.
     * @return object The parsed JSON-object.
     * @throws JSONCParserException Thrown if the given `.jsonc`-code is invalid.
     */
    public function parse(string $code): object
    {
        class_exists(Tokens::class);
        /**
         * @var ClassLoader $autoLoader
         */
        define('PHP_CODESNIFFER_VERBOSITY', 0);
        $context = new ParserContext((new JS($code, new Config(['--']), "\n"))->getTokens());
        $this->skipWhitespace($context);
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
    protected function parseCode(ParserContext $context): object
    {
        $context->getCommentStack()->push(new Collection());
        $this->parseComments($context, CommentPosition::BeforeAll);
        $result = $this->parseValue($context);
        $this->skipWhitespace($context);
        $this->parseComments($context, CommentPosition::AfterAll);
        $context->getCommentStack()->pop();
        return $result;
    }

    /**
     * Parses the current value in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the value to parse.
     * @return object The parsed value.
     */
    protected function parseValue(ParserContext $context): object
    {
        /**
         * @var object $result
         */
        $result;

        if ($context->isObject())
        {
            $result = $this->parseObject($context);
        }
        else if($context->isArray())
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
        else if ($context->isFinished)
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
     * @return object The parsed object.
     */
    protected function parseObject(ParserContext $context): object
    {
        $context->next();
        $this->skipWhitespace($context);
        $context->getCommentStack()->push(new Collection());
        $this->parseComments($context);
        $this->skipWhitespace($context);
        $first = true;
        $empty = true;

        /**
         * @var string $propertyName
         */
        $propertyName;

        $finalizeProperty = function (ParserContext $context, CommentPosition $position)
        {
            $this->skipWhitespace($context);
            $context->assignComments($position);
            // TODO: assign comments to corresponding property
            $context->getCommentStack()->pop();
        };

        while (!$context->isFinished() && $context->getType() !== T_CLOSE_OBJECT)
        {
            if (!$first)
            {
                $finalizeProperty($context, CommentPosition::AfterValue);
                $context->consumeType(T_COMMA);
                $this->skipWhitespace($context);
                $this->parseComments($context);
                $context->assignInlineComments(CommentPosition::AfterEntry);
                $context->getCommentStack()->pop();

                if ($context->getType() === T_CLOSE_OBJECT)
                {
                    break;
                }
            }

            $empty = $first = false;
            $context->assertType(T_PROPERTY);
            $propertyName = json_decode($context->read());
            $context->next();
            $context->getCommentStack()->push(new Collection());
            $context->assignComments(CommentPosition::BeforeEntry);
            $this->skipWhitespace($context);
            $this->parseComments($context, CommentPosition::AfterAccessor);
            $context->consumeType(T_COLON);
            $this->skipWhitespace($context);
            $this->parseComments($context, CommentPosition::BeforeValue);
            $value = $this->parseValue($context);
            $this->skipWhitespace($context);
            $this->parseComments($context);
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
                $context->getCommentStack()->pop();
                $context->assignComments(CommentPosition::AfterContent);
            }
    
            // TODO: Assign comments to the object
            $context->getCommentStack()->pop();
            return new stdClass();
        }
    }

    /**
     * Parses the current array in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the array to parse.
     * @return object The parsed array.
     */
    protected function parseArray(ParserContext $context): object
    {
        $context->next();
        $this->skipWhitespace($context);
        $context->getCommentStack()->push(new Collection());
        $this->parseComments($context);
        $this->skipWhitespace($context);
        $first = true;
        $empty = true;
        $index = 0;

        while (!$context->isFinished() && $context->getType() !== T_CLOSE_SQUARE_BRACKET)
        {
            if (!$first)
            {
                $context->assignComments(CommentPosition::AfterValue);
                $context->consumeType(T_COMMA);
                $this->skipWhitespace($context);
                $this->parseComments($context);
                $this->skipWhitespace($context);
                $context->assignInlineComments(CommentPosition::AfterEntry);
                $context->getCommentStack()->pop();

                if ($context->getType() === T_CLOSE_SQUARE_BRACKET)
                {
                    break;
                }
            }

            $empty = $first = false;
            $context->getCommentStack()->push(new Collection());
            $context->assignComments(CommentPosition::BeforeValue);
            $value = $this->parseValue($context);
            $this->parseComments($context);
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
                $context->getCommentStack()->pop();
                $context->assignComments(CommentPosition::AfterContent);
            }
    
            // TODO: Assign comments to the array
            $context->getCommentStack()->pop();
            return new stdClass();
        }
    }

    /**
     * Parses the current string in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the string to parse.
     * @return object The parsed string.
     */
    protected function parseString(ParserContext $context): object
    {
        $value = json_decode($context->read());
        $context->next();
        $this->skipWhitespace($context);
        return new stdClass();
    }

    /**
     * Parses the current number in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the number to parse.
     * @return object The parsed number.
     */
    protected function parseNumber(ParserContext $context): object
    {
        $content = "";

        while ($context->isNumber())
        {
            $content .= $context->read();
            $context->next();
        }

        $result = json_decode($content);
        $this->skipWhitespace($context);
        return new stdClass();
    }

    /**
     * Parses the current comments in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the comments to parse.
     * @param CommentPosition $position The position to save the comments to.
     */
    protected function parseComments(ParserContext $context, CommentPosition $position = CommentPosition::None): void
    {
        /**
         * @var Comment[] $comments
         */
        $comments = [];

        while (!$context->isFinished() && $context->isComment())
        {
            $chars = $context->peek(2);

            if ($chars === '/*')
            {
                $comments[] = $this->parseBlockComment($context, $position);
            }
            else if ($chars === '//')
            {
                $comments[] = $this->parseLineComment($context, $position);
            }
            else
            {
                $context->throwError('Malformed comment.');
            }

            $this->skipWhitespace($context);
        }

        $context->pushComments($position, ...$comments);
    }

    /**
     * Parses the current block comment in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the block comment to parse.
     * @param CommentPosition $position The position to save the comment to.
     * @return Comment The parsed block comment.
     */
    protected function parseBlockComment(ParserContext $context, CommentPosition $position): Comment
    {
        $isDocComment = $context->getType() === T_DOC_COMMENT_OPEN_TAG;
        $context->read($isDocComment ? 3 : 2);
        $content = $context->peek();

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
        return new Comment($position, $isDocComment ? CommentType::Doc : CommentType::Block, trim($content));
    }

    /**
     * Parses the current inline comment in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the inline comment to parse.
     * @param CommentPosition $position The position to save the comment to.
     * @return Comment The parsed inline comment.
     */
    protected function parseLineComment(ParserContext $context, CommentPosition $position): Comment
    {
        $content = trim(substr($context->getContent(), 2));
        $context->next();
        return new Comment($position, CommentType::Inline, $content);
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
                return $result;
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

        return $result;
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
