<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\DTO;

/**
 * Representa um evento atômico de interação/demanda capturado no front-end.
 */
final class DemandEvent
{
    public readonly string $ipAddress;

    public function __construct(
        public readonly string $productId,
        public readonly string $productTitle,
        public readonly string $categorySlug,
        public readonly string $actionType, // 'view_details' | 'buy_click' | 'conversion'
        public readonly int $timestamp = 0,
        public readonly ?string $sessionId = null,
        public readonly ?int $customerId = null,
        public readonly ?string $customerName = null,
        public readonly ?string $customerEmail = null,
        public readonly string $userType = 'externo', // 'cliente_logado' | 'externo'
        ?string $ipAddress = null
    ) {
        $this->ipAddress = $ipAddress ?: self::resolveClientIp();
    }

    public static function resolveClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $raw = (string)$_SERVER[$header];
                $ips = explode(',', $raw);
                $candidate = trim($ips[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    return $candidate;
                }
            }
        }

        return '127.0.0.1';
    }

    public static function fromArray(
        array $payload,
        ?string $sessionId = null,
        ?int $customerId = null,
        ?string $customerName = null,
        ?string $customerEmail = null,
        ?string $ipAddress = null
    ): self {
        $rawAction = (string)($payload['action'] ?? ($payload['event_type'] ?? 'view_details'));
        $action = match ($rawAction) {
            'buy_click', 'click_buy', 'buy' => 'buy_click',
            'conversion', 'purchase' => 'conversion',
            default => 'view_details',
        };

        $resolvedCustomerId = $customerId ?? (isset($payload['customer_id']) ? (int)$payload['customer_id'] : null);
        $resolvedCustomerName = $customerName ?? (isset($payload['customer_name']) ? (string)$payload['customer_name'] : null);
        $resolvedCustomerEmail = $customerEmail ?? (isset($payload['customer_email']) ? (string)$payload['customer_email'] : null);
        $resolvedIp = $ipAddress ?? (isset($payload['ip_address']) ? (string)$payload['ip_address'] : null);

        $hasUser = ($resolvedCustomerId !== null && $resolvedCustomerId > 0)
            || (!empty($resolvedCustomerEmail) && filter_var($resolvedCustomerEmail, FILTER_VALIDATE_EMAIL));

        $userType = $hasUser ? 'cliente_logado' : 'externo';

        return new self(
            productId: trim((string)($payload['product_id'] ?? ($payload['ali_product_id'] ?? ''))),
            productTitle: trim(mb_substr((string)($payload['title'] ?? ($payload['product_title'] ?? '')), 0, 255)),
            categorySlug: trim(mb_substr((string)($payload['category'] ?? ($payload['category_slug'] ?? 'geral')), 0, 64)),
            actionType: $action,
            timestamp: (int)($payload['timestamp'] ?? time()),
            sessionId: $sessionId,
            customerId: $resolvedCustomerId,
            customerName: $resolvedCustomerName ? trim($resolvedCustomerName) : null,
            customerEmail: $resolvedCustomerEmail ? trim(strtolower($resolvedCustomerEmail)) : null,
            userType: $userType,
            ipAddress: $resolvedIp
        );
    }
}
