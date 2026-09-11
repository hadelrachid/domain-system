<?php

namespace DomainSystem\Plugins\visual_themes;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Http\Request;

class Plugin extends AbstractPlugin
{
    private array $colors = [
        'default' => ['primary' => '#10b981', 'hover' => '#059669', 'light' => '#d1fae5', 'border' => '#6ee7b7', 'bg' => '#f0f2f5', 'card' => '#ffffff', 'text' => '#1d2327', 'muted' => '#64748b'],
        'blue'    => ['primary' => '#2271b1', 'hover' => '#135e96', 'light' => '#e0f2fe', 'border' => '#7dd3fc', 'bg' => '#f0f2f5', 'card' => '#ffffff', 'text' => '#1d2327', 'muted' => '#64748b'],
        'pink'    => ['primary' => '#ec4899', 'hover' => '#be185d', 'light' => '#fce7f3', 'border' => '#f9a8d4', 'bg' => '#fdf2f8', 'card' => '#ffffff', 'text' => '#831843', 'muted' => '#db2777'],
        'purple'  => ['primary' => '#8b5cf6', 'hover' => '#6d28d9', 'light' => '#ede9fe', 'border' => '#c4b5fd', 'bg' => '#f5f3ff', 'card' => '#ffffff', 'text' => '#2e1065', 'muted' => '#7c3aed'],
        'orange'  => ['primary' => '#f97316', 'hover' => '#c2410c', 'light' => '#ffedd5', 'border' => '#fdba74', 'bg' => '#fff7ed', 'card' => '#ffffff', 'text' => '#7c2d12', 'muted' => '#ea580c'],
        'dark'    => ['primary' => '#3b82f6', 'hover' => '#2563eb', 'light' => '#2d2d30', 'border' => '#3f3f46', 'bg' => '#1e1e1e', 'card' => '#252526', 'text' => '#e4e4e7', 'muted' => '#a1a1aa'],
    ];

