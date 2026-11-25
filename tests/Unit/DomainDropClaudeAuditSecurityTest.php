<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Contracts\ProductMiningServiceInterface;
use DomainSystem\Plugins\domain_drop\Controllers\DropshipAdminController;
use DomainSystem\Plugins\domain_drop\Exceptions\AliExpressApiException;
use DomainSystem\Plugins\domain_drop\Services\AliExpressApiClient;
use DomainSystem\Plugins\domain_drop\Services\AtomicConfigStorage;
use DomainSystem\Plugins\domain_drop\Services\ProductMiningService;
use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DomainDropClaudeAuditSecurityTest extends TestCase
{
    private string $tempDir;
    private string $tempConfigFile;

    private function createSessionMock(array $state = []): SessionManagerInterface
    {
        $mock = $this->createMock(SessionManagerInterface::class);
        $flash = null;

        $mock->method('get')->willReturnCallback(function (string $key, mixed $default = null) use (&$state) {
            return $state[$key] ?? $default;
        });

        $mock->method('set')->willReturnCallback(function (string $key, mixed $value) use (&$state) {
            $state[$key] = $value;
        });

        $mock->method('has')->willReturnCallback(function (string $key) use (&$state) {
            return isset($state[$key]);
        });

        $mock->method('remove')->willReturnCallback(function (string $key) use (&$state) {
            unset($state[$key]);
        });

        $mock->method('getCsrfToken')->willReturn('valid_csrf_token');
        $mock->method('validateCsrfToken')->willReturn(true);

        $mock->method('setFlash')->willReturnCallback(function (string $type, string $msg) use (&$flash) {
            $flash = ['type' => $type, 'msg' => $msg];
        });

        $mock->method('getFlash')->willReturnCallback(function () use (&$flash) {
            return $flash;
        });

        return $mock;
    }

    private function createController(SessionManagerInterface $session): DropshipAdminController
    {
        return new DropshipAdminController(
            catalogRepo: $this->createMock(DropshipCatalogRepositoryInterface::class),
            miningService: $this->createMock(ProductMiningServiceInterface::class),
            currencyConverter: $this->createMock(CurrencyConverterInterface::class),
            session: $session,
            basePath: $this->tempDir
        );
    }

    protected function setUp(): void
    {
        if (!is_dir(dirname(__DIR__, 2) . '/src/Plugins/domain_drop')) { $this->markTestSkipped('Plugin agregado externo [domain_drop] não está presente no checkout.'); return; }
        $this->tempDir = sys_get_temp_dir() . '/domain_drop_test_' . bin2hex(random_bytes(6));
        mkdir($this->tempDir . '/config/plugins', 0777, true);
        $this->tempConfigFile = $this->tempDir . '/config/plugins/domain_drop.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempConfigFile)) {
            @unlink($this->tempConfigFile);
        }
        $lockFile = $this->tempConfigFile . '.lock';
        if (file_exists($lockFile)) {
            @unlink($lockFile);
        }
        if (is_dir($this->tempDir . '/config/plugins')) {
            @rmdir($this->tempDir . '/config/plugins');
        }
        if (is_dir($this->tempDir . '/config')) {
            @rmdir($this->tempDir . '/config');
        }
        if (is_dir($this->tempDir)) {
            @rmdir($this->tempDir);
        }
    }

    public function testSignedStateGenerationAndValidation(): void
    {
        $session = $this->createSessionMock();
        $controller = $this->createController($session);

        $secret = 'my_super_secret_app_key_321';
        $state = $controller->generateSignedState($secret);

        $this->assertNotEmpty($state);
        $parts = explode('.', $state);
        $this->assertCount(3, $parts);

        // State válido
        $this->assertTrue($controller->verifySignedState($state, $secret, 600));

        // State com segredo diferente é rejeitado
        $this->assertFalse($controller->verifySignedState($state, 'wrong_secret', 600));

        // State com payload adulterado é rejeitado
        $tampered = 'tampered_nonce.' . $parts[1] . '.' . $parts[2];
        $this->assertFalse($controller->verifySignedState($tampered, $secret, 600));

        // State expirado (TTL 1 segundo)
        $expiredPayload = bin2hex(random_bytes(16)) . '.' . (time() - 700);
        $expiredSig = hash_hmac('sha256', $expiredPayload, $secret);
        $expiredState = $expiredPayload . '.' . $expiredSig;
        $this->assertFalse($controller->verifySignedState($expiredState, $secret, 600));

        // State com data futura abusiva
        $futurePayload = bin2hex(random_bytes(16)) . '.' . (time() + 500);
        $futureSig = hash_hmac('sha256', $futurePayload, $secret);
        $futureState = $futurePayload . '.' . $futureSig;
        $this->assertFalse($controller->verifySignedState($futureState, $secret, 600));

        // State vazio ou corrompido
        $this->assertFalse($controller->verifySignedState('', $secret, 600));
        $this->assertFalse($controller->verifySignedState('invalid.state', $secret, 600));
    }

    public function testOAuthCallbackRejectsMissingOrTamperedState(): void
    {
        $session = $this->createSessionMock();
        $controller = $this->createController($session);

        // Configuração inicial com segredo
        file_put_contents($this->tempConfigFile, json_encode([
            'app_key'    => '560750',
            'app_secret' => 'valid_secret_123',
        ]));

        // 1. Callback sem state deve ser rejeitado categoricamente
        $req1 = new Request(query: ['code' => 'test_code_123']);
        $resp1 = $controller->oauthCallback($req1);
        $this->assertNotNull($session->getFlash());
        $flash = $session->getFlash();
        $this->assertSame('error', $flash['type']);
        $this->assertStringContainsString('state inválido', $flash['msg']);

        // 2. Callback com state forjado deve ser rejeitado categoricamente
        $req2 = new Request(query: ['code' => 'test_code_123', 'state' => 'forged_attacker_state.123.sig']);
        $resp2 = $controller->oauthCallback($req2);
        $flash2 = $session->getFlash();
        $this->assertSame('error', $flash2['type']);
        $this->assertStringContainsString('state inválido', $flash2['msg']);
    }

    public function testAtomicConfigStorageOperations(): void
    {
        $testData = [
            'app_key'    => '560750',
            'app_secret' => 'secret_xyz',
            'tested_at'  => 123456789,
        ];

        // 1. Escrita atômica
        $ok = AtomicConfigStorage::write($this->tempConfigFile, $testData);
        $this->assertTrue($ok);
        $this->assertFileExists($this->tempConfigFile);

        // 2. Leitura atômica
        $read = AtomicConfigStorage::read($this->tempConfigFile);
        $this->assertSame('560750', $read['app_key']);
        $this->assertSame('secret_xyz', $read['app_secret']);

        // 3. Atualização transacional sob trava exclusiva
        $updated = AtomicConfigStorage::update($this->tempConfigFile, function (array $cfg): array {
            $cfg['access_token'] = 'token_abc_999';
            return $cfg;
        });

        $this->assertSame('token_abc_999', $updated['access_token']);
        $this->assertSame('560750', $updated['app_key']);

        $readAgain = AtomicConfigStorage::read($this->tempConfigFile);
        $this->assertSame('token_abc_999', $readAgain['access_token']);
    }

    public function testSaveSettingsPreservesMaskedSecrets(): void
    {
        $session = $this->createSessionMock();
        $csrf = $session->getCsrfToken();
        $controller = $this->createController($session);

        file_put_contents($this->tempConfigFile, json_encode([
            'app_key'      => '560750',
            'app_secret'   => 'original_secret_kept',
            'access_token' => 'original_token_kept',
        ]));

        // Submete com segredos mascarados (•••••••• e ***)
        $req = new Request(request: [
            'csrf_token'   => $csrf,
            'app_key'      => '560750',
            'app_secret'   => '••••••••••••••••kept',
            'access_token' => '***masked_token***',
        ]);

        $controller->saveSettings($req);

        $savedConfig = AtomicConfigStorage::read($this->tempConfigFile);
        $this->assertSame('original_secret_kept', $savedConfig['app_secret']);
        $this->assertSame('original_token_kept', $savedConfig['access_token']);
    }

    public function testClassifyApiExceptionAvailabilityNeverDeletesOnAuthOrNetworkErrors(): void
    {
        // 1. Erros de Autenticação / Token / Sessão
        $eAuth1 = new AliExpressApiException('MissingParameter: access_token is required', 400, 'MissingParameter');
        $this->assertSame('sdk_auth_required', ProductMiningService::classifyApiExceptionAvailability($eAuth1));

        $eAuth2 = new AliExpressApiException('IllegalAccessToken: Invalid session key', 401, 'IllegalAccessToken');
        $this->assertSame('sdk_auth_required', ProductMiningService::classifyApiExceptionAvailability($eAuth2));

        $eAuth3 = new RuntimeException('Signature does not match for api request');
        $this->assertSame('sdk_auth_required', ProductMiningService::classifyApiExceptionAvailability($eAuth3));

        // 2. Erros de Quota / Rate Limit
        $eRate = new AliExpressApiException('AppCallQuotaExceeded: Hourly limit reached', 429, 'AppCallQuotaExceeded');
        $this->assertSame('rate_limited', ProductMiningService::classifyApiExceptionAvailability($eRate));

        // 3. Erros de Rede e Timeout
        $eNet1 = new AliExpressApiException('cURL error 28: Operation timed out', 0, 'CONNECTION_ERROR');
        $this->assertSame('network_or_system_error', ProductMiningService::classifyApiExceptionAvailability($eNet1));

        $eNet2 = new AliExpressApiException('ServiceUnavailable: 503 Gateway maintenance', 503, 'HTTP_503');
        $this->assertSame('network_or_system_error', ProductMiningService::classifyApiExceptionAvailability($eNet2));

        // 4. Erros Legítimos de Produto Inexistente (esses SIM retornam not_found)
        $eNotFound1 = new AliExpressApiException('ITEM_NOT_FOUND: The requested item does not exist', 404, 'ITEM_NOT_FOUND');
        $this->assertSame('not_found', ProductMiningService::classifyApiExceptionAvailability($eNotFound1));

        $eNotFound2 = new AliExpressApiException('All SKU Unsaleable', 200, 'PRODUCT_UNSALEABLE');
        $this->assertSame('not_found', ProductMiningService::classifyApiExceptionAvailability($eNotFound2));
    }

    public function testTokenUnwrapHandlesAllIopEnvelopesAndNormalizesExpirations(): void
    {
        // 1. Resposta embrulhada em /wl/gray/first_response (simplify=false)
        $rawGray = [
            '/wl/gray/first_response' => [
                'access_token'  => 'gray_token_xyz',
                'refresh_token' => 'gray_refresh_123',
                'expires_in'    => 86400,
                'account'       => 'gray_seller',
            ],
        ];

        $tokenGray = AliExpressToken::fromApiResponse($rawGray);
        $this->assertSame('gray_token_xyz', $tokenGray->accessToken);
        $this->assertSame('gray_refresh_123', $tokenGray->refreshToken);
        $this->assertSame(86400, $tokenGray->expiresIn);
        $this->assertSame('gray_seller', $tokenGray->account);

        // 2. Resposta com timestamp em epoch milissegundos (> 1e11)
        $futureMs = (time() + 172800) * 1000;
        $rawMs = [
            'data' => [
                'access_token' => 'ms_token_abc',
                'expire_time'  => $futureMs,
                'user_nick'    => 'ms_user',
            ],
        ];

        $tokenMs = AliExpressToken::fromApiResponse($rawMs);
        $this->assertSame('ms_token_abc', $tokenMs->accessToken);
        $this->assertGreaterThanOrEqual(172700, $tokenMs->expiresIn);
        $this->assertLessThanOrEqual(172900, $tokenMs->expiresIn);
        $this->assertSame('ms_user', $tokenMs->account);
    }

    public function testAliExpressApiClientTextSearchImplementation(): void
    {
        $client = new class('test_app_key', 'test_app_secret', 'https://api-sg.aliexpress.com/sync') extends AliExpressApiClient {
            /** @var array<string, mixed> */
            public array $capturedExecuteParams = [];
            public string $capturedMethod = '';

            public function execute(string $apiMethod, array $params = [], ?string $accessToken = null): array
            {
                $this->capturedMethod = $apiMethod;
                $this->capturedExecuteParams = $params;

                return [
                    'aliexpress_ds_text_search_response' => [
                        'data' => [
                            'products' => [
                                'traffic_product_d_t_o' => [
                                    ['product_id' => '1005001', 'product_title' => 'Smartwatch Ultra Pro'],
                                ],
                            ],
                            'current_record_count' => 1,
                        ],
                    ],
                ];
            }
        };

        // Chama o método textSearch especificado no dropshiper.pdf
        $results = $client->textSearch([
            'keyword'      => 'smartwatch',
            'country_code' => 'BR',
            'sort_by'      => 'min_price,asc',
            'page_size'    => 25,
            'page_index'   => 2,
        ]);

        $this->assertSame('aliexpress.ds.text.search', $client->capturedMethod);
        $this->assertSame('smartwatch', $client->capturedExecuteParams['keyWord']);
        $this->assertSame('BR', $client->capturedExecuteParams['countryCode']);
        $this->assertSame('min_price,asc', $client->capturedExecuteParams['sortBy']);
        $this->assertSame('25', $client->capturedExecuteParams['pageSize']);
        $this->assertSame('2', $client->capturedExecuteParams['pageIndex']);

        // Verifica que searchProducts delega para textSearch quando keyword estiver presente
        $products = $client->searchProducts(['keyword' => 'smartwatch']);
        $this->assertCount(1, $products);
        $this->assertSame('Smartwatch Ultra Pro', $products[0]['product_title']);
    }

    public function testAliExpressContractSellerDetailsImagesAndPricing(): void
    {
        $mockApi = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AliExpressApiClientInterface::class);
        $mockCurrency = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\CurrencyConverterInterface::class);
        $mockCurrency->method('calculateSalePrice')->willReturn(149.90);
        $mockCurrency->method('calculateSalePriceFromBrl')->willReturn(149.90);
        $mockAi = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\AiProductEnricherInterface::class);
        $mockAi->method('polishTitle')->willReturn('Fone de Ouvido Bluetooth Sem Fio TWS Pro');
        $mockRepo = $this->createMock(\DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface::class);

        $miningService = new \DomainSystem\Plugins\domain_drop\Services\ProductMiningService(
            $mockApi,
            $mockCurrency,
            $mockAi,
            $mockRepo
        );

        $rawItem = [
            'ae_item_base_info_dto' => [
                'product_id' => '1005007891234567',
                'subject'    => 'Fone de Ouvido Bluetooth Sem Fio TWS Pro',
            ],
            'ae_store_info' => [
                'store_id'           => '8823910',
                'store_name'         => 'Loja Oficial Tech Sound',
                'store_country_code' => 'CN',
            ],
            'ae_multimedia_info_dto' => [
                'image_urls' => 'https://ae01.alicdn.com/kf/img1.jpg;https://ae01.alicdn.com/kf/img2.jpg;https://ae01.alicdn.com/kf/img3.jpg',
            ],
            'ae_item_sku_info_dtos' => [
                'ae_item_sku_info_d_t_o' => [
                    [
                        'sku_id'           => 'sku_001',
                        'sku_price'        => '29.90',
                        'offer_sale_price' => '14.50',
                        'sku_image'        => 'https://ae01.alicdn.com/kf/sku_black.jpg',
                        'ae_sku_property_dtos' => [
                            'ae_sku_property_d_t_o' => [
                                [
                                    'sku_property_name'            => 'Color',
                                    'property_value_definition_name' => 'Preto Fosco',
                                    'sku_image'                    => 'https://ae01.alicdn.com/kf/sku_black.jpg',
                                ],
                            ],
                        ],
                    ],
                    [
                        'sku_id'           => 'sku_002',
                        'sku_price'        => '32.90',
                        'offer_sale_price' => '16.80',
                        'sku_image'        => 'https://ae01.alicdn.com/kf/sku_white.jpg',
                        'ae_sku_property_dtos' => [
                            'ae_sku_property_d_t_o' => [
                                [
                                    'sku_property_name'            => 'Color',
                                    'property_value_definition_name' => 'Branco Neve',
                                    'sku_image'                    => 'https://ae01.alicdn.com/kf/sku_white.jpg',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dto = $miningService->transformRawItemToDto($rawItem, 'Eletrônicos');

        // 1. Link direto do vendedor e ID oficial
        $this->assertSame('1005007891234567', $dto->aliexpressId);
        $this->assertSame('https://www.aliexpress.com/item/1005007891234567.html', $dto->attributes['aliexpress_url']);

        // 2. Metadados oficiais do vendedor
        $this->assertSame('Loja Oficial Tech Sound', $dto->attributes['store_name']);
        $this->assertSame('https://www.aliexpress.com/store/8823910', $dto->attributes['store_url']);
        $this->assertSame('CN', $dto->attributes['store_country']);

        // 3. Preço real do vendedor (min offer_sale_price = 14.50)
        $this->assertSame(14.50, $dto->costPriceUsd);

        // 4. Todas as imagens extraídas da galeria e SKUs
        $this->assertGreaterThanOrEqual(5, count($dto->images));
        $this->assertContains('https://ae01.alicdn.com/kf/img1.jpg', $dto->images);
        $this->assertContains('https://ae01.alicdn.com/kf/img2.jpg', $dto->images);
        $this->assertContains('https://ae01.alicdn.com/kf/img3.jpg', $dto->images);
        $this->assertContains('https://ae01.alicdn.com/kf/sku_black.jpg', $dto->images);
        $this->assertContains('https://ae01.alicdn.com/kf/sku_white.jpg', $dto->images);
    }
}
