<?php

namespace DomainSystem\Plugins\ai_hub\Providers;

use DomainSystem\Plugins\ai_hub\Contracts\AiProviderInterface;

class GeminiProvider implements AiProviderInterface
{
    private string $apiKey;
    private string $systemContext = '';

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setSystemContext(string $context): void
    {
        $this->systemContext = $context;
    }

    public function sendPrompt(string $prompt): string
    {
        if (empty($this->apiKey)) {
            return "Erro: A chave de API do Gemini não está configurada no Cérebro I.A.";
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $this->apiKey;

        $data = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $this->systemContext . "\n\n" . $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.2
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas para dev local XAMPP
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return "Erro cURL: " . $error;
        }

        $json = json_decode($response, true);
        
        if (isset($json['error'])) {
            return "Erro da API Gemini: " . ($json['error']['message'] ?? json_encode($json['error']));
        }

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        return "Erro: Resposta inesperada da API.\n" . $response;
    }
}
