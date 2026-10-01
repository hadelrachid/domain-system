<?php

namespace DomainSystem\Plugins\ApiGateway\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Application;
use Exception;

class WebhookController
{
    public function handleWhatsApp(Request $request)
    {
        header("Content-Type: application/json");
        
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        if (!$data) {
            http_response_code(400);
            return json_encode(["error" => "Payload invalido"]);
        }

        try {
            // Dispara um evento genérico para o SO
            // Qualquer plugin pode escutar 'webhook.whatsapp.received'
            $app = Application::getInstance();
            $responses = $app->getDispatcher()->applyFilters('webhook.whatsapp.received', [], $data);

            http_response_code(200);
            return json_encode([
                "success" => true,
                "message" => "Webhook processado",
                "plugin_responses" => $responses
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(["error" => "Erro interno: " . $e->getMessage()]);
        }
    }
}
