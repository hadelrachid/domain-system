<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class SchemaVerification
{
    /**
     * @param list<string> $missingTables
     * @param list<string> $missingColumns
     * @param list<string> $unrecorded
     */
    public function __construct(
        public bool $ok,
        public array $missingTables = [],
        public array $missingColumns = [],
        public array $unrecorded = [],
    ) {
        foreach ([$this->missingTables, $this->missingColumns, $this->unrecorded] as $list) {
            foreach ($list as $entry) {
                if (!is_string($entry) || trim($entry) === '') {
                    throw new InvalidArgumentException('Itens de SchemaVerification devem ser strings não vazias.');
                }
            }
        }
    }
}
