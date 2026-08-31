<?php

namespace DomainSystem\Plugins\ai_hub\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Plugin\PluginManager;

class BuilderController
{
    private ThemeManager $theme;
    private PluginManager $pluginManager;

    public function __construct(ThemeManager $theme, PluginManager $pluginManager)
    {
        $this->theme = $theme;
        $this->pluginManager = $pluginManager;
    }

    public function index(Request $request): Response
    {
        $plugins = $this->pluginManager->getPlugins();
        $pluginList = [];
        
        foreach ($plugins as $p) {
            $pluginList[] = [
                'name' => $p->getName(),
                'active' => $p->isActive()
            ];
        }

        $html = $this->theme->render('builder', ['plugins' => $pluginList], __DIR__ . '/../views');
        return new Response($html);
    }

    public function generate(Request $request): Response
    {
        $data = $request->getParsedBody();
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            return new Response(json_encode(['error' => 'Prompt vazio.']), 400, ['Content-Type' => 'application/json']);
        }

        $configPath = DOMAIN_SYSTEM_ROOT . '/temp/ai_hub_config.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        
        $apiKey = $config['api_keys']['gemini'] ?? '';
        
        $provider = new \DomainSystem\Plugins\ai_hub\Providers\GeminiProvider($apiKey);
        $agent = new \DomainSystem\Plugins\ai_hub\Services\AiAgentService($provider);
        
        // Define strict rules for the plugin builder
        $systemContext = "Você é um assistente especializado em criar plugins para o Domain System Kernel.\n";
        $systemContext .= "Regras rígidas:\n";
        $systemContext .= "1. NÃO use banco de dados diretamente, nem PDO. Use APENAS Injeção de Dependência nas interfaces disponíveis: PatientRepositoryInterface, AppointmentRepositoryInterface.\n";
        $systemContext .= "2. Gere APENAS um único arquivo PHP (Plugin.php) contendo a classe 'Plugin extends AbstractPlugin'.\n";
        $systemContext .= "3. Inclua as views diretamente no método usando Heredoc para facilitar a leitura.\n";
        $systemContext .= "4. Não use Markdown, retorne APENAS o código PHP puro, começando com <?php.\n";
        
        $provider->setSystemContext($systemContext);
        
        $response = $agent->executeTask($prompt);

        return new Response(json_encode(['code' => $response]), 200, ['Content-Type' => 'application/json']);
    }
}
