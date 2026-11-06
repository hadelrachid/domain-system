<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\Controllers;

use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Responses\JsonResponse;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use PDO;
use Throwable;

/**
 * Controlador de Webhooks e Notificações de Bancos/Gateways e Afiliados (SRP / DIP / Sem Superglobais).
 * Suporta Hotmart, Eduzz, Monetizze, ClickBank, Mercado Pago, PayPal e Stripe.
 */
class WebhookController
{
    private OrderRepositoryInterface $orderRepo;
    private PaymentGatewayRegistryInterface $gatewayRegistry;
    private ?EventDispatcherInterface $dispatcher;
    private ?PDO $pdo;

    public function __construct(
        OrderRepositoryInterface $orderRepo,
        PaymentGatewayRegistryInterface $gatewayRegistry,
        ?EventDispatcherInterface $dispatcher = null,
        DatabaseConnectionInterface|PDO|null $db = null
    ) {
        $this->orderRepo = $orderRepo;
        $this->gatewayRegistry = $gatewayRegistry;
        $this->dispatcher = $dispatcher;
        $this->pdo = $db instanceof DatabaseConnectionInterface ? $db->getPdo() : $db;
    }

    /**
     * Endpoint do Webhook de liquidação: POST /webhook/payment/{gateway}
     */
    public function handle(Request $request, $gateway): ResponseInterface
    {
        $gatewayId = strtolower(trim((string)$gateway));
        $paymentGateway = $this->gatewayRegistry->get($gatewayId);

        if (!$paymentGateway) {
            return new JsonResponse(['received' => false, 'error' => "Gateway '{$gatewayId}' desconhecido."], 404);
        }

        $result = $paymentGateway->handleWebhook($request);
        if (!$result || empty($result['order_ref'])) {
            return new JsonResponse(['received' => true, 'action' => 'ignored'], 200);
        }

        $orderRef = (string)$result['order_ref'];
        $order = $this->orderRepo->findByOrderRef($orderRef);

        // Cenário de Reembolso (T13): Pedido previamente pago que foi estornado/reembolsado
        if ($order && ($result['status'] ?? '') === 'refunded' && ($order['status'] ?? '') === 'paid') {
            $this->orderRepo->updateStatus((int)$order['id'], 'refunded');
            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch('payment.refunded', [
                    'user_id'   => (int)$order['user_id'],
                    'course_id' => (int)$order['item_id'],
                    'order_ref' => $orderRef,
                ]);
            }
            return new JsonResponse(['received' => true, 'order_status' => 'refunded'], 200);
        }

        // Cenário 1: Pedido pré-existente (ex: criado no checkout interno do DomainCommerce)
        if ($order && ($order['status'] ?? '') === 'paid') {
            return new JsonResponse(['received' => true, 'action' => 'duplicate'], 200);
        }

        if ($order && ($order['status'] ?? '') === 'pending' && ($result['status'] ?? '') === 'approved') {
            $paid = (float)($result['total'] ?? 0.0);
            $expected = (float)($order['total'] ?? 0.0);
            if ($paid > 0.0 && $paid + 0.01 < $expected) {
                error_log("[commerce] Valor divergente order={$orderRef} pago={$paid} esperado={$expected}");
                return new JsonResponse(['received' => true, 'action' => 'amount_mismatch'], 200);
            }

            $txId = (string)($result['transaction_id'] ?? '');
            $this->orderRepo->updateStatus((int)$order['id'], 'paid', $txId);

            if ($this->dispatcher !== null) {
                $this->dispatcher->dispatch('payment.approved', [
                    'user_id'   => (int)$order['user_id'],
                    'course_id' => (int)$order['item_id'],
                    'order_ref' => $orderRef,
                ]);
            }

            return new JsonResponse([
                'received'     => true,
                'order_status' => 'paid',
                'order_ref'    => $orderRef,
                'user_id'      => (int)$order['user_id'],
                'course_id'    => (int)$order['item_id'],
            ], 200);
        }

