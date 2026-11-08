<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

interface CoursePackageServiceInterface
{
    /**
     * Exporta o curso completo (manifesto course.json + arquivos .php das aulas) para um arquivo ZIP temporário.
     * Retorna o caminho absoluto do arquivo .zip gerado no sistema.
     *
     * @param int $courseId
     * @return string Caminho absoluto do arquivo .zip gerado
     * @throws \InvalidArgumentException Se o curso não for encontrado
     * @throws \RuntimeException Se falhar ao criar o pacote ZIP
     */
    public function exportCourseZip(int $courseId): string;

    /**
     * Importa um arquivo ZIP contendo um curso, descompacta com segurança os arquivos .php
     * no diretório do tema ativo e cadastra o curso com sua estrutura completa de módulos e aulas.
     *
     * @param string $zipFilePath Caminho do arquivo .zip a ser importado
     * @param string|null $overrideThemePath Caminho alternativo de tema (útil para testes unitários)
     * @return array Informações do curso importado (id, title, slug, modules_count, lessons_count)
     * @throws \InvalidArgumentException Se o ZIP for inválido ou corrompido
     * @throws \RuntimeException Se houver falha de extração ou segurança
     */
    public function importCourseZip(string $zipFilePath, ?string $overrideThemePath = null): array;
}
