<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface CourseImageStorageInterface
{
    /**
     * Valida e armazena a imagem do curso no padrão 16:9.
     * 
     * @param array<string, mixed> $fileInfo Informações do arquivo ($_FILES['thumbnail'])
     * @return string Caminho relativo salvo (ex: 'uploads/academy/course_xyz.webp')
     * @throws \InvalidArgumentException Se arquivo for inválido, ultrapassar limite ou violar padrão 16:9
     * @throws \RuntimeException Se falhar ao mover/gravar o arquivo
     */
    public function storeUpload(array $fileInfo): string;

    /**
     * Remove o arquivo físico correspondente.
     * 
     * @param string $relativePath
     */
    public function deleteFile(string $relativePath): void;
}