    public function register(): void
    {
        /** @var EventDispatcher $events */
        $events = $this->events();

        // 1. Salvar preferências de tema
        $events->addListener('cockpit.profile.save', function(string $userId, Request $request) {
            $themeColor = $request->input('theme_color');
            if ($themeColor && array_key_exists($themeColor, $this->colors)) {
                $prefsDir = dirname(__DIR__, 3) . '/public/uploads/prefs';
                if (!is_dir($prefsDir)) {
                    mkdir($prefsDir, 0777, true);
                }
                $prefsFile = $prefsDir . '/user_' . $userId . '.json';
                $prefs = file_exists($prefsFile) ? json_decode(file_get_contents($prefsFile), true) : [];
                $prefs['theme_color'] = $themeColor;
                file_put_contents($prefsFile, json_encode($prefs));
            }
        });

        // 2. Injetar variáveis CSS no <head>
        $events->addListener('cockpit.head.css', function(string $css, ?string $userId, string $cockpitType) {
            $themeColor = 'default';
            if ($userId) {
                $prefsFile = dirname(__DIR__, 3) . '/public/uploads/prefs/user_' . $userId . '.json';
                if (file_exists($prefsFile)) {
                    $prefs = json_decode(file_get_contents($prefsFile), true);
                    $themeColor = $prefs['theme_color'] ?? 'default';
                }
            }

            // Fallbacks de compatibilidade para a versão antiga
            if ($cockpitType === 'secretary' && $themeColor === 'default') {
                $c = $this->colors['blue'];
            } else {
                $c = $this->colors[$themeColor] ?? $this->colors['default'];
            }

            $darkModeRule = ($themeColor === 'dark') ? 'color-scheme: dark;' : '';
            
            $injectedCss = "
                :root {
                    {$darkModeRule}
                    --primary: {$c['primary']};
                    --primary-hover: {$c['hover']};
                    --primary-light: {$c['light']};
                    --primary-border: {$c['border']};
                    --bg-body: {$c['bg']};
                    --bg-card: {$c['card']};
                    --text-main: {$c['text']};
                    --text-muted: {$c['muted']};
                }
            ";

            return $css . $injectedCss;
        });

        // 3. Registrar Shortcode da Paleta de Cores
        $events->addListener('shortcodes.register', function($manager) {
            $manager->add('theme_palette', function($attrs) {
                $userId = $attrs['user_id'] ?? null;
                $themeColor = 'default';
                if ($userId) {
                    $prefsFile = dirname(__DIR__, 3) . '/public/uploads/prefs/user_' . $userId . '.json';
                    if (file_exists($prefsFile)) {
                        $prefs = json_decode(file_get_contents($prefsFile), true);
                        $themeColor = $prefs['theme_color'] ?? 'default';
                    }
                }

                $themeOpts = [
                    'default' => ['label' => 'Verde/Azul', 'color' => '#10b981'],
                    'blue'    => ['label' => 'Azul',       'color' => '#2271b1'],
                    'pink'    => ['label' => 'Rosa',       'color' => '#ec4899'],
                    'purple'  => ['label' => 'Roxo',       'color' => '#8b5cf6'],
                    'orange'  => ['label' => 'Laranja',    'color' => '#f97316'],
                    'dark'    => ['label' => 'Dark',       'color' => '#1e1e1e'],
                ];

                $optionsHtml = '';
                foreach ($themeOpts as $key => $opt) {
                    $isSelected = ($themeColor === $key);
                    $checked = $isSelected ? 'checked' : '';
                    $borderColor = $isSelected ? '#1e293b' : 'transparent';
                    
                    $optionsHtml .= "
                    <label style=\"cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:4px;\">
                        <input type=\"radio\" name=\"theme_color\" value=\"{$key}\" {$checked} style=\"display:none;\" onchange=\"previewTheme('{$key}')\">
                        <span style=\"width:32px;height:32px;border-radius:50%;background:{$opt['color']};display:block;border:3px solid {$borderColor};box-shadow:0 0 0 2px {$opt['color']}40;\" id=\"swatch-{$key}\"></span>
                        <span style=\"font-size:11px;font-weight:600;color:#64748b;\">{$opt['label']}</span>
                    </label>
                    ";
                }

                $colorsJson = json_encode($this->colors);
                $js = "
                <script>
                if (typeof window.themeColors === 'undefined') {
                    window.themeColors = {$colorsJson};
                    window.previewTheme = function(key) {
                        if (themeColors[key]) {
                            const c = themeColors[key];
                            const root = document.documentElement;
                            root.style.setProperty('--primary', c.primary);
                            root.style.setProperty('--primary-hover', c.hover);
                            root.style.setProperty('--primary-light', c.light);
                            root.style.setProperty('--primary-border', c.border);
                            root.style.setProperty('--bg-body', c.bg);
                            root.style.setProperty('--bg-card', c.card);
                            root.style.setProperty('--text-main', c.text);
                            root.style.setProperty('--text-muted', c.muted);
                            
                            if (key === 'dark') {
                                root.style.colorScheme = 'dark';
                            } else {
                                root.style.colorScheme = 'light';
                            }
                            
                            document.querySelectorAll('span[id^=\"swatch-\"]').forEach(el => el.style.borderColor = 'transparent');
                            document.getElementById('swatch-' + key).style.borderColor = '#1e293b';
                        }
                    };
                    window.updateLiveTheme = window.previewTheme;
                }
                </script>
                ";

                return "
                <div class=\"settings-form-group\">
                    <label><i class=\"fas fa-palette\" style=\"color:var(--primary);width:16px;\"></i> Cor do Tema</label>
                    <div style=\"display:flex;gap:10px;flex-wrap:wrap;margin-top:5px;\">
                        {$optionsHtml}
                    </div>
                </div>
                " . $js;
            }, 'Renderiza a paleta de cores para o modal de configurações', ['user_id' => 'ID do usuário logado'], 'Interface/Perfil');
        });
    }
}
