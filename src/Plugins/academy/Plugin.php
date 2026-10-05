<?php

namespace DomainSystem\Plugins\academy;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Theme\ShortcodeManager;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db');
        $os->listenHook('admin.menu');
        $os->listenHook('router.register');
        $os->listenHook('shortcodes.register');
    }

    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Menu Administrativo
        $runtime->onHook('admin.menu', function($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Academy (Cursos)',
                    'url' => '/admin/academy',
                    'icon' => '🎓'
                ];
            }
            return $menus;
        });

        // 2. Rotas do Painel
        $runtime->onHook('router.register', function(Router $router) {
            $router->addRoute('GET', '/admin/academy', [\DomainSystem\Plugins\academy\Controllers\CourseAdminController::class, 'index'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/store', [\DomainSystem\Plugins\academy\Controllers\CourseAdminController::class, 'store'], 'academy', ['admin']);
            $router->addRoute('POST', '/admin/academy/delete/{id}', [\DomainSystem\Plugins\academy\Controllers\CourseAdminController::class, 'delete'], 'academy', ['admin']);
        });

        // 3. Shortcode: [hub_de_cursos]
        $runtime->onHook('shortcodes.register', function(\DomainSystem\Core\Theme\ShortcodeManager $manager) {
            $manager->add('hub_de_cursos', function($attrs) {
                return $this->renderHub();
            });
        });
    }

    public function activate(\DomainSystem\Core\Contracts\OsRuntimeInterface $runtime): void
    {
        // Criação da tabela de cursos
        $schema = $runtime->make(\DomainSystem\SystemApps\Database\Schema\SchemaBuilder::class);
        $schema->create('academy_courses', function ($table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('thumbnail')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // published, draft, soon
            $table->string('link')->nullable(); // Link para comprar ou assistir
            $table->datetime('created_at')->nullable()->default('CURRENT_TIMESTAMP');
        });
    }

    private function renderHub(): string
    {
        try {
            $db = $runtime->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
            $stmt = $db->query("SELECT * FROM academy_courses WHERE status IN ('published', 'soon') ORDER BY id DESC");
            $courses = $stmt->fetchAll();
            
            if (empty($courses)) {
                return "<div style='text-align:center; padding:40px; background:var(--surface); border-radius:12px; border:1px dashed var(--primary);'>Nenhum curso disponível no momento.</div>";
            }

            // A grade HTML
            $html = "<div class='academy-grid' style='display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-top: 30px;'>";
            
            foreach ($courses as $course) {
                $statusBadge = '';
                if ($course['status'] === 'soon') {
                    $statusBadge = "<span style='position:absolute; top:15px; right:15px; background:var(--primary); color:#0b0c10; font-size:11px; font-weight:bold; padding:4px 10px; border-radius:20px; box-shadow:0 0 10px rgba(69, 243, 255, 0.5);'>EM BREVE</span>";
                }

                $thumbUrl = htmlspecialchars($course['thumbnail'] ?: (defined('BASE_URL') ? BASE_URL : '') . '/assets/img/default-course.jpg', ENT_QUOTES, 'UTF-8');
                $link = htmlspecialchars($course['link'] ?: '#', ENT_QUOTES, 'UTF-8');
                
                $html .= "
                <a href='{$link}' style='text-decoration:none; display:block;'>
                    <div class='glass-panel course-card' style='position:relative; overflow:hidden; transition: transform 0.3s ease, box-shadow 0.3s ease;'>
                        {$statusBadge}
                        <div style='height: 180px; background-image: url({$thumbUrl}); background-size: cover; background-position: center; border-bottom: 1px solid rgba(69, 243, 255, 0.1);'></div>
                        <div style='padding: 25px;'>
                            <h3 style='color: #fff; margin-bottom: 10px; font-size: 1.2rem;'>".htmlspecialchars($course['title'] ?? '')."</h3>
                            <p style='color: var(--text-muted); font-size: 0.95rem; line-height: 1.5; margin:0;'>".htmlspecialchars($course['description'] ?? '')."</p>
                        </div>
                    </div>
                </a>";
            }

            $html .= "</div>";
            
            // Hover effect injected locally for the shortcode
            $html .= "
            <style>
                .course-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 15px 30px rgba(0,0,0,0.5), 0 0 15px rgba(69, 243, 255, 0.2);
                }
            </style>";

            return $html;
        } catch (\Exception $e) {
            // Se a tabela não existir ou o BD falhar, não expomos erro SQL pro usuário
            return "<div style='text-align:center; padding:40px; background:var(--surface); border-radius:12px; border:1px dashed rgba(255,255,255,0.1); color: var(--text-muted);'>Hub de Cursos em configuração. Volte em breve.</div>";
        }
    }
}
