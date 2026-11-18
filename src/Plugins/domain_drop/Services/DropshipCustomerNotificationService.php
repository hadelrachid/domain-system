<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
use Throwable;

/**
 * Serviço de Notificações Transacionais por E-mail para Clientes de Dropshipping (SRP / SOLID).
 */
class DropshipCustomerNotificationService
{
    public function __construct(
        private readonly ?EmailSenderInterface $mailer = null,
        private readonly string $storeName = 'RachidD Store',
        private readonly string $supportEmail = 'contato@rachidd.com'
    ) {}

    /**
     * Envia confirmação de pagamento aprovado do pedido.
     */
    public function notifyOrderPaid(array $order): bool
    {
        $to = trim((string)($order['customer_email'] ?? ''));
        if (!$this->canSend($to)) {
            return false;
        }

        $orderRef = (string)($order['order_ref'] ?? 'N/A');
        $customerName = htmlspecialchars((string)($order['customer_name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $productTitle = htmlspecialchars((string)($order['product_title'] ?? 'Item do Catálogo Dropshipping'), ENT_QUOTES, 'UTF-8');
        $totalPaid = number_format((float)($order['total_paid_brl'] ?? 0.0), 2, ',', '.');
        $expectedDays = (int)($order['expected_delivery_days'] ?? 15);
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $accountUrl = $baseUrl . '/minha-conta';

        $subject = "✅ Pedido Confirmado #{$orderRef} - {$this->storeName}";

        $message = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 24px 0; background: #0a0e17; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background: #111827; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden;">
        <!-- Cabeçalho -->
        <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #1e293b, #0f172a); border-bottom: 2px solid #00d284; text-align: center;">
                <h1 style="margin: 0; font-size: 24px; color: #ffffff; letter-spacing: -0.5px;">🛍️ {$this->storeName}</h1>
                <p style="margin: 6px 0 0; font-size: 13px; color: #00d284; font-weight: 600;">CONFIRMAÇÃO DE PAGAMENTO & PEDIDO</p>
            </td>
        </tr>
        <!-- Conteúdo -->
        <tr>
            <td style="padding: 32px;">
                <p style="font-size: 16px; margin: 0 0 16px; color: #f8fafc;">Olá, <strong>{$customerName}</strong>!</p>
                <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 24px;">
                    Excelente notícia! Seu pagamento foi confirmado com sucesso e nosso fornecedor já foi notificado para preparar o seu produto para despacho.
                </p>

                <!-- Caixa do Pedido -->
                <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 20px; margin-bottom: 24px;">
                    <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Detalhes da Compra</div>
                    <div style="font-size: 15px; font-weight: 700; color: #45f3ff; margin-bottom: 6px;">Pedido: #{$orderRef}</div>
                    <div style="font-size: 14px; color: #e2e8f0; margin-bottom: 12px;">{$productTitle}</div>
                    <div style="font-size: 14px; color: #94a3b8;">
                        Total Pago: <strong style="color: #00d284;">R$ {$totalPaid}</strong><br>
                        Prazo Estimado de Entrega: <strong style="color: #ffffff;">{$expectedDays} dias úteis</strong>
                    </div>
                </div>

                <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 28px;">
                    Assim que o produto for postado pela transportadora, nós lhe enviaremos outro e-mail com o seu <strong>Código de Rastreamento</strong> oficial.
                </p>

                <!-- Botão de Ação -->
                <div style="text-align: center; margin-bottom: 28px;">
                    <a href="{$accountUrl}" style="background: #00d284; color: #0a0e17; text-decoration: none; padding: 13px 28px; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-block;">
                        Acessar Minha Conta & Acompanhar Pedido &rarr;
                    </a>
                </div>

                <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 28px 0;">

                <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;">
                    Dúvidas ou atendimento? Responda a este e-mail ou entre em contato diretamente pelo canal oficial: <a href="mailto:{$this->supportEmail}" style="color: #38bdf8; text-decoration: none;">{$this->supportEmail}</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        try {
            $this->mailer->send($to, $subject, $message);
            return true;
        } catch (Throwable $e) {
            error_log("Falha ao enviar e-mail de pedido pago para {$to}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envia notificação de despacho do produto com o Código de Rastreio oficial.
     */
    public function notifyOrderShipped(array $order, string $trackingCode): bool
    {
        $to = trim((string)($order['customer_email'] ?? ''));
        $trackingCode = strtoupper(trim($trackingCode));
        if (!$this->canSend($to) || $trackingCode === '') {
            return false;
        }

        $orderRef = (string)($order['order_ref'] ?? 'N/A');
        $customerName = htmlspecialchars((string)($order['customer_name'] ?? 'Cliente'), ENT_QUOTES, 'UTF-8');
        $productTitle = htmlspecialchars((string)($order['product_title'] ?? 'Item do Catálogo Dropshipping'), ENT_QUOTES, 'UTF-8');
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $trackUrl = $baseUrl . '/minha-conta?rastrear=' . urlencode($trackingCode);
        $publicTrackUrl = $baseUrl . '/rastreio?codigo=' . urlencode($trackingCode);

        $subject = "🚚 Seu Pedido #{$orderRef} foi Despachado! Rastreio: {$trackingCode}";

        $message = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 24px 0; background: #0a0e17; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f1f5f9;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background: #111827; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); overflow: hidden;">
        <!-- Cabeçalho -->
        <tr>
            <td style="padding: 28px 32px; background: linear-gradient(135deg, #1e293b, #0f172a); border-bottom: 2px solid #38bdf8; text-align: center;">
                <h1 style="margin: 0; font-size: 24px; color: #ffffff; letter-spacing: -0.5px;">📦 {$this->storeName}</h1>
                <p style="margin: 6px 0 0; font-size: 13px; color: #38bdf8; font-weight: 600;">SEU PACOTE ESTÁ EM TRÂNSITO</p>
            </td>
        </tr>
        <!-- Conteúdo -->
        <tr>
            <td style="padding: 32px;">
                <p style="font-size: 16px; margin: 0 0 16px; color: #f8fafc;">Olá, <strong>{$customerName}</strong>!</p>
                <p style="font-size: 14px; line-height: 1.6; color: #cbd5e1; margin: 0 0 24px;">
                    Seu produto acabou de ser coletado pela transportadora e já está a caminho da sua casa!
                </p>

                <!-- Destaque do Código de Rastreio -->
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.35); border-radius: 8px; padding: 22px; text-align: center; margin-bottom: 24px;">
                    <div style="font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Código de Rastreamento</div>
                    <div style="font-family: monospace; font-size: 22px; font-weight: 800; color: #45f3ff; letter-spacing: 2px; margin-bottom: 14px;">{$trackingCode}</div>
                    <div>
                        <a href="{$trackUrl}" style="background: #38bdf8; color: #0a0e17; text-decoration: none; padding: 11px 24px; border-radius: 6px; font-weight: 700; font-size: 13px; display: inline-block;">
                            Rastrear Pacote na RachidD Store &rarr;
                        </a>
                    </div>
                </div>

                <!-- Detalhes do Pedido -->
                <div style="background: rgba(0,0,0,0.25); border-radius: 6px; padding: 14px 18px; margin-bottom: 24px; font-size: 13px; color: #94a3b8;">
                    <strong>Pedido:</strong> #{$orderRef}<br>
                    <strong>Produto:</strong> {$productTitle}
                </div>

                <p style="font-size: 13px; line-height: 1.6; color: #94a3b8; margin: 0 0 24px;">
                    Você também pode consultar o rastreamento público a qualquer momento pelo link:<br>
                    <a href="{$publicTrackUrl}" style="color: #45f3ff; word-break: break-all;">{$publicTrackUrl}</a>
                </p>

                <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 28px 0;">

                <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin: 0;">
                    Atendimento exclusivo: <a href="mailto:{$this->supportEmail}" style="color: #38bdf8; text-decoration: none;">{$this->supportEmail}</a>
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        try {
            $this->mailer->send($to, $subject, $message);
            return true;
        } catch (Throwable $e) {
            error_log("Falha ao enviar e-mail de despacho para {$to}: " . $e->getMessage());
            return false;
        }
    }

    private function canSend(string $email): bool
    {
        return $this->mailer !== null && $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
