<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\DTO;

/**
 * Representa um evento atômico de interação/demanda capturado no front-end.
 */
final class DemandEvent
{
    public function __construct(
        public readonly string $productId,
        public readonly string $productTitle,
        public readonly string $categorySlug,
        public readonly string $actionType, // 'view_details' | 'buy_click' | 'conversion'
        public readonly int $timestamp = 0,
        public readonly ?string $sessionId = null
    ) {
    }

    public static function fromArray(array $payload, ?string $sessionId = null): self
    {
        $rawAction = (string)($payload['action'] ?? ($payload['event_type'] ?? 'view_details'));
        $action = match ($rawAction) {
            'buy_click', 'click_buy', 'buy' => 'buy_click',
            'conversion', 'purchase' => 'conversion',
            default => 'view_details',
        };

        return new self(
            productId: trim((string)($payload['product_id'] ?? ($payload['ali_product_id'] ?? ''))),
            productTitle: trim(mb_substr((string)($payload['title'] ?? ($payload['product_title'] ?? '')), 0, 255)),
            categorySlug: trim(mb_substr((string)($payload['category'] ?? ($payload['category_slug'] ?? 'geral')), 0, 64)),
            actionType: $action,
            timestamp: (int)($payload['timestamp'] ?? time()),
            sessionId: $sessionId
        );
    }
}
