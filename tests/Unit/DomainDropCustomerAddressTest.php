<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\commerce\Contracts\CommerceSettingsInterface;
use DomainSystem\Plugins\commerce\Contracts\OrderRepositoryInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayInterface;
use DomainSystem\Plugins\commerce\Contracts\PaymentGatewayRegistryInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemInterface;
use DomainSystem\Plugins\commerce\Contracts\PurchasableItemResolverInterface;
use DomainSystem\Plugins\commerce\Controllers\CheckoutController;
use DomainSystem\Plugins\commerce\DTO\PaymentResult;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCustomerRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipOrderRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Controllers\CustomerAccountController;
use DomainSystem\Plugins\domain_drop\Repositories\PdoDropshipCustomerRepository;
use DomainSystem\Plugins\domain_drop\Services\DisputeAttachmentService;
use DomainSystem\Plugins\domain_drop\Services\OrderSlaProgressService;
use PDO;
use PHPUnit\Framework\TestCase;

class DomainDropCustomerAddressTest extends TestCase
{
    private function createInMemoryPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS domain_drop_customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                cpf TEXT NULL,
                phone TEXT NULL,
                password_hash TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                shipping_zip TEXT NULL,
                shipping_address TEXT NULL,
                shipping_number TEXT NULL,
                shipping_complement TEXT NULL,
                shipping_neighborhood TEXT NULL,
                shipping_city TEXT NULL,
                shipping_state TEXT NULL,
                default_shipping_address TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS domain_drop_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_ref TEXT NOT NULL UNIQUE,
                customer_name TEXT NOT NULL,
                customer_email TEXT NOT NULL,
                customer_document TEXT NULL,
                shipping_address TEXT NULL,
                aliexpress_order_id TEXT NULL,
                tracking_code TEXT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                total_paid_brl REAL NOT NULL DEFAULT 0.00,
                seller_origin TEXT NOT NULL DEFAULT 'BR',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS domain_drop_disputes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_ref TEXT NOT NULL,
                customer_id INTEGER NULL,
                customer_email TEXT NOT NULL,
                reason TEXT NOT NULL DEFAULT 'produto_danificado',
                description TEXT NOT NULL,
                attachments_json TEXT NULL,
                seller_origin TEXT NOT NULL DEFAULT 'CN',
                seller_name TEXT NULL,
                seller_email TEXT NULL,
                seller_language TEXT NULL,
                translated_message TEXT NULL,
                mediation_status TEXT NOT NULL DEFAULT 'open',
                mediation_channel TEXT NULL,
                mediation_sent_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS domain_drop_product_demand (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id TEXT NOT NULL,
                views_count INTEGER NOT NULL DEFAULT 0,
                clicks_count INTEGER NOT NULL DEFAULT 0,
                cart_add_count INTEGER NOT NULL DEFAULT 0,
                conversions_count INTEGER NOT NULL DEFAULT 0,
                estimated_demand_score REAL NOT NULL DEFAULT 0.0,
                priority_tier TEXT NOT NULL DEFAULT 'frio',
                trend_momentum TEXT NOT NULL DEFAULT 'stable',
                last_calculated_at TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS domain_drop_demand_activity_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id TEXT NOT NULL,
                action_type TEXT NOT NULL,
                session_id TEXT NULL,
                customer_id INTEGER NULL,
                customer_name TEXT NULL,
                customer_email TEXT NULL,
                user_type TEXT NOT NULL DEFAULT 'visitante',
                client_ip TEXT NULL,
                metadata_json TEXT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
        ");

        return $pdo;
    }

