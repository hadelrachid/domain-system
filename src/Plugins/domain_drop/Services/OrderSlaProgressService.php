<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DateTimeImmutable;
use Throwable;

/**
 * Calcula a barra de progresso regressiva (contagem até entrega) e o estado de SLA
 * de cada pedido para exibição no painel administrativo e área do cliente.
 */
class OrderSlaProgressService
{
    public const DEFAULT_DAYS_BR = 7;
    public const DEFAULT_DAYS_INTL = 25;

    /**
     * Calcula o estado de progresso regressivo do pedido.
     *
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $disputes
     * @param DateTimeImmutable|null $now
     * @param array<string, mixed>|null $sdkTracking
     * @return array<string, mixed>
     */
    public function calculate(
        array $order,
        array $disputes = [],
        ?DateTimeImmutable $now = null,
        ?array $sdkTracking = null
    ): array {
        $now = $now ?? new DateTimeImmutable('now');
        $origin = strtoupper(trim((string)($order['seller_origin'] ?? 'BR')));
        if ($origin !== 'CN') {
            $origin = 'BR';
        }

        $expectedDays = (int)($order['expected_delivery_days'] ?? 0);
        if ($expectedDays <= 0) {
            $expectedDays = $origin === 'CN' ? self::DEFAULT_DAYS_INTL : self::DEFAULT_DAYS_BR;
        }

        $createdAt = $this->parseDate((string)($order['created_at'] ?? '')) ?? $now;
        $createdDay = $createdAt->setTime(0, 0, 0);
        $nowDay = $now->setTime(0, 0, 0);

        $daysElapsed = $nowDay >= $createdDay ? (int)$createdDay->diff($nowDay)->days : 0;
        $daysRemaining = max(0, $expectedDays - $daysElapsed);
        $percentRemaining = (int)max(0, min(100, round(($daysRemaining / max(1, $expectedDays)) * 100)));

        $trackingAnalysis = $sdkTracking !== null ? $this->parseSdkTracking($sdkTracking) : [
            'delivered'   => false,
            'last_event'  => '',
            'status_code' => '',
        ];

        $hasComplaint = !empty($disputes);
        $latestDispute = $hasComplaint ? $disputes[0] : null;
        $mediationSent = is_array($latestDispute) && (($latestDispute['mediation_status'] ?? '') === 'sent');

        $sdkDelivered = !empty($order['sdk_delivered_at'])
            || strtolower((string)($order['status'] ?? '')) === 'delivered'
            || ($trackingAnalysis['delivered'] === true);

        if ($hasComplaint) {
            return [
                'origin'            => $origin,
                'expected_days'     => $expectedDays,
                'days_elapsed'      => $daysElapsed,
                'days_remaining'    => $daysRemaining,
                'percent_remaining' => $percentRemaining,
                'bar_percent'       => max(8, $percentRemaining),
                'state'             => 'complaint_open',
                'tone'              => 'danger',
                'sdk_delivered'     => $sdkDelivered,
                'has_complaint'     => true,
                'mediation_sent'    => $mediationSent,
                'last_sdk_event'    => $trackingAnalysis['last_event'],
                'label'             => $mediationSent
                    ? 'Reclamação Aberta • Intermediação Enviada'
                    : 'Reclamação do Cliente Aberta',
            ];
        }

        if ($sdkDelivered) {
            return [
                'origin'            => $origin,
                'expected_days'     => $expectedDays,
                'days_elapsed'      => $daysElapsed,
                'days_remaining'    => 0,
                'percent_remaining' => 0,
                'bar_percent'       => 100,
                'state'             => 'ok_delivered',
                'tone'              => 'success',
                'sdk_delivered'     => true,
                'has_complaint'     => false,
                'mediation_sent'    => false,
                'last_sdk_event'    => $trackingAnalysis['last_event'],
                'label'             => 'Tudo Ok! Entregue no local sem reclamações',
            ];
        }

        if ($daysRemaining <= 0) {
            return [
                'origin'            => $origin,
                'expected_days'     => $expectedDays,
                'days_elapsed'      => $daysElapsed,
                'days_remaining'    => 0,
                'percent_remaining' => 0,
                'bar_percent'       => 100,
                'state'             => 'delayed_awaiting_customer',
                'tone'              => 'warning',
                'sdk_delivered'     => false,
                'has_complaint'     => false,
                'mediation_sent'    => false,
                'last_sdk_event'    => $trackingAnalysis['last_event'],
                'label'             => 'Entrega em atraso — Aguardando cliente',
            ];
        }

        return [
            'origin'            => $origin,
            'expected_days'     => $expectedDays,
            'days_elapsed'      => $daysElapsed,
            'days_remaining'    => $daysRemaining,
            'percent_remaining' => $percentRemaining,
            'bar_percent'       => $percentRemaining,
            'state'             => 'in_transit_countdown',
            'tone'              => $daysRemaining <= 2 ? 'amber' : 'info',
            'sdk_delivered'     => false,
            'has_complaint'     => false,
            'mediation_sent'    => false,
            'last_sdk_event'    => $trackingAnalysis['last_event'],
            'label'             => sprintf('%d de %d dias restantes', $daysRemaining, $expectedDays),
        ];
    }

    /**
     * Analisa resposta do método queryTrackingInfo do SDK AliExpress para detectar entrega local.
     *
     * @param array<string, mixed> $trackingResponse
     * @return array{delivered: bool, last_event: string, status_code: string}
     */
    public function parseSdkTracking(array $trackingResponse): array
    {
        if (isset($trackingResponse['delivered']) && $trackingResponse['delivered'] === true) {
            return [
                'delivered'   => true,
                'last_event'  => (string)($trackingResponse['last_event'] ?? 'Entregue ao destinatário'),
                'status_code' => (string)($trackingResponse['status'] ?? 'DELIVERED'),
            ];
        }

        $json = strtolower((string)json_encode($trackingResponse, JSON_UNESCAPED_UNICODE));
        $deliveredSignals = [
            'delivered',
            'signed_by_customer',
            'objeto entregue ao destinat',
            'entregue no local',
            'package delivered',
            'successfully delivered',
        ];

        $delivered = false;
        foreach ($deliveredSignals as $signal) {
            if (str_contains($json, $signal)) {
                $delivered = true;
                break;
            }
        }

        $lastEvent = '';
        $details = $trackingResponse['aliexpress_logistics_buyer_freight_get_response']['result']['details']['details']
            ?? $trackingResponse['details']
            ?? $trackingResponse['events']
            ?? [];
        if (is_array($details) && !empty($details)) {
            $first = reset($details);
            if (is_array($first)) {
                $lastEvent = (string)($first['event_desc'] ?? $first['desc'] ?? $first['status'] ?? '');
            } elseif (is_string($first)) {
                $lastEvent = $first;
            }
        }

        return [
            'delivered'   => $delivered,
            'last_event'  => $lastEvent,
            'status_code' => $delivered ? 'DELIVERED' : (string)($trackingResponse['status'] ?? 'IN_TRANSIT'),
        ];
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($trimmed);
        } catch (Throwable $e) {
            return null;
        }
    }
}
