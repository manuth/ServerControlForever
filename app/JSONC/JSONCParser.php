<?php

namespace Gizmo\ServerControlForever\JSONC;

use Composer\Autoload\ClassLoader;
use Illuminate\Support\Collection;
use PHP_CodeSniffer\Config;
use PHP_CodeSniffer\Tokenizers\JS;
use PHP_CodeSniffer\Util\Tokens;

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
        return $this->parseContainer($context, ContainerValueType::Object);
    }

    /**
     * Parses the current array in the specified {@see $context}.
     *
     * @param ParserContext $context The context containing the array to parse.
     * @return JSONCArray The parsed array.
     */
    protected function parseArray(ParserContext $context): JSONCArray
    {
        return $this->parseContainer($context, ContainerValueType::Array);
    }

    /**
     * Parses the current container in the specified {@see $context}.
     *
     * @param ParserContext $context The context of the parser.
     * @param 
     */
    protected function parseContainer(ParserContext $context, ContainerValueType $type): JSONCObjectBase
    {
        /**
         * @var JSONCObjectBase $container
         */
        $container;
        $isObject = $type === ContainerValueType::Object;

        if ($isObject)
        {
            $container = new JSONCObject();
        }
        else
        {
            $container = new JSONCArray();
        }

        $terminator = $isObject ? T_CLOSE_OBJECT : T_CLOSE_SQUARE_BRACKET;
        $preEntryPosition = $isObject ? CommentPosition::BeforeEntry : CommentPosition::BeforeValue;
        $context->consumeType($isObject ? T_OBJECT : T_OPEN_SQUARE_BRACKET);
        $context->getCommentStack()->push($container->getComments());
        $this->parseComments($context, CommentPosition::None, CommentPosition::BeforeContent);
        $first = true;
        $empty = true;

        /**
         * @var string|int $accessor
         */
        $accessor = 0;

        for ($i = 0; !$context->isFinished() && $context->getType() !== $terminator; $i++)
        {
            if (!$first)
            {
                $context->assignComments(CommentPosition::AfterValue);
                $context->consumeType(T_COMMA);
                $this->parseComments($context, CommentPosition::None, CommentPosition::AfterEntry);

                if ($context->getType() === $terminator)
                {
                    break;
                }
                else
                {
                    $context->getCommentStack()->pop();
                }
            }

            $empty = $first = false;

            if ($isObject)
            {
                $context->assertType(T_PROPERTY);
                $accessor = json_decode($context->read());
                $context->next();
            }
            else
            {
                $accessor = $i;
            }

            $commentCollection = new Collection();
            $container->getAccessorComments()->put($accessor, $commentCollection);
            $context->getCommentStack()->push($commentCollection);
            $context->assignComments($preEntryPosition);

            if ($isObject)
            {
                $this->parseComments($context, CommentPosition::AfterAccessor);
                $context->consumeType(T_COLON);
                $this->parseComments($context, CommentPosition::BeforeValue);
            }

            $container[$accessor] = $this->parseValue($context);
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
                $context->getCommentStack();
                $context->assignComments(CommentPosition::AfterContent);
            }

            $context->getCommentStack()->pop();
        }

        return $container;
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
