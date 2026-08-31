<?php

namespace DomainSystem\Plugins\ApiGateway\Middleware;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class ApiAuthMiddleware
{
    private string $secretKey;

    public function __construct()
    {
        // Define hardcoded string or read from env.
        $envFile = dirname(__DIR__, 4) . "/.env";
        $secret = "z3cr3t_k3y_for_b0ts"; // Default fallback
        if (file_exists($envFile)) {
            $env = parse_ini_file($envFile);
            if (isset($env["API_SECRET_KEY"])) {
                $secret = $env["API_SECRET_KEY"];
            }
        }
        $this->secretKey = $secret;
    }

    public function handle(string $uri, Request $request)
    {
        if (str_starts_with($uri, "/api/")) {
            $headers = function_exists("apache_request_headers") ? apache_request_headers() : [];
            $authHeader = $request->server["HTTP_AUTHORIZATION"] ?? $headers["Authorization"] ?? "";
            
            if (empty($authHeader) || !str_starts_with($authHeader, "Bearer ")) {
                $this->unauthorized("Token não fornecido ou inválido no formato Bearer.");
            }

            $token = trim(substr($authHeader, 7));

            if ($token !== $this->secretKey) {
                $this->unauthorized("Token de API inválido.");
            }
        }
    }

    private function unauthorized(string $msg)
    {
        header("Content-Type: application/json");
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized", "message" => $msg]);
        exit;
    }
}

