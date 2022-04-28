<?php

namespace Gizmo\ServerControlForever\JSONC;

use SplStack;

/**
 * Represents the context of a dumper.
 */
class DumperContext
{
    /**
     * The object to dump.
     */
    private $object;

    /**
     * The position of the dumper in the current line.
     */
    private int $linePosition = 0;

    /**
     * The content which has been dumped so far.
     */
    private string $content = "";

    /**
     * The current indentation level.
     */
    private int $indentationLevel = 0;

    /**
     * The width of the indentation.
     */
    private int $indentationWidth;

    /**
     * A value indicating whether comments should be dumped.
     */
    private bool $includeComments;

    /**
     * A set of flags for controlling the behavior of the dumper.
     */
    private int $flags;

    /**
     * A stack which holds the property names of the current leaf which is being dumped.
     *
     * @var SplStack<string>
     */
    private SplStack $propertyStack;

    /**
     * Initializes a new instance of the {@see DumperContext} class.
     *
     * @param mixed $object The object to dump.
     * @param int $width The width of the indentation. Specifying the width implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param bool $includeComments A value indicating whether to dump comments. Enabling comments implicitly enables the `JSON_PRETTY_PRINT` flag.
     * @param int $flags A set of flags for controlling the behavior of the dumper.
     */
    public function __construct(mixed $object, ?int $width = null, ?bool $includeComments = null, ?int $flags = null)
    {
        $this->includeComments = $includeComments ?? true;
        $this->flags = $flags ?? JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $this->object = $object;
        $this->indentationWidth = $width ?? 4;
        $this->propertyStack = new SplStack();

        if ($this->getIndentationWidth() || $this->getIncludeComments())
        {
            $this->flags |= JSON_PRETTY_PRINT;
        }
    }

    /**
     * Gets the object to dump.
     *
     * @return mixed The object to dump.
     */
    public function getRootObject(): mixed
    {
        return $this->object;
    }

    /**
     * Gets the current object.
     *
     * @return mixed The current object.
     */
    public function getCurrentObject(): mixed
    {
        $result = $this->getRootObject();
        $tree = collect($this->propertyStack)->reverse();

        foreach ($tree as $property)
        {
            $result = $result[$property];
        }

        return $result;
    }

    /**
     * Gets the current position of the dumper in the current line.
     *
     * @return int The current position of the dumper in the current line.
     */
    public function getLinePosition(): int
    {
        return $this->linePosition;
    }

    /**
     * Gets the content which has been dumped so far.
     *
     * @return string The content which has been dumped so far.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Gets the current indentation level.
     *
     * @return int The current indentation level.
     */
    public function getIndentationLevel(): int
    {
        return $this->indentationLevel;
    }

    /**
     * Gets the width of the indentation.
     *
     * @return int The width of the indentation.
     */
    public function getIndentationWidth(): int
    {
        return $this->indentationWidth;
    }

    /**
     * Gets a value indicating whether comments should be dumped.
     *
     * @return bool A value indicating whether comments should be dumped.
     */
    public function getIncludeComments(): bool
    {
        return $this->includeComments;
    }

    /**
     * Gets a value indicating whether pretty printing is enabled.
     *
     * @return bool A value indicating whether pretty printing is enabled.
     */
    public function getPrettyPrint(): bool
    {
        return ($this->flags & JSON_PRETTY_PRINT) > 0;
    }

    /**
     * Gets a set of flags for controlling the behavior of the dumper.
     *
     * @return int A set of flags for controlling the behavior of the dumper.
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * Gets the indentation string.
     *
     * @param int $count The number of spaces to indent.
     * @return string The indentation string.
     */
    public function getIndentationString(int $count = null): string
    {
        return str_repeat(" ", $count ?? ($this->getIndentationLevel() * $this->getIndentationWidth()));
    }

    /**
     * Gets a stack which holds the property names of the current leaf which is being dumped.
     *
     * @return SplStack<string> A stack which holds the property names of the current leaf which is being dumped.
     */
    public function getPropertyStack(): SplStack
    {
        return $this->propertyStack;
    }

    /**
     * Pushes the specified property name to the property stack.
     */
    public function pushProperty(string $property): void
    {
        $this->propertyStack->push($property);
    }

    /**
     * Pops the last property name from the property stack.
     */
    public function popProperty(): void
    {
        $this->propertyStack->pop();
    }

    /**
     * Increments the indentation level.
     */
    public function incrementIndentationLevel(): void
    {
        $this->indentationLevel++;
    }

    /**
     * Decrements the indentation level.
     */
    public function decrementIndentationLevel(): void
    {
        $this->indentationLevel--;
    }

    /**
     * Ensures that the cursor is at the beginning of a new line.
     */
    public function ensureNewLine(): void
    {
        if ($this->getPrettyPrint() && $this->getLinePosition() > 0)
        {
            $this->writeLine();
        }
    }

    /**
     * Writes the specified {@see line} to the output.
     *
     * @param string $line The line to write.
     */
    public function writeLine(string $line = ""): void
    {
        $text = $line;

        if ($this->getPrettyPrint())
        {
            $text .= PHP_EOL;
        }

        $this->write($text);
    }

    /**
     * Writes the specified {@see $content} to the output.
     *
     * @param string $content The content to write.
     */
    public function write(string $content): void
    {
        if (!$this->getPrettyPrint())
        {
            $content = trim($content);
        }

        $this->content .= $content;
        $this->linePosition += mb_strlen($content);

        if (str_ends_with($content, "\n"))
        {
            $this->linePosition = 0;
        }
    }

    /**
     * Writes the current indentation to the output.
     *
     * @param int $count The number of spaces to write.
     */
    public function writeIndent(int $count = null): void
    {
        if ($this->getPrettyPrint())
        {
            $this->write($count ? $this->getIndentationString($count) : $this->getIndentationString());
        }
    }

    /**
     * Writes an indentation to the output if the cursor is at the beginning of a new line.
     */
    public function indentIfNewline(): void
    {
        if ($this->getLinePosition() === 0)
        {
            $this->writeIndent();
        }
    }

    /**
     * Dumps the specified {@see $object} to a json string.
     */
    public function dumpJSON(mixed $object): string
    {
        return json_encode($object, $this->getFlags());
    }
}
