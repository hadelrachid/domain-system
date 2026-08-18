<?php

namespace DomainSystem\Core\Utils\Archive;

interface ExtractorInterface
{
    /**
     * Extrai um pacote ZIP e retorna o nome da pasta do plugin.
     * Deve lançar uma Exception caso seja um pacote inválido (sem plugin.json).
     */
    public function extract(string $archivePath, string $destinationPath): string;
}
