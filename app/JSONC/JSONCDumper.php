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
         * @param bool $includeComments A value indicating whether to include comments.
         * @param int $flags A set of flags for controlling the behavior of the dumper.
         * @return string The dumped JSONC code.
         */
        public function dump(mixed $object, ?int $width = null, ?bool $includeComments = null, ?int $flags = null): string
        {
            $context = new DumperContext($object, $width, $includeComments, $flags);
            $this->writeRoot($context);
            return $context->getContent();
        }

        /**
         * Dumps the specified literal.
         *
         * @param DumperContext $context The context of the dumper.
         * @param mixed $value The value to dump.
         */
        protected function dumpLiteral(DumperContext $context, mixed $value): string
        {
            return $context->dumpJSON($value);
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
            return collect(["//", $comment->getContent()])->join(" ");
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
            $parts = collect(["/*"]);

            if (!empty($comment->getContent()))
            {
                $parts->push($this->joinLines($comment->getContent(), "\n   "));
            }

            $parts->push("*/");
            return $parts->join(" ");
        }

        /**
         * Dumps the specified doc comment.
         *
         * @param Comment $comment The comment to dump.
         */
        protected function dumpDocComment(Comment $comment): string
        {
            $terminator = "\n *";
            $content = "/**" . $terminator;

            if (!empty($comment->getContent()))
            {
                $content .= " " . $this->joinLines($comment->getContent(), $terminator . " ");
            }

            $content .= "\n */";
            return $content;
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
                $this->writeComments($context, $comments->get(CommentPosition::AfterAll->value));
            }
            else
            {
                $this->writeLiteral($context, $object);
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
                $this->writeContainer($context);
            }
            else if ($object instanceof JSONCObject || is_object($object))
            {
                $this->writeContainer($context);
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

            $context->write($this->dumpLiteral($context, $object));
        }

        protected function writeContainer(DumperContext $context): void
        {
            /**
             * @var JSONCObjectBase $container
             */
            $container;
            /**
             * @var bool $isObject
             */
            $isObject;
            $currentObject = $context->getCurrentObject();

            if ($currentObject instanceof JSONCObjectBase)
            {
                $isObject = $currentObject instanceof JSONCObject;
                $container = $currentObject;
            }
            else
            {
                $isObject = !is_array($currentObject);

                if ($isObject)
                {
                    $container = new JSONCObject();
                }
                else
                {
                    $container = new JSONCArray();
                }

                $container->getProperties()->merge($currentObject);
            }

            $comments = $container->getComments();
            $accessorComments = collect($container->getAccessorComments());

            /**
             * @var callable(string|int,Collection<CommentPosition,Collection<int,Comment>>,bool) : void $writeAccessor
             */
            $writeAccessor;

            if ($isObject)
            {
                $writeAccessor = function (string $accessor, Collection $comments, bool $last) use ($context)
                {
                    $this->writeProperty($context, $accessor, $comments, $last);
                };
            }
            else
            {
                $writeAccessor = function (int $accessor, Collection $comments, bool $last) use ($context)
                {
                    $this->writeArrayEntry($context, $accessor, $comments, $last);
                };
            }

            $this->writeComments($context, $comments->get(CommentPosition::BeforeValue->value));
            $context->indentIfNewline();
            $context->write($isObject ? "{" : "[");
            $context->incrementIndentationLevel();
            $this->writeTrailingComments($context, $comments->get(CommentPosition::BeforeContent->value));

            if ($container->getProperties()->isNotEmpty() || $accessorComments->some(
                function (Collection $comments)
                {
                    return $comments->isNotEmpty();
                }
            ))
            {
                $processAccessor = function (string | int $accessor, bool $last) use ($accessorComments, $writeAccessor)
                {
                    /**
                     * @var Collection<CommentPosition,Collection<int,Comment>> $comments
                     */
                    $comments = $accessorComments->get($accessor);
                    $accessorComments->forget($accessor);
                    $writeAccessor($accessor, $comments, $last);
                };

                $context->ensureNewLine();
                $lastKey = $container->getProperties()->keys()->last();

                foreach ($container->getProperties()->slice(0, -1) as $accessor => $_)
                {
                    $processAccessor($accessor, false);
                    $context->ensureNewLine();
                }

                $processAccessor($lastKey, true);
            }

            $this->writeOrphanedComments($context, $accessorComments);
            $this->writeComments($context, $comments->get(CommentPosition::AfterContent->value));
            $context->decrementIndentationLevel();
            $context->indentIfNewline();
            $context->write($isObject ? "}" : "]");
            $this->writeTrailingComments($context, $comments->get(CommentPosition::AfterValue->value));
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
            $context->indentIfNewline();
            $context->write($context->dumpJSON($propertyName));
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
            if ($context->getIncludeComments() && ($comments !== null))
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
            if ($context->getIncludeComments() && ($comments !== null))
            {
                if ($comments->count() > 0)
                {
                    $context->write(' ');
                    $indentationString = $context->getIndentationString($context->getLinePosition());

                    $context->writeLine(
                        $this->joinLines(
                            $comments->map(function (Comment $comment)
                            {
                                return $this->dumpComment($comment);
                            })->join("\n"),
                            "\n" . $indentationString
                        )
                    );
                }
            }
        }

        /**
         * Writes orphaned comments to the output.
         *
         * @param DumperContext $context The context of the dumper.
         * @param Collection<string,Collection<string,Collection<int,Comment>>> $comments The orphaned comments to write.
         */
        protected function writeOrphanedComments(DumperContext $context, Collection $comments): void
        {
            foreach ($comments as $commentCollection)
            {
                foreach ([
                    CommentPosition::BeforeEntry,
                    CommentPosition::AfterAccessor,
                    CommentPosition::BeforeValue,
                    CommentPosition::AfterValue,
                    CommentPosition::AfterEntry
                ] as $position)
                {
                    $this->writeComments($context, $commentCollection->get($position->value));
                }
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