        // Cenário 2: Compra vinda de Plataforma de Afiliados Externa (Hotmart, Eduzz, Monetizze, ClickBank)
        if (!$order && ($result['status'] ?? '') === 'approved' && !empty($result['student_email'])) {
            $existing = $this->orderRepo->findByOrderRef($orderRef);
            if ($existing && ($existing['status'] ?? '') === 'paid') {
                return new JsonResponse(['received' => true, 'action' => 'duplicate'], 200);
            }

            $productId = (string)($result['product_id'] ?? '');
            $courseId = $this->resolveCourseByProduct($productId, (string)$gatewayId);

            if ($courseId <= 0) {
                error_log("[commerce] Produto de afiliado sem curso vinculado: gateway={$gatewayId} product={$productId} order={$orderRef}");
                return new JsonResponse(['received' => true, 'action' => 'orphan'], 200);
            }

            $userId = $this->resolveOrCreateUser(
                (string)$result['student_email'],
                (string)($result['student_name'] ?? 'Aluno')
            );

            if ($userId > 0) {
                try {
                    $this->orderRepo->createOrder([
                        'order_ref'      => $orderRef,
                        'user_id'        => $userId,
                        'item_type'      => 'course',
                        'item_id'        => $courseId,
                        'total'          => (float)($result['total'] ?? 0.0),
                        'currency'       => $gatewayId === 'clickbank' ? 'USD' : 'BRL',
                        'gateway'        => $gatewayId,
                        'payment_method' => 'external_checkout',
                        'status'         => 'paid',
                        'transaction_id' => (string)($result['transaction_id'] ?? $orderRef),
                    ]);
                } catch (\Throwable $e) {
                    error_log('[commerce] Falha ao gravar pedido de afiliado: ' . $e->getMessage());
                    return new JsonResponse(['received' => false, 'error' => 'order_persist_failed'], 500);
                }

                if ($this->dispatcher !== null) {
                    $this->dispatcher->dispatch('payment.approved', [
                        'user_id'   => $userId,
                        'course_id' => $courseId,
                        'order_ref' => $orderRef,
                    ]);
                }

                return new JsonResponse([
                    'received'     => true,
                    'order_status' => 'paid',
                    'provisioned'  => true,
                    'user_id'      => $userId,
                    'course_id'    => $courseId,
                    'order_ref'    => $orderRef,
                ], 200);
            }
        }

        return new JsonResponse(['received' => true, 'action' => 'processed'], 200);
    }

    private function resolveOrCreateUser(string $email, string $name): int
    {
        if ($this->pdo === null) {
            return 0;
        }

        $email = strtolower(trim($email));
        if ($email === '') {
            return 0;
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            return (int)$existingId;
        }

        // Cria o usuário com segurança e hash de senha aleatória
        try {
            $randomPass = bin2hex(random_bytes(8));
            $hash = password_hash($randomPass, PASSWORD_DEFAULT);
            $cleanName = trim($name) !== '' ? trim($name) : 'Aluno';
            $cleanUsername = preg_replace('/[^a-z0-9_.]/', '', strtolower(str_replace(' ', '', $cleanName)));
            if (strlen($cleanUsername) < 3) {
                $cleanUsername = 'aluno' . substr(md5($email . (string)microtime(true)), 0, 6);
            }

            try {
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$cleanName, $cleanUsername, $email, $hash]);
            } catch (Throwable $e) {
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO users (name, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$cleanName, $email, $hash]);
            }
            return (int)$this->pdo->lastInsertId();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function resolveCourseByProduct(string $productId, string $gatewayId): int
    {
        if ($this->pdo === null) {
            return 0;
        }

        $productId = trim($productId);
        if ($productId === '') {
            return 0;
        }

        // 1. Busca estrita por external_product_id vinculado à plataforma informada (ou genérica)
        $stmt = $this->pdo->prepare(
            "SELECT id FROM academy_courses 
             WHERE external_product_id = ? 
               AND (external_platform = ? OR external_platform IS NULL OR external_platform = '') 
             LIMIT 1"
        );
        $stmt->execute([$productId, $gatewayId]);
        $cId = $stmt->fetchColumn();
        if ($cId) {
            return (int)$cId;
        }

        // 2. Busca secundária por slug exato
        $stmtSlug = $this->pdo->prepare("SELECT id FROM academy_courses WHERE slug = ? LIMIT 1");
        $stmtSlug->execute([$productId]);
        $cId = $stmtSlug->fetchColumn();
        return $cId ? (int)$cId : 0;
    }
}
