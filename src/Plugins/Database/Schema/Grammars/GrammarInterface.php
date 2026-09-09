<?php

namespace DomainSystem\Plugins\Database\Schema\Grammars;

use DomainSystem\Plugins\Database\Schema\Blueprint;

interface GrammarInterface
{
    /**
     * Translates a Blueprint into a CREATE TABLE SQL statement.
     */
    public function compileCreate(Blueprint $blueprint): string;
}
