<?php

namespace DomainSystem\Plugins\analytics\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Application;

class TrackerController
{
    public function track(Request $request)
    {
        // Pega corpo JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        $type = $input['type'] ?? 'unknown';
        $source = $input['source'] ?? 'unknown';
        
        // Hash de sessão simples (IP + UserAgent) para filtrar views únicos
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $hash = hash('sha256', $ip . $ua . date('Y-m-d')); // Único por dia
        
        try {
            $db = Application::getInstance()->getContainer()->make(\DomainSystem\SystemApps\Database\Connection::class)->getPdo();
            
            $stmt = $db->prepare("INSERT INTO analytics_events (event_type, event_source, session_hash) VALUES (?, ?, ?)");
            $stmt->execute([$type, $source, $hash]);
            
            return Response::json(['status' => 'ok']);
        } catch (\Exception $e) {
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
}
