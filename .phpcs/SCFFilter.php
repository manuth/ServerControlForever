<?php

use PHP_CodeSniffer\Filters\Filter;

/**
 * Provides the functionality to enable `phpcs` for the `scf` file.
 */
class SCFFilter extends Filter
{
    /**
     * @inheritDoc
     */
    public function accept()
    {

        return (basename($this->current()) === "scf") || parent::accept();
    }
}