    public function testCustomerRepositorySavesAndHydratesCompleteShippingAddress(): void
    {
        $pdo = $this->createInMemoryPdo();
        $repo = new PdoDropshipCustomerRepository($pdo);

        $customerId = $repo->create([
            'name'          => 'Carlos Eduardo',
            'email'         => 'carlos@cliente.com',
            'phone'         => '(11) 98888-7777',
            'cpf'           => '123.456.789-00',
            'password_hash' => password_hash('123456', PASSWORD_DEFAULT),
        ]);

        $this->assertGreaterThan(0, $customerId);

        $success = $repo->updateAddress($customerId, [
            'shipping_zip'          => '01310-100',
            'shipping_address'      => 'Avenida Paulista',
            'shipping_number'       => '1578',
            'shipping_complement'   => 'Apto 82',
            'shipping_neighborhood' => 'Bela Vista',
            'shipping_city'         => 'São Paulo',
            'shipping_state'        => 'SP',
            'phone'                 => '(11) 98888-7777',
            'cpf'                   => '123.456.789-00',
        ]);

        $this->assertTrue($success);

        $customer = $repo->findById($customerId);
        $this->assertNotNull($customer);
        $this->assertSame('01310-100', $customer['shipping_zip']);
        $this->assertSame('Avenida Paulista', $customer['shipping_address']);
        $this->assertSame('1578', $customer['shipping_number']);
        $this->assertSame('Apto 82', $customer['shipping_complement']);
        $this->assertSame('Bela Vista', $customer['shipping_neighborhood']);
        $this->assertSame('São Paulo', $customer['shipping_city']);
        $this->assertSame('SP', $customer['shipping_state']);
        $this->assertIsArray($customer['parsed_shipping_address']);
        $this->assertSame('Avenida Paulista', $customer['parsed_shipping_address']['street']);
        $this->assertSame('1578', $customer['parsed_shipping_address']['number']);

        // Teste de salvamento por e-mail
        $savedByEmail = $repo->saveAddressByEmail('carlos@cliente.com', [
            'shipping_zip'     => '04538-133',
            'shipping_address' => 'Rua Funchal',
            'shipping_number'  => '200',
            'shipping_city'    => 'São Paulo',
            'shipping_state'   => 'SP',
        ]);
        $this->assertTrue($savedByEmail);

        $updatedCust = $repo->findByEmail('carlos@cliente.com');
        $this->assertSame('04538-133', $updatedCust['shipping_zip']);
        $this->assertSame('Rua Funchal', $updatedCust['shipping_address']);
    }

