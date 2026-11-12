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
use InvalidArgumentException;
use PDO;
use RuntimeException;
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
        if ($order && ($result['status'] ?? '') === 'refunded') {
            if (($order['status'] ?? '') === 'refunded') {
                return new JsonResponse(['received' => true, 'action' => 'duplicate'], 200);
            }
            if (($order['status'] ?? '') === 'paid') {
                try {
                    $updated = $this->orderRepo->updateStatus((int)$order['id'], 'refunded');
                } catch (Throwable $e) {
                    error_log('[commerce] Erro ao registrar reembolso order=' . $orderRef . ': ' . $e->getMessage());
                    return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
                }
                if (!$updated) {
                    error_log('[commerce] Falha ao atualizar status para refunded order=' . $orderRef);
                    return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
                }
                if ($this->dispatcher !== null) {
                    $this->dispatcher->dispatch('payment.refunded', [
                        'user_id'   => (int)$order['user_id'],
                        'course_id' => (int)$order['item_id'],
                        'order_ref' => $orderRef,
                    ]);
                }
                return new JsonResponse(['received' => true, 'order_status' => 'refunded'], 200);
            }
            error_log("[commerce] Reembolso ignorado para pedido ainda pendente (orphan_refund): gateway={$gatewayId} order={$orderRef}");
        }

        // Cenário 1: Pedido pré-existente (ex: criado no checkout interno do DomainCommerce)
        if ($order && ($order['status'] ?? '') === 'paid') {
            return new JsonResponse(['received' => true, 'action' => 'duplicate'], 200);
        }

        if ($order && ($order['status'] ?? '') === 'pending' && ($result['status'] ?? '') === 'approved') {
            $paid = round((float)($result['total'] ?? 0.0), 2);
            $expected = round((float)($order['total'] ?? 0.0), 2);
            if ($paid > 0.0 && $paid + 0.01 < $expected) {
                error_log("[commerce] Valor divergente order={$orderRef} pago={$paid} esperado={$expected}");
                return new JsonResponse(['received' => true, 'action' => 'amount_mismatch'], 200);
            }

            if ($this->dispatcher !== null) {
                try {
                    $this->dispatcher->dispatch('payment.approved', [
                        'user_id'   => (int)$order['user_id'],
                        'course_id' => (int)$order['item_id'],
                        'item_id'   => (int)$order['item_id'],
                        'item_type' => (string)($order['item_type'] ?? 'course'),
                        'order_ref' => $orderRef,
                        'total'     => (float)($order['total'] ?? 0.0),
                    ]);
                } catch (Throwable $e) {
                    error_log('[commerce] Falha ao disparar fulfillment (payment.approved) order=' . $orderRef . ': ' . $e->getMessage());
                    return new JsonResponse(['received' => false, 'error' => 'fulfillment_dispatch_failed'], 500);
                }
            }

            $txId = (string)($result['transaction_id'] ?? '');
            try {
                $updated = $this->orderRepo->updateStatus((int)$order['id'], 'paid', $txId);
            } catch (Throwable $e) {
                error_log('[commerce] Erro ao atualizar pedido pendente para pago order=' . $orderRef . ': ' . $e->getMessage());
                return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
            }
            if (!$updated) {
                error_log('[commerce] Falha ao atualizar pedido pendente para pago order=' . $orderRef);
                return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
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

            if ($this->pdo === null) {
                error_log("[commerce] Banco de dados indisponível ao processar webhook de afiliado gateway={$gatewayId} order={$orderRef}");
                return new JsonResponse(['received' => false, 'error' => 'database_unavailable'], 500);
            }

            $productId = (string)($result['product_id'] ?? '');
            try {
                $courseId = $this->resolveCourseByProduct($productId, (string)$gatewayId);
            } catch (Throwable $e) {
                error_log("[commerce] Falha temporária ao consultar produto de afiliado gateway={$gatewayId} product={$productId} order={$orderRef}: " . $e->getMessage());
                return new JsonResponse(['received' => false, 'error' => 'course_lookup_failed'], 500);
            }

            if ($courseId <= 0) {
                error_log("[commerce] Produto de afiliado sem curso vinculado: gateway={$gatewayId} product={$productId} order={$orderRef}");
                return new JsonResponse(['received' => true, 'action' => 'orphan'], 200);
            }

            try {
                $userId = $this->resolveOrCreateUser(
                    (string)$result['student_email'],
                    (string)($result['student_name'] ?? 'Aluno')
                );
            } catch (InvalidArgumentException $e) {
                error_log("[commerce] Payload de webhook com e-mail inválido gateway={$gatewayId} order={$orderRef}: " . $e->getMessage());
                return new JsonResponse(['received' => false, 'error' => 'invalid_student_email'], 422);
            } catch (Throwable $e) {
                error_log("[commerce] Falha ao provisionar usuário no webhook gateway={$gatewayId} order={$orderRef}: " . $e->getMessage());
                return new JsonResponse(['received' => false, 'error' => 'user_provisioning_failed'], 500);
            }

            $txId = (string)($result['transaction_id'] ?? $orderRef);
            try {
                // Insere inicialmente como 'pending' para garantir lock único (UNIQUE order_ref) antes do dispatch de matrícula
                $createdOrderId = $this->orderRepo->createOrder([
                    'order_ref'      => $orderRef,
                    'user_id'        => $userId,
                    'item_type'      => 'course',
                    'item_id'        => $courseId,
                    'total'          => (float)($result['total'] ?? 0.0),
                    'currency'       => $gatewayId === 'clickbank' ? 'USD' : 'BRL',
                    'gateway'        => $gatewayId,
                    'payment_method' => 'external_checkout',
                    'status'         => 'pending',
                    'transaction_id' => $txId,
                ]);
            } catch (Throwable $e) {
                // Em caso de corrida/duplicidade na constraint UNIQUE(order_ref), trata de forma idempotente ou solicita retry
                try {
                    $existingAfterConflict = $this->orderRepo->findByOrderRef($orderRef);
                    if ($existingAfterConflict !== null && ($existingAfterConflict['status'] ?? '') === 'paid') {
                        return new JsonResponse(['received' => true, 'action' => 'duplicate'], 200);
                    }
                    if ($existingAfterConflict !== null && ($existingAfterConflict['status'] ?? '') === 'pending') {
                        return new JsonResponse(['received' => false, 'error' => 'concurrent_order_in_progress'], 500);
                    }
                } catch (Throwable $ignored) {
                }

                error_log('[commerce] Falha ao gravar pedido de afiliado: ' . $e->getMessage());
                return new JsonResponse(['received' => false, 'error' => 'order_persist_failed'], 500);
            }

            if ($this->dispatcher !== null) {
                try {
                    $this->dispatcher->dispatch('payment.approved', [
                        'user_id'   => $userId,
                        'course_id' => $courseId,
                        'order_ref' => $orderRef,
                    ]);
                } catch (Throwable $e) {
                    error_log('[commerce] Falha ao disparar fulfillment de afiliado order=' . $orderRef . ': ' . $e->getMessage());
                    return new JsonResponse(['received' => false, 'error' => 'fulfillment_dispatch_failed'], 500);
                }
            }

            if ($createdOrderId > 0) {
                try {
                    $updated = $this->orderRepo->updateStatus($createdOrderId, 'paid', $txId);
                } catch (Throwable $e) {
                    error_log('[commerce] Erro ao concluir status pago de pedido afiliado order=' . $orderRef . ': ' . $e->getMessage());
                    return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
                }
                if (!$updated) {
                    error_log('[commerce] Falha ao concluir status pago de pedido afiliado order=' . $orderRef);
                    return new JsonResponse(['received' => false, 'error' => 'order_update_failed'], 500);
                }
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

        return new JsonResponse(['received' => true, 'action' => 'processed'], 200);
    }

    /**
     * Resolve usuário existente por e-mail ou provisiona novo aluno com proteção contra duplicidade e falhas silenciosas.
     *
     * @throws InvalidArgumentException Quando o e-mail informado é inválido (erro definitivo do payload).
     * @throws RuntimeException Quando há falha de infraestrutura/banco no provisionamento (erro temporário elegível para retry).
     */
    private function resolveOrCreateUser(string $email, string $name): int
    {
        if ($this->pdo === null) {
            throw new RuntimeException('Conexão PDO indisponível para provisionamento de usuário.');
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail do comprador ausente ou inválido.');
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $existingId = $stmt->fetchColumn();

            if ($existingId) {
                return (int)$existingId;
            }

            $randomPass = bin2hex(random_bytes(8));
            $hash = password_hash($randomPass, PASSWORD_DEFAULT);
            $cleanName = trim($name) !== '' ? trim($name) : 'Aluno';
            $baseUsername = preg_replace('/[^a-z0-9_.]/', '', strtolower(str_replace(' ', '', $cleanName))) ?: 'aluno';
            $cleanUsername = substr($baseUsername, 0, 24) . '_' . substr(bin2hex(random_bytes(3)), 0, 6);

            try {
                $insertStmt = $this->pdo->prepare("
                    INSERT INTO users (name, username, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$cleanName, $cleanUsername, $email, $hash]);
            } catch (Throwable $e) {
                // Reconsulta por e-mail primeiro para capturar condição de corrida antes de tentar schema legado sem username
                $stmtRace = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtRace->execute([$email]);
                $raceId = $stmtRace->fetchColumn();
                if ($raceId) {
                    return (int)$raceId;
                }

                $insertStmt = $this->pdo->prepare("
                    INSERT INTO users (name, email, password, role, created_at, updated_at)
                    VALUES (?, ?, ?, 'user', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$cleanName, $email, $hash]);
            }

            $createdId = (int)$this->pdo->lastInsertId();
            if ($createdId <= 0) {
                throw new RuntimeException('Banco não retornou ID do usuário recém-criado.');
            }

            return $createdId;
        } catch (Throwable $e) {
            // Última verificação de idempotência para e-mail concorrente antes de sinalizar erro 500
            try {
                $stmtRace = $this->pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmtRace->execute([$email]);
                $raceId = $stmtRace->fetchColumn();
                if ($raceId) {
                    return (int)$raceId;
                }
            } catch (Throwable $ignored) {
            }

            throw new RuntimeException('Falha ao resolver ou criar usuário: ' . $e->getMessage(), 0, $e);
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
