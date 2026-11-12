<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Services\DisputeAttachmentService;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use Throwable;

/**
 * Controlador público para cadastro, login, pedidos, rastreio integrado e abertura de reclamações/dúvidas/observações de clientes.
 * Isolado da tabela `users` administrativa para impedir qualquer escalonamento de privilégio.
 */
class CustomerAccountController
{
    public function __construct(
        private readonly DropshipCustomerRepositoryInterface $customerRepo,
        private readonly DropshipOrderRepositoryInterface $orderRepo,
        private readonly DropshipDisputeRepositoryInterface $disputeRepo,
        private readonly SessionManagerInterface $session,
        private readonly DisputeAttachmentService $attachmentService,
        private readonly OrderSlaProgressService $slaService,
        private readonly ?ThemeManagerInterface $themeManager = null,
        private readonly ?AliExpressApiClientInterface $apiClient = null,
        private readonly ?DropshipCatalogRepositoryInterface $catalogRepo = null
    ) {
    }

    public function showRegister(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        $redirect = $this->sanitizeRedirect((string)$request->input('redirect', ''));
        if ($this->isLoggedIn()) {
            return Response::redirect($redirect !== '' ? $redirect : ($baseUrl . '/minha-conta'));
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $flash = $this->session->getFlash();
        $prefillEmail = trim((string)$request->input('email', ''));
        $prefillName = trim((string)$request->input('name', ''));

        ob_start();
        include dirname(__DIR__) . '/views/front/customer_register.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Criar Conta - RachidD.com');
    }

    public function register(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        $redirect = $this->sanitizeRedirect((string)$request->input('redirect', ''));
        $targetUrl = $redirect !== '' ? $redirect : ($baseUrl . '/minha-conta');
        $redirectQuery = $redirect !== '' ? ('?redirect=' . rawurlencode($redirect)) : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Atualize a página e tente novamente.');
            return Response::redirect($baseUrl . '/cadastro' . $redirectQuery);
        }

        $name = trim((string)$request->input('name', ''));
        $email = strtolower(trim((string)$request->input('email', '')));
        $cpf = preg_replace('/[^0-9.-]/', '', trim((string)$request->input('cpf', ''))) ?? '';
        $phone = trim((string)$request->input('phone', ''));
        $password = (string)$request->input('password', '');
        $confirm = (string)$request->input('password_confirm', '');

        if (mb_strlen($name) < 2) {
            $this->session->setFlash('error', 'Informe seu nome completo.');
            return Response::redirect($baseUrl . '/cadastro' . $redirectQuery);
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->session->setFlash('error', 'Informe um endereço de e-mail válido.');
            return Response::redirect($baseUrl . '/cadastro' . $redirectQuery);
        }
        if (strlen($password) < 6) {
            $this->session->setFlash('error', 'A senha deve conter no mínimo 6 caracteres.');
            return Response::redirect($baseUrl . '/cadastro' . $redirectQuery);
        }
        if ($confirm !== '' && $password !== $confirm) {
            $this->session->setFlash('error', 'A confirmação de senha não confere.');
            return Response::redirect($baseUrl . '/cadastro' . $redirectQuery);
        }

        if ($this->customerRepo->findByEmail($email) !== null) {
            $this->session->setFlash('error', 'Este e-mail já possui cadastro. Faça login para acessar seus pedidos.');
            $sep = $redirectQuery !== '' ? '&' : '?';
            return Response::redirect($baseUrl . '/entrar' . $redirectQuery . $sep . 'email=' . rawurlencode($email));
        }

        $customerId = $this->customerRepo->create([
            'name'          => $name,
            'email'         => $email,
            'cpf'           => $cpf,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status'        => 'active',
        ]);

        $this->authenticateCustomerSession([
            'id'    => $customerId,
            'name'  => $name,
            'email' => $email,
            'cpf'   => $cpf,
        ]);

