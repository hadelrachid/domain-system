<?php

namespace DomainSystem\Plugins\appointments\Services;

use DomainSystem\Plugins\appointments\Contracts\BookingCallbackInterface;
use DomainSystem\Core\Http\Response;

class BookingCallbackService implements BookingCallbackInterface
{
    private array $messages = [
        self::ERR_MISSING_FIELDS => ['success' => false, 'message' => 'Por favor, preencha os campos obrigatórios.'],
        self::ERR_INVALID_EMAIL  => ['success' => false, 'message' => 'Por favor, insira um endereço de e-mail válido (contendo @ e o domínio).'],
        self::ERR_SLOT_OCCUPIED  => ['success' => false, 'message' => 'Este horário acabou de ser ocupado. Escolha outro.'],
        self::SUCCESS_BOOKED     => ['success' => true, 'message' => 'Agendamento solicitado com sucesso!']
    ];

    public function respond(int|string $code, array $extraData = []): Response
    {
        $payload = $this->messages[$code] ?? ['success' => false, 'message' => 'Erro desconhecido.'];
        
        if (!empty($extraData)) {
            $payload = array_merge($payload, $extraData);
        }

        return Response::json($payload);
    }
}