    public function testCustomerAccountControllerUpdatesAddressSuccessfully(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, $default = null) {
            return match ($key) {
                'customer_id'    => 42,
                'customer_email' => 'maria@cliente.com',
                default          => $default,
            };
        });
        $session->method('getCsrfToken')->willReturn('token_valido_123');

        $customerRepo = $this->createMock(DropshipCustomerRepositoryInterface::class);
        $customerRepo->expects($this->once())
            ->method('updateAddress')
            ->with(42, $this->callback(function (array $data) {
                return ($data['shipping_zip'] ?? '') === '12242-000'
                    && ($data['shipping_address'] ?? '') === 'Avenida São João'
                    && ($data['shipping_number'] ?? '') === '500';
            }))
            ->willReturn(true);

        $orderRepo = $this->createMock(DropshipOrderRepositoryInterface::class);
        $disputeRepo = $this->createMock(DropshipDisputeRepositoryInterface::class);
        $attachmentService = $this->createMock(DisputeAttachmentService::class);
        $slaService = $this->createMock(OrderSlaProgressService::class);

        $controller = new CustomerAccountController(
            customerRepo: $customerRepo,
            orderRepo: $orderRepo,
            disputeRepo: $disputeRepo,
            session: $session,
            attachmentService: $attachmentService,
            slaService: $slaService
        );

        $request = new Request([], [
            'csrf_token'            => 'token_valido_123',
            'shipping_zip'          => '12242-000',
            'shipping_address'      => 'Avenida São João',
            'shipping_number'       => '500',
            'shipping_complement'   => 'Bloco 1',
            'shipping_neighborhood' => 'Jardim Esplanada',
            'shipping_city'         => 'São José dos Campos',
            'shipping_state'        => 'SP',
            'phone'                 => '(12) 99999-8888',
            'cpf'                   => '987.654.321-99',
            'name'                  => 'Maria Santos',
        ]);

        $response = $controller->updateAddress($request);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/minha-conta#meu-endereco', (string)$response->getHeader('Location'));
    }

    public function testCheckoutControllerPrefillsCustomerSavedAddressAndSavesOnPurchase(): void
    {
        $session = $this->createMock(SessionManagerInterface::class);
        $session->method('get')->willReturnCallback(function (string $key, $default = null) {
            return match ($key) {
                'customer_id'    => 10,
                'customer_email' => 'joao@cliente.com',
                'customer_name'  => 'João Pedro',
                default          => $default,
            };
        });
        $session->method('getCsrfToken')->willReturn('token_checkout_999');

        $orderRepo = $this->createMock(OrderRepositoryInterface::class);
        $orderRepo->method('createOrder')->willReturn(101);

        $gateway = $this->createMock(PaymentGatewayInterface::class);
        $gateway->method('getId')->willReturn('mercadopago');
        $gateway->method('isConfigured')->willReturn(true);
        $gateway->method('processPayment')->willReturn(PaymentResult::approved(
            orderRef: 'ORD_TEST_123',
            paymentMethod: 'pix',
            transactionId: 'MP_TX_APPROVED_123'
        ));

        $gatewayRegistry = $this->createMock(PaymentGatewayRegistryInterface::class);
        $gatewayRegistry->method('getAll')->willReturn([$gateway]);
        $gatewayRegistry->method('get')->with('mercadopago')->willReturn($gateway);

        $itemResolver = $this->createMock(PurchasableItemResolverInterface::class);
        $purchasable = $this->createMock(PurchasableItemInterface::class);
        $purchasable->method('isAvailable')->willReturn(true);
        $purchasable->method('getId')->willReturn(55);
        $purchasable->method('getTitle')->willReturn('Fone Bluetooth Pro');
        $purchasable->method('getPrice')->willReturn(99.90);
        $purchasable->method('getItemType')->willReturn('dropship_product');
        $purchasable->method('getDescription')->willReturn('Fone sem fio');
        $purchasable->method('getThumbnailUrl')->willReturn('https://img.com/fone.png');
        $itemResolver->method('resolve')->willReturn($purchasable);

        $settings = $this->createMock(CommerceSettingsInterface::class);
        $settings->method('get')->willReturnCallback(fn ($k, $d) => $d);

        $savedAddressMock = [
            'id'               => 10,
            'name'             => 'João Pedro',
            'email'            => 'joao@cliente.com',
            'phone'            => '(11) 97777-6666',
            'cpf'              => '111.222.333-44',
            'shipping_zip'     => '01310-200',
            'shipping_address' => 'Avenida Paulista',
            'shipping_number'  => '2000',
            'shipping_city'    => 'São Paulo',
            'shipping_state'   => 'SP',
        ];

        $addressSavedToCustomer = null;
        $customerFinder = static fn (int $id, string $email) => $savedAddressMock;
        $customerSaver = static function (int $id, string $email, array $data) use (&$addressSavedToCustomer): bool {
            $addressSavedToCustomer = $data;
            return true;
        };

        $controller = new CheckoutController(
            session: $session,
            orderRepo: $orderRepo,
            gatewayRegistry: $gatewayRegistry,
            itemResolver: $itemResolver,
            settings: $settings,
            dropshipCustomerFinder: $customerFinder,
            dropshipCustomerAddressSaver: $customerSaver
        );

        // 1. Testa exibição do checkout pré-carregando o endereço
        $showReq = new Request([], [], ['REQUEST_URI' => '/checkout/produto/55']);
        $showRes = $controller->showCheckout($showReq, 55);
        $body = (string)$showRes->getContent();
        $this->assertStringContainsString('01310-200', $body);
        $this->assertStringContainsString('Avenida Paulista', $body);
        $this->assertStringContainsString('Endereço Salvo na Conta', $body);

        // 2. Testa processamento de pagamento salvando/atualizando endereço no perfil
        $processReq = new Request([], [
            'csrf_token'              => 'token_checkout_999',
            'item_id'                 => 55,
            'item_type'               => 'dropship_product',
            'gateway'                 => 'mercadopago',
            'payment_method'          => 'pix',
            'payer_name'              => 'João Pedro',
            'payer_email'             => 'joao@cliente.com',
            'payer_phone'             => '(11) 97777-6666',
            'payer_cpf'               => '111.222.333-44',
            'shipping_zip'            => '01310-200',
            'shipping_address'        => 'Avenida Paulista',
            'shipping_number'         => '2000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'São Paulo',
            'shipping_state'          => 'SP',
            'save_address_to_account' => true,
        ]);

        $processRes = $controller->process($processReq);
        $this->assertSame(200, $processRes->getStatusCode());
        $this->assertNotNull($addressSavedToCustomer);
        $this->assertSame('01310-200', $addressSavedToCustomer['shipping_zip']);
        $this->assertSame('Avenida Paulista', $addressSavedToCustomer['shipping_address']);
        $this->assertSame('2000', $addressSavedToCustomer['shipping_number']);
    }
}