        $this->session->setFlash('success', 'Cadastro realizado com sucesso! Seus pedidos e garantias estão disponíveis aqui.');
        return Response::redirect($targetUrl);
    }

    public function showLogin(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        $redirect = $this->sanitizeRedirect((string)$request->input('redirect', ''));
        if ($this->isLoggedIn()) {
            return Response::redirect($redirect !== '' ? $redirect : ($baseUrl . '/minha-conta'));
        }

        $csrfToken = (string)$this->session->getCsrfToken();
        $flash = $this->session->getFlash();
        $prefillEmail = trim((string)$request->input('email', ''));
        $prefillName = trim((string)$request->input('name', ''));

        ob_start();
        include dirname(__DIR__) . '/views/front/customer_login.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Entrar na Minha Conta - RachidD.com');
    }

    public function login(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        $redirect = $this->sanitizeRedirect((string)$request->input('redirect', ''));
        $targetUrl = $redirect !== '' ? $redirect : ($baseUrl . '/minha-conta');
        $redirectQuery = $redirect !== '' ? ('?redirect=' . rawurlencode($redirect)) : '';

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return Response::redirect($baseUrl . '/entrar' . $redirectQuery);
        }

        $email = strtolower(trim((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');

        if ($email === '' || $password === '') {
            $this->session->setFlash('error', 'Informe e-mail e senha para entrar.');
            return Response::redirect($baseUrl . '/entrar' . $redirectQuery);
        }

        $customer = $this->customerRepo->findByEmail($email);
        if ($customer === null || !password_verify($password, (string)($customer['password_hash'] ?? ''))) {
            $this->session->setFlash('error', 'E-mail ou senha inválidos.');
            $sep = $redirectQuery !== '' ? '&' : '?';
            return Response::redirect($baseUrl . '/entrar' . $redirectQuery . $sep . 'email=' . rawurlencode($email));
        }

        if (($customer['status'] ?? 'active') !== 'active') {
            $this->session->setFlash('error', 'Conta temporariamente suspensa. Entre em contato com o suporte.');
            return Response::redirect($baseUrl . '/entrar' . $redirectQuery);
        }

        $this->authenticateCustomerSession($customer);
        $this->session->setFlash('success', 'Bem-vindo de volta!');

        return Response::redirect($targetUrl);
    }

    private function sanitizeRedirect(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || !str_starts_with($raw, '/') || str_starts_with($raw, '//') || str_contains($raw, '://') || str_contains($raw, "\r") || str_contains($raw, "\n")) {
            return '';
        }
        return $raw;
    }

    public function logout(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        $this->session->remove('customer_id');
        $this->session->remove('customer_name');
        $this->session->remove('customer_email');
        $this->session->remove('customer_cpf');

        $this->session->setFlash('success', 'Você saiu da sua conta com segurança.');
        return Response::redirect($baseUrl . '/');
    }

    public function account(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        if (!$this->isLoggedIn()) {
            $this->session->setFlash('error', 'Faça login ou crie sua conta para visualizar pedidos e abrir reclamações.');
            return Response::redirect($baseUrl . '/entrar');
        }

        $customerId = (int)$this->session->get('customer_id', 0);
        $customerEmail = (string)$this->session->get('customer_email', '');
        $customer = $this->customerRepo->findById($customerId) ?? [
            'id'    => $customerId,
            'name'  => (string)$this->session->get('customer_name', 'Cliente'),
            'email' => $customerEmail,
            'cpf'   => (string)$this->session->get('customer_cpf', ''),
        ];

        $orders = $this->orderRepo->findByCustomerEmail($customerEmail, 50);
        $orderRefs = array_values(array_filter(array_map(static fn (array $o): string => (string)($o['order_ref'] ?? ''), $orders)));
        $disputesByOrder = $this->disputeRepo->mapByOrderRefs($orderRefs);

        foreach ($orders as &$order) {
            $ref = (string)($order['order_ref'] ?? '');
            $orderDisputes = $disputesByOrder[$ref] ?? [];
            $order['disputes'] = $orderDisputes;
            $order['sla'] = $this->slaService->calculate($order, $orderDisputes);
        }
        unset($order);

        $disputes = $this->disputeRepo->findByCustomerEmail($customerEmail);
        $csrfToken = (string)$this->session->getCsrfToken();
        $flash = $this->session->getFlash();
        $selectedOrderRef = trim((string)$request->input('pedido', ''));
        $selectedProductId = (int)$request->input('produto', 0);
        $catalogProducts = $this->catalogRepo !== null
            ? $this->catalogRepo->list(['status' => 'published', 'sort' => 'updated_desc'], 100, 0)
            : [];

        // Rastreio integrado dentro da Área do Cliente (/minha-conta?rastrear=...)
        $trackQuery = trim((string)$request->input('rastrear', ''));
        $trackedOrder = null;
        $trackedCodeOnly = '';
        $sdkEvents = [];
        $sdkOfficialUrl = '';

        if ($trackQuery !== '') {
            foreach ($orders as $ord) {
                $oRef = (string)($ord['order_ref'] ?? '');
                $tCode = (string)($ord['tracking_code'] ?? '');
                if (strcasecmp($oRef, $trackQuery) === 0 || ($tCode !== '' && strcasecmp($tCode, $trackQuery) === 0)) {
                    $trackedOrder = $ord;
                    break;
                }
            }
            if ($trackedOrder === null && preg_match('/^[A-Z0-9\-]{6,32}$/i', $trackQuery)) {
                $trackedCodeOnly = strtoupper($trackQuery);
            }
        } elseif (!empty($orders)) {
            $trackedOrder = $orders[0];
        }

        if ($trackedOrder !== null && $this->apiClient !== null) {
            $tCode = trim((string)($trackedOrder['tracking_code'] ?? ''));
            $aliOrderId = trim((string)($trackedOrder['aliexpress_order_id'] ?? ''));
            if ($tCode !== '' && $aliOrderId !== '') {
                try {
                    $sdkRes = $this->apiClient->queryTrackingInfo(
                        logisticsNo: $tCode,
                        origin: 'ESCROW',
                        outRef: $aliOrderId,
                        serviceName: 'CAINIAO_STANDARD',
                        toArea: 'BR'
                    );
                    if (!empty($sdkRes['details']) && is_array($sdkRes['details'])) {
                        $sdkEvents = $sdkRes['details'];
                    }
                    if (!empty($sdkRes['official_website'])) {
                        $sdkOfficialUrl = (string)$sdkRes['official_website'];
                    }
                } catch (Throwable $e) {
                }
            }
        }

        ob_start();
        include dirname(__DIR__) . '/views/front/customer_account.php';
        $content = (string)ob_get_clean();

        return $this->wrapWithThemeOrRaw($content, 'Minha Conta, Rastreio e Atendimento - RachidD.com');
    }

    public function submitComplaint(Request $request): ResponseInterface
    {
        $baseUrl = $this->baseUrl();
        if (!$this->isLoggedIn()) {
            $this->session->setFlash('error', 'É necessário estar conectado para abrir uma reclamação ou mensagem.');
            return Response::redirect($baseUrl . '/entrar');
        }

        if (!$this->verifyCsrf($request)) {
            $this->session->setFlash('error', 'Token de segurança expirado. Tente novamente.');
            return Response::redirect($baseUrl . '/minha-conta');
        }

        $customerId = (int)$this->session->get('customer_id', 0);
        $customerEmail = strtolower(trim((string)$this->session->get('customer_email', '')));
        $orderRef = trim((string)$request->input('order_ref', ''));
        $manualOrderRef = trim((string)$request->input('manual_order_ref', ''));
        $catalogProductId = (int)$request->input('catalog_product_id', 0);
        $reason = trim((string)$request->input('reason', 'produto_danificado'));
        $description = trim((string)$request->input('description', ''));

        $allowedReasons = [
            'produto_danificado',
            'entrega_atrasada',
            'item_errado',
            'arrependimento',
            'duvida_entrega',
            'duvida_produto',
            'observacao_pedido',
            'outro',
        ];
        if (!in_array($reason, $allowedReasons, true)) {
            $reason = 'outro';
        }

        if (mb_strlen($description) < 10) {
            $this->session->setFlash('error', 'Descreva sua dúvida, observação ou reclamação com pelo menos 10 caracteres.');
            return Response::redirect($baseUrl . '/minha-conta?pedido=' . rawurlencode($orderRef ?: 'GERAL'));
        }

        $isGeneralInquiry = ($orderRef === '' || strtoupper($orderRef) === 'GERAL');
        $order = null;

        if (!$isGeneralInquiry) {
            $order = $this->orderRepo->findByOrderRef($orderRef);
            if ($order === null || strtolower(trim((string)($order['customer_email'] ?? ''))) !== $customerEmail) {
                $this->session->setFlash('error', 'Você ainda não possui o pedido selecionado vinculado à sua conta. Você pode deixar como "Sem pedido na lista" ou informar o código/produto opcionalmente.');
                return Response::redirect($baseUrl . '/minha-conta');
            }
        } elseif ($manualOrderRef !== '') {
            $cleanManualRef = preg_replace('/[^A-Za-z0-9\-_#\/]/', '', $manualOrderRef) ?: '';
            $cleanManualRef = substr(strtoupper($cleanManualRef), 0, 60);
            if ($cleanManualRef !== '') {
                $orderRef = $cleanManualRef;
                $order = $this->orderRepo->findByOrderRef($cleanManualRef);
            } else {
                $orderRef = 'GERAL';
            }
        } else {
            $orderRef = 'GERAL';
        }

        $linkedProduct = ($catalogProductId > 0 && $this->catalogRepo !== null)
            ? $this->catalogRepo->findById($catalogProductId)
            : null;

        $contextPrefixParts = [];
        if ($manualOrderRef !== '' && $isGeneralInquiry) {
            $contextPrefixParts[] = 'Ref. Informada: ' . $manualOrderRef;
        }
        if ($linkedProduct !== null) {
            $contextPrefixParts[] = 'Produto: #' . $linkedProduct->id . ' - ' . $linkedProduct->title;
            if ($orderRef === 'GERAL') {
                $orderRef = 'PROD-' . $linkedProduct->id;
            }
        } elseif (!empty($order['product_title'])) {
            $contextPrefixParts[] = 'Produto do Pedido: ' . (string)$order['product_title'];
        }

        if (!empty($contextPrefixParts)) {
            $description = '[' . implode(' | ', $contextPrefixParts) . "]\n" . $description;
        }

        if (mb_strlen($description) > 3000) {
            $description = mb_substr($description, 0, 3000);
        }

        $fileInput = $request->files['evidences'] ?? null;
        $uploadResult = $this->attachmentService->storeUploadedFiles(is_array($fileInput) ? $fileInput : null);
        if (!empty($uploadResult['errors'])) {
            $this->session->setFlash('error', implode(' ', $uploadResult['errors']));
            return Response::redirect($baseUrl . '/minha-conta?pedido=' . rawurlencode($orderRef));
        }

        $sellerOrigin = strtoupper(trim((string)($order['seller_origin'] ?? '')));
        $sellerName = $order['seller_name'] ?? null;
        $sellerEmail = $order['seller_email'] ?? null;

        if ($linkedProduct !== null) {
            $sellerInfo = $linkedProduct->getSellerInfo();
            if ($sellerOrigin === '') {
                $isNational = !empty($linkedProduct->attributes['origem_brasil'])
                    || (($linkedProduct->attributes['ship_from'] ?? '') === 'BR');
                $sellerOrigin = $isNational ? 'BR' : 'CN';
            }
            if ($sellerName === null || $sellerName === '') {
                $sellerName = $sellerInfo['store_name'] . ' (' . $sellerInfo['seller_id'] . ')';
            }
        }

        if ($sellerOrigin !== 'BR' && $sellerOrigin !== 'CN') {
            $sellerOrigin = 'BR';
        }

        $this->disputeRepo->create([
            'order_ref'        => $orderRef,
            'customer_id'      => $customerId > 0 ? $customerId : null,
            'customer_email'   => $customerEmail,
            'reason'           => $reason,
            'description'      => $description,
            'attachments_json' => $uploadResult['attachments'],
            'seller_origin'    => $sellerOrigin,
            'seller_name'      => $sellerName,
            'seller_email'     => $sellerEmail,
            'mediation_status' => 'open',
        ]);

        $this->session->setFlash('success', 'Sua solicitação foi registrada com sucesso! Nossa equipe analisará e responderá em breve.');
        return Response::redirect($baseUrl . '/minha-conta');
    }

    public function serveAttachment(Request $request, string $id, string $file): ResponseInterface
    {
        $dispute = $this->disputeRepo->findById((int)$id);
        if ($dispute === null) {
            return new Response('Arquivo não encontrado.', 404);
        }

        $role = (string)$this->session->get('user_role', '');
        $isAdmin = in_array($role, ['admin', 'manager'], true);
        $sessionCustomerEmail = strtolower(trim((string)$this->session->get('customer_email', '')));
        $isOwner = $sessionCustomerEmail !== '' && $sessionCustomerEmail === strtolower(trim((string)($dispute['customer_email'] ?? '')));

        if (!$isAdmin && !$isOwner) {
            return new Response('Acesso negado ao anexo solicitado.', 403);
        }

        $matchedMeta = null;
        $attachments = is_array($dispute['attachments'] ?? null) ? $dispute['attachments'] : [];
        foreach ($attachments as $att) {
            if (is_array($att) && ($att['filename'] ?? '') === $file) {
                $matchedMeta = $att;
                break;
            }
        }
        if ($matchedMeta === null) {
            return new Response('Anexo não pertence a esta solicitação.', 404);
        }

        $safePath = $this->attachmentService->resolveSafePath($file);
        if ($safePath === null) {
            return new Response('Arquivo indisponível no armazenamento.', 404);
        }

        $bytes = @file_get_contents($safePath);
        if ($bytes === false) {
            return new Response('Erro ao ler anexo.', 500);
        }

        $mime = (string)($matchedMeta['mime'] ?? 'application/octet-stream');
        $downloadName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)($matchedMeta['original_name'] ?? $file)) ?: $file;

        return new Response($bytes, 200, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="' . $downloadName . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=3600',
        ]);
    }

    /**
     * @param array<string, mixed> $customer
     */
    private function authenticateCustomerSession(array $customer): void
    {
        $this->session->regenerate();
        $this->session->set('customer_id', (int)($customer['id'] ?? 0));
        $this->session->set('customer_name', (string)($customer['name'] ?? 'Cliente'));
        $this->session->set('customer_email', strtolower(trim((string)($customer['email'] ?? ''))));
        $this->session->set('customer_cpf', (string)($customer['cpf'] ?? ''));
    }

    private function isLoggedIn(): bool
    {
        return (int)$this->session->get('customer_id', 0) > 0
            && trim((string)$this->session->get('customer_email', '')) !== '';
    }

    private function verifyCsrf(Request $request): bool
    {
        $token = (string)$request->input('csrf_token', (string)$request->input('_csrf', (string)($request->server['HTTP_X_CSRF_TOKEN'] ?? '')));
        $expected = (string)$this->session->getCsrfToken();

        return $token !== '' && hash_equals($expected, $token);
    }

    private function baseUrl(): string
    {
        return defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
    }

    private function wrapWithThemeOrRaw(string $content, string $title): ResponseInterface
    {
        if ($this->themeManager !== null) {
            try {
                $wrapped = $this->themeManager->render('page', [
                    'title'   => $title,
                    'content' => $content,
                ]);
                return new Response($wrapped);
            } catch (Throwable $e) {
                // Fallback direto
            }
        }

        return new Response($content ?: '');
    }
}
