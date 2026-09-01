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

    public function testConnection(Request $request): Response
    {
        $config = file_exists($this->configPath) ? json_decode(file_get_contents($this->configPath), true) : [];
        $activeModel = $config['active_model'] ?? 'gemini';
        $apiKeys = $config['api_keys'] ?? [];
        $apiKey = $apiKeys[$activeModel] ?? '';

        if (empty($apiKey)) {
            return Response::json(['success' => false, 'message' => "Chave de API do motor '$activeModel' não configurada."]);
        }

        try {
            if ($activeModel === 'gemini') {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=" . $apiKey;
                $data = [
                    'contents' => [
                        ['parts' => [['text' => 'Diga apenas: "Conexão estabelecida com sucesso! Eu sou o Gemini e estou pronto para operar o CockPit."' ]]]
                    ]
                ];
                
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($data),
                        'ignore_errors' => true, // Permite ler o corpo do erro HTTP 400
                        'timeout' => 30 // Aumentado para 30s pois a API está lenta
                    ]
                ];
                $context  = stream_context_create($options);
                // O @ evita que o ErrorHandler global intercepte erros HTTP 5xx
                $result = @file_get_contents($url, false, $context);
                
                if ($result === FALSE) {
                    return Response::json(['success' => false, 'message' => 'Falha ao conectar com a API do Google Gemini. Verifique sua chave ou conexão de rede.']);
                }
                
                $response = json_decode($result, true);
                
                if (isset($response['error'])) {
                     return Response::json(['success' => false, 'message' => 'Erro da API: ' . ($response['error']['message'] ?? 'Desconhecido')]);
                }
                
                $text = $response['candidates'][0]['content']['parts'][0]['text'] ?? 'Resposta vazia da IA.';
                return Response::json(['success' => true, 'message' => trim($text)]);
            }
            
            return Response::json(['success' => false, 'message' => "Teste de conexão automático implementado apenas para Gemini no momento."]);
        } catch (\Exception $e) {
            return Response::json(['success' => false, 'message' => 'Exceção: ' . $e->getMessage()]);
        }
    }
}
