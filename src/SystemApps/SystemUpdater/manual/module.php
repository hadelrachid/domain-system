<?php

declare(strict_types=1);

use DomainSystem\Core\Contracts\ManualModuleInterface;

return new class implements ManualModuleInterface {
    public function getId(): string
    {
        return 'system-updater';
    }

    public function getTitle(): string
    {
        return 'Atualizador Automático (GitHub Release)';
    }

    public function getSubtitle(): string
    {
        return 'Verificação, download assinado SHA-256, backup, migração de schema por contrato e rollback automático.';
    }

    public function getBadge(): string
    {
        return 'SystemApp Ring 0 • v2.3.0';
    }

    public function getAccentColor(): string
    {
        return '#38bdf8';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/settings/updates';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'updater-check-ui',
                'label' => 'Acessar /admin/settings/updates e clicar em "Verificar agora" validando exibição da versão instalada e status da Release.',
            ],
            [
                'id'    => 'updater-dashboard-widget',
                'label' => 'Confirmar que o widget "Versão do Sistema" aparece no Painel (/admin) sem violação de auditoria Ring 3.',
            ],
            [
                'id'    => 'updater-cli-commands',
                'label' => 'Executar "php os-cli.php system:status" e "php os-cli.php system:health" no terminal.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        $prefix = rtrim($baseUrl, '/');
        $updatesUrl = htmlspecialchars($prefix . '/admin/settings/updates', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div style="line-height: 1.7; color: #e2e8f0; font-size: 14px;">
    <p>
        O <strong>SystemUpdater</strong> é o motor oficial de atualização em tempo real do núcleo do <strong>Domain System</strong> (Modelo A — Monorepo Oficial), desenhado 100% sobre contratos em <code>src/Core/Contracts/Update/</code>.
    </p>

    <h4 style="color: #38bdf8; margin-top: 16px;">1. Como funciona a Máquina de Estados</h4>
    <ol style="padding-left: 20px;">
        <li><strong>Checked / Downloaded:</strong> Consulta a GitHub Releases API com cache ETag e baixa o pacote oficial em streaming.</li>
        <li><strong>Verified:</strong> Valida o hash criptográfico SHA-256 do pacote e de cada arquivo listado no <code>manifest.json</code> em quarentena.</li>
        <li><strong>Preflighted:</strong> Confere versão do PHP, extensões, espaço livre em disco (3x), permissões de escrita, compatibilidade de plugins e plano de migrations.</li>
        <li><strong>BackedUp &amp; MaintenanceOn:</strong> Gera ponto de restauração de código e banco de dados em <code>storage/backups/core/</code> e ativa o modo de manutenção (com isenção para as rotas de avanço e saúde).</li>
        <li><strong>Applied, Migrated &amp; SchemaVerified:</strong> Troca arquivos de forma atômica preservando caminhos protegidos (<code>.env</code>, <code>config/</code>, <code>storage/</code>, uploads), executa migrations aditivas e confere o contrato de tabelas/colunas (<code>expects</code>).</li>
        <li><strong>HealthChecked &amp; Done:</strong> Valida a saúde em nova requisição HTTP; se qualquer etapa após o backup falhar, executa <strong>rollback automático</strong> completo.</li>
    </ol>

    <h4 style="color: #38bdf8; margin-top: 16px;">2. Comandos via Terminal (OS-CLI)</h4>
    <pre style="background: #090d16; padding: 12px; border-radius: 6px; border: 1px solid #1e293b; color: #cbd5e1; font-size: 12px;">php os-cli.php system:check
php os-cli.php system:status
php os-cli.php system:update --yes
php os-cli.php system:rollback
php os-cli.php system:health</pre>

    <p style="margin-top: 14px;">
        Acesse a central em <a href="{$updatesUrl}" style="color: #38bdf8; font-weight: 600;">Configurações Globais → Atualizações</a>.
    </p>
</div>
HTML;
    }
};
