<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Contracts;

/**
 * Contrato para fabricação automática de pastas (módulos) e arquivos PHP pré-configurados
 * de aulas diretamente no tema ativo, integrados ao Editor de Blocos e às resoluções Mobile/Desktop.
 */
interface LessonFileScaffolderInterface
{
    /**
     * Normaliza um título (ex: "Estrutura de Dados") para slug de arquivo ("estrutura-de-dados").
     */
    public function slugify(string $title): string;

    /**
     * Cria fisicamente uma pasta/módulo dentro de `pages/` do tema ativo.
     *
     * @param string $folderTitle Nome ou título da pasta/módulo
     * @return array{slug: string, relative_path: string, full_path: string}
     */
    public function createFolder(string $folderTitle): array;

    /**
     * Fabrica automaticamente um arquivo `.php` pré-configurado (com CSS e JS padrão Mobile/Desktop
     * e suporte ao Editor de Blocos) a partir do Título da aula/página.
     *
     * Ex: Título "Estrutura de Dados" -> arquivo `estrutura-de-dados.php`
     *     Slug relativo: `pages/estrutura-de-dados.php` (ou `pages/{folder}/estrutura-de-dados.php`)
     *
     * @param string      $title         Título da aula ou página (ex: "Estrutura de Dados")
     * @param string|null $folder        Subpasta opcional dentro de pages/ (ex: "curso-de-python" ou "")
     * @param string|null $blocksJson    Conteúdo opcional em blocos JSON para pré-popular o arquivo
     * @param string      $mobileProfile Perfil mobile padrão ('drawer', 'bottom_bar', 'cinema')
     * @return array{
     *   title: string,
     *   slug: string,
     *   file_name: string,
     *   relative_path: string,
     *   full_path: string,
     *   public_url: string
     * }
     */
    public function fabricateLessonFile(
        string $title,
        ?string $folder = null,
        ?string $blocksJson = null,
        string $mobileProfile = 'drawer'
    ): array;
}
