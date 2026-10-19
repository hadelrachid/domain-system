<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class DumpResult
{
    public function __construct(
        public string $dumpPath,
        public int $sizeBytes,
        public int $tablesCount,
    ) {
        if (trim($this->dumpPath) === '') {
            throw new InvalidArgumentException('O caminho do dump não pode ser vazio.');
        }
        if ($this->sizeBytes < 0) {
            throw new InvalidArgumentException('O tamanho do dump em bytes não pode ser negativo.');
        }
        if ($this->tablesCount < 0) {
            throw new InvalidArgumentException('O número de tabelas no dump não pode ser negativo.');
        }
    }
}
