<?php
namespace DomainSystem\Plugins\SystemAdmin\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Application;
use DomainSystem\Core\Registry\DashboardWidgetRegistry;
use Exception;

class DashboardController
{
    private ThemeManager $theme;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
    }

    public function index(\DomainSystem\Core\Http\Request $request)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return \DomainSystem\Core\Http\Response::redirect(BASE_URL . '/login');
        }

        try {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            
            // 1. Carrega as preferências do usuário
            $stmt = $db->prepare("SELECT dashboard_layout FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $layoutStr = $stmt->fetchColumn();
            
            $userWidgets = [];
            if ($layoutStr) {
                $userWidgets = json_decode($layoutStr, true) ?: [];
            }
            
            // 2. Carrega todos os widgets disponíveis do Registry
            $registry = Application::getInstance()->getContainer()->make(DashboardWidgetRegistry::class);
            $providers = $registry->getProviders();
            
            // Se o usuário não tem layout salvo, carregamos widgets padrão se houver
            if (empty($userWidgets) && !empty($providers)) {
                $userWidgets = [];
                // Auto-adiciona os dois primeiros widgets que encontrar como padrão
                foreach ($providers as $class => $prov) {
                    $av = $prov->getAvailableWidgets();
                    foreach ($av as $id => $meta) {
                        $userWidgets[] = ['provider' => $class, 'id' => $id];
                        if (count($userWidgets) >= 2) break 2;
                    }
                }
            }

            // 3. Renderiza os widgets ativos do usuário
            $renderedWidgets = [];
            foreach ($userWidgets as $uw) {
                $prov = $registry->getProvider($uw['provider']);
                if ($prov) {
                    $renderedWidgets[] = $prov->renderWidget($uw['id']);
                }
            }
            
            // 4. Monta o catálogo para o Combobox (Add Widget)
            $catalog = [];
            foreach ($providers as $class => $prov) {
                $catalog[] = [
                    'provider_class' => $class,
                    'provider_name' => $prov->getProviderName(),
                    'widgets' => $prov->getAvailableWidgets()
                ];
            }

            return $this->theme->render('dashboard_modular', [
                'theme' => $this->theme,
                'renderedWidgets' => $renderedWidgets,
                'catalog' => $catalog,
                'userWidgets' => $userWidgets
            ]);
            
        } catch (Exception $e) {
            return "Erro ao renderizar dashboard modular: " . $e->getMessage();
        }
    }

    public function saveLayout(\DomainSystem\Core\Http\Request $request)
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) return \DomainSystem\Core\Http\Response::json(['error' => 'Not logged in'], 401);

        $widgets = $request->input('widgets') ?? [];
        $layoutArray = [];
        if (is_array($widgets)) {
            foreach ($widgets as $w) {
                $decoded = json_decode(html_entity_decode($w), true);
                if ($decoded) {
                    $layoutArray[] = $decoded;
                }
            }
        }
        
        $layout = json_encode($layoutArray);

        try {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\Plugins\Database\Connection::class)->getPdo();
            $stmt = $db->prepare("UPDATE users SET dashboard_layout = ? WHERE id = ?");
            $stmt->execute([$layout, $userId]);
            
            return \DomainSystem\Core\Http\Response::redirect(BASE_URL . '/admin');
        } catch (\Exception $e) {
            return \DomainSystem\Core\Http\Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
