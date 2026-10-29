<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\Database\Schema\Grammars;

use DomainSystem\SystemApps\Database\Schema\Blueprint;

interface GrammarInterface
{
    /**
     * Translates a Blueprint into a CREATE TABLE SQL statement.
     */
    public function compileCreate(Blueprint $blueprint): string;
}
