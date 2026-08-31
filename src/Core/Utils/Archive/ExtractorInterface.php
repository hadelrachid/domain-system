<?php

namespace DomainSystem\Core\Utils\Archive;

interface ExtractorInterface
{
    /**
     * Extrai um pacote ZIP e retorna o nome da pasta do componente.
     * Deve lançar uma Exception caso seja um pacote inválido (sem o descritor).
     */
    public function extract(string $archivePath, string $destinationPath, string $descriptorFile = 'plugin.json'): string;
}
