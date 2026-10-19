<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class VerifiedPackage
{
    public function __construct(
        public ReleaseInfo $release,
        public UpdateManifest $manifest,
        public string $extractedPath,
    ) {
        if (trim($this->extractedPath) === '') {
            throw new InvalidArgumentException('O caminho de extração (quarentena) de VerifiedPackage não pode ser vazio.');
        }
    }
}
