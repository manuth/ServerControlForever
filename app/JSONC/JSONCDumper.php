<?php

namespace Gizmo\ServerControlForever\JSONC;

use Illuminate\Support\Collection;

{
    /**
     * Provides the functionality to dump JSONC code.
     */
    class JSONCDumper
    {
        /**
         * Dumps the given JSONC code.
         *
         * @param mixed $object The object to dump.
         * @param int $width The width of the indentation.
         * @return string The dumped JSONC code.
         */
        public function dump(mixed $object, int $width = 4): string
        {
            $context = new DumperContext($object, $width);
            $this->writeRoot($context);
            return $context->getContent();
        }

        /**
         * Dumps the specified literal.
         *
         * @param mixed $value The value to dump.
         */
        protected function dumpLiteral(mixed $value): string
        {
            return json_encode($value);
        }

        /**
         * Dumps the specified comment.
         *
         * @param Comment $comment The comment to dump.
         */
        protected function dumpComment(Comment $comment): string
        {
            switch ($comment->getType())
            {
                case CommentType::Inline:
                    return $this->dumpInlineComment($comment);
                case CommentType::Block:
                    return $this->dumpBlockComment($comment);
                case CommentType::Doc:
                    return $this->dumpDocComment($comment);
            }
        }

        /**
         * Dumps the specified inline comment.
         *
         * @param Comment $comment The comment to dump.
         */
        protected function dumpInlineComment(Comment $comment): string
        {
            return "// " . $comment->getContent();
        }

        /**
         * Dumps the specified block comment.
         *
         * @param Comment $comment The comment to dump.
         */
        protected function dumpBlockComment(
            Comment $comment
        ): string
        {
            return "/* " . $this->joinLines($comment->getContent(), "\n   ") . " */";
        }

        /**
         * Dumps the specified doc comment.
         *
         * @param Comment $comment The comment to dump.
         */
        protected function dumpDocComment(Comment $comment): string
        {
            $separator = "\n * ";
            return "/**" . $separator . $this->joinLines($comment->getContent(), "\n * ") . "\n */";
        }

        /**
         * Writes the root object.
         *
         * @param DumperContext $context The context of the dumper.
         */
        protected function writeRoot(DumperContext $context): void
        {
            $object = $context->getRootObject();

            if ($object instanceof JSONCValue)
            {
                /**
                 * @var Collection<string,Collection> $comments
                 */
                $comments = $object->getComments();
                $this->writeComments($context, $comments->get(CommentPosition::BeforeAll->value));
                $context->ensureNewline();
                $this->writeValue($context);
                $this->writeTrailingComments($context, $comments->get(CommentPosition::AfterAll->value));
            }
            else
            {
                $context->write($this->dumpLiteral($object));
            }
        }

        /**
         * Writes the current value.
         *
         * @param DumperContext $context The context of the dumper.
         */
        protected function writeValue(DumperContext $context): void
        {
            $object = $context->getCurrentObject();

            if ($object instanceof JSONCArray || is_array($object))
            {
                $this->writeArray($context, $object);
            }
            else if ($object instanceof JSONCObject || is_object($object))
            {
                $this->writeObject($context);
            }
            else
            {
                $this->writeLiteral($context);
            }
        }

        /**
         * Writes the specified literal.
         *
         * @param DumperContext $context The context of the dumper.
         */
        protected function writeLiteral(DumperContext $context): void
        {
            $object = $context->getCurrentObject();

            if ($object instanceof JSONCValue)
            {
                $object = $object->getValue();
            }

            $context->write($this->dumpLiteral($object));
        }

        /**
         * Writes the current object.
         *
         * @param DumperContext $context The context of the dumper.
         */
        protected function writeObject(DumperContext $context): void
        {
            /**
             * @var JSONCObject $object
             */
            $object;
            $currentObject = $context->getCurrentObject();

            if ($currentObject instanceof JSONCObject)
            {
                $object = $currentObject;
            }
            else
            {
                $object = new JSONCObject();
                $object->getProperties()->merge($object);
            }

            $comments = $object->getComments();
            $accessorComments = collect($object->getAccessorComments());
            $this->writeComments($context, $comments->get(CommentPosition::BeforeValue->value));
            $context->indentIfNewline();
            $context->write("{");
            $context->incrementIndentationLevel();
            $this->writeComments($context, $comments->get(CommentPosition::BeforeContent->value));

            if ($object->getProperties()->isNotEmpty())
            {
                $context->ensureNewLine();
                $lastKey = $object->getProperties()->keys()->last();

                $processProperty = function ($propertyName, $last) use ($context, $accessorComments)
                {
                    /**
                     * @var Collection<CommentPosition,Collection<int,Comment>> $propertyComments
                     */
                    $propertyComments = $accessorComments->get($propertyName, new Collection());
                    $accessorComments->forget($propertyName);
                    $this->writeProperty($context, $propertyName, $propertyComments, $last);
                };

                foreach ($object->getProperties()->slice(0, -1) as $propertyName => $_)
                {
                    $processProperty($propertyName, false);
                    $context->ensureNewLine();
                }

                $processProperty($lastKey, true);
                $context->ensureNewLine();
                $context->decrementIndentationLevel();
                $context->writeIndent();
            }
            else
            {
                $context->decrementIndentationLevel();
            }

            $context->write("}");
        }

        /**
         * Writes the current array to the output.
         *
         * @param DumperContext $context The context of the dumper.
         */
        protected function writeArray(DumperContext $context): void
        {
            /**
             * @var JSONCArray $object
             */
            $object;
            $currentObject = $context->getCurrentObject();

            if ($currentObject instanceof JSONCArray)
            {
                $object = $currentObject;
            }
            else
            {
                $object = new JSONCArray();
                $object->getProperties()->merge($object);
            }

            $comments = $object->getComments();
            $accessorComments = collect($object->getAccessorComments());
            $this->writeComments($context, $comments->get(CommentPosition::BeforeValue->value));
            $context->indentIfNewline();
            $context->write("[");
            $context->incrementIndentationLevel();
            $this->writeComments($context, $comments->get(CommentPosition::BeforeContent->value));

            if ($object->getProperties()->isNotEmpty())
            {
                $context->ensureNewLine();
                $lastKey = $object->getProperties()->keys()->last();

                $processProperty = function ($propertyName, $last) use ($context, $accessorComments)
                {
                    /**
                     * @var Collection<CommentPosition,Collection<int,Comment>> $propertyComments
                     */
                    $propertyComments = $accessorComments->get($propertyName, new Collection());
                    $accessorComments->forget($propertyName);
                    $this->writeArrayEntry($context, $propertyName, $propertyComments, $last);
                };

                foreach ($object->getProperties()->slice(0, -1) as $propertyName => $_)
                {
                    $processProperty($propertyName, false);
                    $context->ensureNewLine();
                }

                $processProperty($lastKey, true);
                $context->ensureNewLine();
                $context->decrementIndentationLevel();
                $context->writeIndent();
            }
            else
            {
                $context->decrementIndentationLevel();
            }

            $context->write("]");
        }

        /**
         * Writes the specified property.
         *
         * @param DumperContext $context The context of the dumper.
         * @param string $propertyName The name of the property to write.
         * @param Collection<CommentPosition,Collection<int,Comment>> $propertyComments The comments of the property.
         * @param bool $last A value indicating whether the property is the last one.
         */
        protected function writeProperty(DumperContext $context, string $propertyName, Collection $propertyComments, $last = false): void
        {
            $context->pushProperty($propertyName);
            $this->writeComments($context, $propertyComments->get(CommentPosition::BeforeEntry->value));
            $context->writeIndent();
            $context->write(json_encode($propertyName));
            $context->incrementIndentationLevel();
            {
                $this->writeComments($context, $propertyComments->get(CommentPosition::AfterAccessor->value), true);
            }
            $context->decrementIndentationLevel();
            $context->indentIfNewline();
            $context->write(":");
            $this->writeComments($context, $propertyComments->get(CommentPosition::BeforeValue->value), true);

            if ($context->getLinePosition() === 0)
            {
                $context->writeIndent();
            }
            else
            {
                $context->write(" ");
            }

            $this->writeValue($context);
            $context->incrementIndentationLevel();
            {
                $this->writeComments($context, $propertyComments->get(CommentPosition::AfterValue->value), true);
            }
            $context->decrementIndentationLevel();

            if (!$last)
            {
                $context->indentIfNewline();
                $context->write(",");
            }

            $context->incrementIndentationLevel();
            {
                $this->writeComments($context, $propertyComments->get(CommentPosition::AfterEntry->value), true);
            }
            $context->decrementIndentationLevel();
            $context->popProperty();
        }

        /**
         * Writes the specified array entry.
         *
         * @param DumperContext $context The context of the dumper.
         * @param int $index The index of the entry to write.
         * @param Collection<CommentPosition,Collection<int,Comment>> $entryComments The comments of the entry.
         * @param bool $last A value indicating whether the entry is the last one.
         */
        protected function writeArrayEntry(DumperContext $context, int $index, Collection $entryComments, $last = false): void
        {
            $context->pushProperty($index);
            {
                $this->writeComments($context, $entryComments->get(CommentPosition::BeforeValue->value));
                $context->indentIfNewline();
                $this->writeValue($context);
                $this->writeComments($context, $entryComments->get(CommentPosition::AfterValue->value), true);

                if (!$last)
                {
                    $context->indentIfNewline();
                    $context->write(",");
                }

                $this->writeComments($context, $entryComments->get(CommentPosition::AfterEntry->value), true);
            }
            $context->popProperty();
        }

        /**
         * Writes a block of the specified {@see comments} at the current indentation level.
         *
         * @param DumperContext $context The context of the dumper.
         * @param Collection<int,Comment> $comments The comments to write.
         * @param bool $inline A value indicating whether the current context is inline.
         */
        protected function writeComments(DumperContext $context, $comments, bool $inline = false): void
        {
            if ($comments !== null)
            {
                if ($inline)
                {
                    if (
                        $comments->every(function (Comment $comment)
                        {
                            return ($comment->getType() === CommentType::Block) &&
                                (count($this->getLines($comment->getContent())) === 1);
                        })
                    )
                    {
                        foreach ($comments as $comment)
                        {
                            $context->write(' ');
                            $context->write($this->dumpComment($comment));
                        }

                        if (
                            $comments->count() > 0 &&
                            $comments->last()->getType() === CommentType::Inline
                        )
                        {
                            $context->writeLine();
                        }
                    }
                    else
                    {
                        $this->writeTrailingComments($context, $comments);
                    }
                }
                else
                {
                    foreach ($comments as $comment)
                    {
                        $context->ensureNewLine();
                        $context->writeLine($this->indent($context, $this->dumpComment($comment)));
                    }
                }
            }
        }

        /**
         * Writes the specified {@see $comments} starting at the end of the current line.
         *
         * @param DumperContext $context The context of the dumper.
         * @param Collection<int,Comment> $comments
         */
        protected function writeTrailingComments(DumperContext $context, $comments): void
        {
            if ($comments !== null)
            {
                $inlineComments = $comments->takeWhile(function (Comment $comment)
                {
                    return $comment->getType() === CommentType::Inline;
                });

                $otherComments = $comments->slice($inlineComments->count());

                if ($inlineComments->count() > 0)
                {
                    $context->write(' ');
                    $indentationString = $context->getIndentationString($context->getLinePosition());

                    $context->writeLine(
                        $inlineComments->map(function (Comment $comment)
                        {
                            return $this->dumpComment($comment);
                        })->join("\n" . $indentationString)
                    );
                }

                if ($otherComments->count() > 0)
                {
                    $this->writeComments($context, $otherComments);
                }

                $context->ensureNewLine();
            }
        }

        /**
         * Indents the specified text.
         *
         * @param DumperContext $context The context of the dumper.
         * @param string $text The text to indent.
         * @param int $count The number of spaces to indent.
         * @return string The indented text.
         */
        protected function indent(DumperContext $context, string $text, int $count = null): string
        {
            $indentationString = $count ? $context->getIndentationString($count) : $context->getIndentationString();
            return $indentationString . collect($this->getLines($text))->join("\n" . $indentationString);
        }

        /**
         * Gets the lines from the specified {@see $text}.
         *
         * @param string $text The text to get the lines from.
         * @return string[] The lines from the specified {@see $text}.
         */
        protected function getLines(string $text): array
        {
            return explode("\n", $text);
        }

        /**
         * Joins the lines in the specified {@see $text} with the specified {@see $separator}.
         *
         * @param string $text The text containing the lines to join.
         * @param string $separator The separator to use.
         * @return string The joined lines.
         */
        protected function joinLines(string $text, string $separator): string
        {
            return collect($this->getLines($text))->join($separator);
        }
    }
}
