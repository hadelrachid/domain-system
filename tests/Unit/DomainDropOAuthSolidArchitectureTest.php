<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Plugins\domain_drop\Contracts\IopHttpClientInterface;
use DomainSystem\Plugins\domain_drop\Contracts\OAuthTokenRepositoryInterface;
use DomainSystem\Plugins\domain_drop\Services\OAuth\AliExpressAuthorizationUrlBuilder;
use DomainSystem\Plugins\domain_drop\Services\OAuth\ConfigOAuthTokenRepository;
use DomainSystem\Plugins\domain_drop\Services\OAuth\TokenExchangeService;
use DomainSystem\Plugins\domain_drop\ValueObjects\AliExpressToken;
use PHPUnit\Framework\TestCase;

final class DomainDropOAuthSolidArchitectureTest extends TestCase
{
    public function testTokenValueObjectConstructsAndConvertsCorrectly(): void
    {
        $payload = [
            'code'                     => '0',
            'access_token'             => 'test_at_123',
            'refresh_token'            => 'test_rt_456',
            'expire_time'              => 2592000,
            'refresh_token_valid_time' => 5184000,
            'account'                  => 'seller@rachidd.com',
            'user_id'                  => '1001',
            'seller_id'                => '2002',
            'account_id'               => '7003',
            'account_platform'         => 'seller_center',
        ];

        $token = AliExpressToken::fromApiResponse($payload);

        $this->assertSame('test_at_123', $token->accessToken);
        $this->assertSame('test_rt_456', $token->refreshToken);
        $this->assertSame(2592000, $token->expiresIn);
        $this->assertSame(5184000, $token->refreshExpiresIn);
        $this->assertSame('seller@rachidd.com', $token->account);
        $this->assertFalse($token->isExpired());

        $arr = $token->toArray();
        $this->assertSame('test_at_123', $arr['access_token']);
        $this->assertSame('seller@rachidd.com', $arr['account']);
    }

    public function testTokenValueObjectThrowsOnMissingAccessToken(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Resposta do AliExpress não trouxe access_token válido');

        AliExpressToken::fromApiResponse(['code' => '0', 'data' => []]);
    }

    public function testAuthorizationUrlBuilderProducesCompliantUrl(): void
    {
        $builder = new AliExpressAuthorizationUrlBuilder('560750');
        $url = $builder->build('https://rachidd.com/admin/dropship/callback', 'state_abc');

        $this->assertStringStartsWith('https://api-sg.aliexpress.com/oauth/authorize?', $url);
        $this->assertStringContainsString('client_id=560750', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('state=state_abc', $url);
        $this->assertStringContainsString(urlencode('https://rachidd.com/admin/dropship/callback'), $url);
    }

    public function testConfigOAuthTokenRepositoryStoresAndLoads(): void
    {
        $tempDir = sys_get_temp_dir() . '/ds_repo_test_' . uniqid();
        @mkdir($tempDir . '/config/plugins', 0777, true);
        file_put_contents($tempDir . '/config/plugins/domain_drop.json', json_encode([
            'enabled' => true,
            'app_key' => '560750',
            'app_secret' => 'secret_123'
        ]));

        $repo = new ConfigOAuthTokenRepository($tempDir);
        $this->assertNull($repo->load());

        $token = new AliExpressToken(
            accessToken: 'at_persisted',
            refreshToken: 'rt_persisted',
            expiresIn: 3600,
            refreshExpiresIn: 7200,
            account: 'contato@rachidd.com',
            createdAt: time()
        );

        $repo->store($token);

        $loaded = $repo->load();
        $this->assertNotNull($loaded);
        $this->assertSame('at_persisted', $loaded->accessToken);
        $this->assertSame('rt_persisted', $loaded->refreshToken);
        $this->assertSame('contato@rachidd.com', $loaded->account);

        $repo->clear();
        $this->assertNull($repo->load());

        @unlink($tempDir . '/config/plugins/domain_drop.json');
        @rmdir($tempDir . '/config/plugins');
        @rmdir($tempDir . '/config');
        @rmdir($tempDir);
    }

    public function testTokenExchangeServiceCoordinatesHttpAndRepository(): void
    {
        $mockHttp = $this->createMock(IopHttpClientInterface::class);
        $mockHttp->expects($this->once())
            ->method('call')
            ->with('/auth/token/create', ['code' => 'auth_code_xyz'])
            ->willReturn([
                'code' => '0',
                'access_token' => 'at_fresh',
                'refresh_token' => 'rt_fresh',
                'expires_in' => 2592000,
                'account' => 'seller_account'
            ]);

        $mockRepo = $this->createMock(OAuthTokenRepositoryInterface::class);
        $mockRepo->expects($this->once())
            ->method('store')
            ->with($this->callback(function (AliExpressToken $t) {
                return $t->accessToken === 'at_fresh' && $t->account === 'seller_account';
            }));

        $service = new TokenExchangeService($mockHttp, $mockRepo);
        $token = $service->exchangeCode('auth_code_xyz');

        $this->assertSame('at_fresh', $token->accessToken);
        $this->assertSame('seller_account', $token->account);
    }
}
