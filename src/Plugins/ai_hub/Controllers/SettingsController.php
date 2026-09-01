<?php

namespace DomainSystem\Plugins\ai_hub\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;

class SettingsController
{
    private ThemeManager $theme;
    private string $configPath;

    public function __construct(ThemeManager $theme)
    {
        $this->theme = $theme;
        $this->configPath = DOMAIN_SYSTEM_ROOT . '/temp/ai_hub_config.json';
    }

    public function index(Request $request): Response
    {
        $config = file_exists($this->configPath) ? json_decode(file_get_contents($this->configPath), true) : [
            'active_model' => 'gemini',
            'api_keys' => [
                'gemini' => '',
                'openai' => '',
                'deepseek' => '',
                'claude' => ''
            ]
        ];

        $html = $this->theme->render('settings', ['config' => $config], __DIR__ . '/../views');
        return new Response($html);
    }

    public function save(Request $request): Response
    {
        $data = $request->all();
        $config = [
            'active_model' => $data['active_model'] ?? 'gemini',
            'api_keys' => [
                'gemini' => $data['api_keys']['gemini'] ?? '',
                'openai' => $data['api_keys']['openai'] ?? '',
                'deepseek' => $data['api_keys']['deepseek'] ?? '',
                'claude' => $data['api_keys']['claude'] ?? ''
            ]
        ];

        file_put_contents($this->configPath, json_encode($config, JSON_PRETTY_PRINT));

        header("Location: " . BASE_URL . "/admin/ai-hub?saved=1");
        exit;
    }
}
