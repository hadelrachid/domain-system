<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class ApplyResult
{
    /**
     * @param list<string> $written
     * @param list<string> $removed
     * @param list<string> $skippedProtected
     */
    public function __construct(
        public BackupRecord $backup,
        public array $written,
        public array $removed,
        public array $skippedProtected,
    ) {
        foreach ([$this->written, $this->removed, $this->skippedProtected] as $list) {
            foreach ($list as $path) {
                if (!is_string($path) || trim($path) === '') {
                    throw new InvalidArgumentException('Caminhos em ApplyResult devem ser strings não vazias.');
                }
            }
        }
    }
}
