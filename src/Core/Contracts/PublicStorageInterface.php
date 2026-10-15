<?php

declare(strict_types=1);

namespace DomainSystem\Core\Contracts;

/**
 * Contrato oficial do Kernel para armazenamento e resolução de arquivos públicos (mídia/uploads).
 *
 * Isola completamente o sistema de arquivos e a topologia de URLs da aplicação,
 * garantindo que plugins e serviços funcionem de forma idêntica e agnóstica em
 * qualquer ambiente (XAMPP local, Hostinger, HostGator, Docker, VPS ou Nuvem).
 */
interface PublicStorageInterface
{
    /**
     * Retorna o caminho físico absoluto no sistema de arquivos para o diretório ou arquivo de upload público.
     *
     * @param string $subpath Subdiretório ou nome do arquivo relativo à pasta pública de uploads (ex: 'academy' ou 'academy/foto.png')
     * @return string Caminho físico absoluto no disco
     */
    public function getUploadPath(string $subpath = ''): string;

    /**
     * Converte um caminho relativo público (ex: 'uploads/academy/foto.png')
     * na URL acessível pelo navegador no ambiente atual.
     *
     * @param string $relativePath Caminho relativo gravado no banco ou URL externa
     * @return string URL pública formatada (ou a URL externa inalterada)
     */
    public function getPublicUrl(string $relativePath): string;

    /**
     * Grava conteúdo diretamente em um arquivo no armazenamento público.
     *
     * @param string $relativePath Caminho relativo sob a pasta pública de uploads (ex: 'academy/arquivo.txt')
     * @param string $contents Conteúdo a ser gravado
     * @return string Caminho relativo padronizado gravado (ex: 'uploads/academy/arquivo.txt')
     */
    public function put(string $relativePath, string $contents): string;

    /**
     * Exclui um arquivo do armazenamento público com segurança contra directory traversal.
     *
     * @param string $relativePath Caminho relativo do arquivo a ser excluído
     * @return bool True se o arquivo foi excluído com sucesso, false caso contrário
     */
    public function delete(string $relativePath): bool;

    /**
     * Verifica se um arquivo existe no armazenamento público.
     *
     * @param string $relativePath Caminho relativo do arquivo
     * @return bool
     */
    public function exists(string $relativePath): bool;
}
