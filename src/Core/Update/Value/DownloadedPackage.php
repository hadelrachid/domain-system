<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class DownloadedPackage
{
    public function __construct(
        public ReleaseInfo $release,
        public string $archivePath,
        public int $sizeBytes,
    ) {
        if (trim($this->archivePath) === '') {
            throw new InvalidArgumentException('O caminho do arquivo baixado não pode ser vazio.');
        }
        if ($this->sizeBytes <= 0) {
            throw new InvalidArgumentException('O tamanho do pacote baixado deve ser maior que zero.');
        }
    }
}
