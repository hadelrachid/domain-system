<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\pages\Services;

use PDO;

class DefaultPagesSeeder
{
    /**
     * @return array<int, array{slug: string, title: string, content: string, theme: string, template_file: string}>
     */
    public static function getDefaultPages(): array
    {
        return [
            [
                'slug' => 'inicio',
                'title' => 'Início',
                'content' => '<h1>Bem-vindo ao Domain-System</h1><p>Página inicial do site oficial.</p>',
                'theme' => 'default',
                'template_file' => 'templates/home.php',
            ],
            [
                'slug' => 'blog',
                'title' => 'Blog',
                'content' => '<h1>Blog & Artigos</h1><p>Últimas novidades, arquitetura de software e tecnologia.</p>',
                'theme' => 'default',
                'template_file' => 'templates/blog.php',
            ],
            [
                'slug' => 'sobre',
                'title' => 'Sobre',
                'content' => '<h1>Sobre Mim</h1><p>Sou o criador do Domain-System OS, uma infraestrutura web modular pronta para o futuro.</p>',
                'theme' => 'default',
                'template_file' => 'templates/sobre.php',
            ],
            [
                'slug' => 'privacidade',
                'title' => 'Privacidade',
                'content' => '<h1>Política de Privacidade</h1><p>Respeitamos sua privacidade e a segurança de seus dados pessoais.</p>',
                'theme' => 'default',
                'template_file' => 'templates/privacidade.php',
            ],
            [
                'slug' => 'termos',
                'title' => 'Termos e Usos',
                'content' => '<h1>Termos e Condições de Uso</h1><p>Ao navegar em nosso ecossistema você concorda com nossos termos de uso.</p>',
                'theme' => 'default',
                'template_file' => 'templates/termos.php',
            ],
            [
                'slug' => 'tutoriais',
                'title' => 'Tutoriais',
                'content' => '<h1>Central de Conhecimento e Cursos</h1><p>[hub_de_cursos]</p>',
                'theme' => 'default',
                'template_file' => 'templates/academy.php',
            ],
        ];
    }

    /**
     * Semeia as páginas padrão garantindo idempotência e compatibilidade cruzada (SQLite / MySQL).
     */
    public static function seed(PDO $pdo): int
    {
        $pages = self::getDefaultPages();
        $insertedCount = 0;

        $checkStmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
        $insertStmt = $pdo->prepare("INSERT INTO pages (slug, title, content, theme, template_file) VALUES (?, ?, ?, ?, ?)");

        foreach ($pages as $p) {
            $checkStmt->execute([$p['slug']]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $insertStmt->execute([
                    $p['slug'],
                    $p['title'],
                    $p['content'],
                    $p['theme'],
                    $p['template_file']
                ]);
                $insertedCount++;
            }
        }

        return $insertedCount;
    }
}
