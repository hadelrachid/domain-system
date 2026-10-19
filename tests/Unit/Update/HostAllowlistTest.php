<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit\Update;

use DomainSystem\Core\Update\Infrastructure\HostAllowlist;
use PHPUnit\Framework\TestCase;

final class HostAllowlistTest extends TestCase
{
    public function testDefaultAllowlistPermitsOfficialGitHubHostsAndWildcardSubdomains(): void
    {
        $allowlist = new HostAllowlist();

        $this->assertTrue($allowlist->isAllowed('api.github.com'));
        $this->assertTrue($allowlist->isAllowed('API.GITHUB.COM'));
        $this->assertTrue($allowlist->isAllowed('github.com'));
        $this->assertTrue($allowlist->isAllowed('objects.githubusercontent.com'));
        $this->assertTrue($allowlist->isAllowed('release-assets.githubusercontent.com'));
        $this->assertTrue($allowlist->isAllowed('codeload.Sub.githubusercontent.com'));
    }

    public function testDefaultAllowlistRejectsBareWildcardBaseDomainAndLookalikeHosts(): void
    {
        $allowlist = new HostAllowlist();

        $this->assertFalse($allowlist->isAllowed(''));
        $this->assertFalse($allowlist->isAllowed('githubusercontent.com'));
        $this->assertFalse($allowlist->isAllowed('evil-github.com'));
        $this->assertFalse($allowlist->isAllowed('api.github.com.evil.org'));
        $this->assertFalse($allowlist->isAllowed('evilgithubusercontent.com'));
        $this->assertFalse($allowlist->isAllowed('127.0.0.1'));
    }
}
