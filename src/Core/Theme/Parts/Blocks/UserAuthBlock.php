<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Blocks;

use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Theme\Parts\Blocks\Traits\AllowedPartTypesTrait;
use DomainSystem\Core\Theme\Parts\Contracts\BlockInterface;
use DomainSystem\Core\Theme\Parts\Contracts\ElementStyleRendererInterface;
use DomainSystem\Core\Theme\Parts\Contracts\HtmlSanitizerInterface;
use DomainSystem\Core\Theme\Parts\Contracts\PublicUrlResolverInterface;
use DomainSystem\Core\Theme\Parts\Contracts\Styles\StylableElementInterface;
use DomainSystem\Core\Theme\Parts\Styles\ElementStyleRenderer;
use DomainSystem\Core\Theme\Parts\Styles\Traits\StylableElementTrait;
use DomainSystem\Core\Theme\Parts\Support\BaseUrlResolver;
use DomainSystem\Core\Theme\Parts\Support\SafeHtmlSanitizer;

/**
 * Bloco Semântico de Autenticação / Login e Cadastro de Alunos no Header & Footer Builder (SOLID - SRP / OCP).
 * 
 * Renderiza dinamicamente:
 * - Deslogado: Botão Login ("Entrar") e Botão Cadastrar ("Criar Conta") vinculados ao Academy.
 * - Logado: Atalho para a Área do Aluno ("🎓 Meu Painel") e Botão Sair ("Logout").
 */
class UserAuthBlock extends AbstractBlock
{
    private ?SessionManagerInterface $session;

    public function __construct(
        ?HtmlSanitizerInterface $htmlSanitizer = null,
        ?PublicUrlResolverInterface $urlResolver = null,
        ?ElementStyleRendererInterface $styleRenderer = null,
        ?SessionManagerInterface $session = null
    ) {
        parent::__construct($htmlSanitizer, $urlResolver, $styleRenderer);
        $this->session = $session;
    }

    public function getType(): string
    {
        return 'user_auth';
    }

    public function getName(): string
    {
        return 'Área de Login / Aluno';
    }

    public function getIcon(): string
    {
        return '👤';
    }

    public function getCategory(): string
    {
        return 'navigation';
    }

    public function render(array $data = [], array $context = []): string
    {
        $this->styleData = $data;
        $baseUrl = $this->urlResolver->getBaseUrl();

        // 1. Detecção do Estado de Autenticação do Aluno (Isolado da Sessão Administrativa)
        $isLoggedIn = false;
        $userName = 'Aluno';

        $isExplicitlyLoggedOut = ($this->session !== null && $this->session->has('academy_logged_out'))
            || (isset($_SESSION['academy_logged_out']) && $_SESSION['academy_logged_out']);

        if (!$isExplicitlyLoggedOut) {
            if (isset($context['is_logged_in'])) {
                $isLoggedIn = (bool)$context['is_logged_in'];
                $userName = (string)($context['user_name'] ?? 'Aluno');
            } elseif ($this->session !== null && $this->session->has('academy_student_id')) {
                $isLoggedIn = true;
                $userName = (string)($this->session->get('academy_student_name', 'Aluno'));
            } elseif (isset($_SESSION['academy_student_id'])) {
                $isLoggedIn = true;
                $userName = (string)($_SESSION['academy_student_name'] ?? 'Aluno');
            } elseif ($this->session !== null && $this->session->has('user_id')) {
                $role = (string)$this->session->get('user_role', '');
                if ($role !== 'admin' && $role !== 'manager' && $role !== 'emergency') {
                    $isLoggedIn = true;
                    $userName = (string)($this->session->get('user_name', 'Aluno'));
                }
            } elseif (isset($_SESSION['user_id'])) {
                $role = (string)($_SESSION['user_role'] ?? '');
                if ($role !== 'admin' && $role !== 'manager' && $role !== 'emergency') {
                    $isLoggedIn = true;
                    $userName = (string)($_SESSION['user_name'] ?? 'Aluno');
                }
            }
        }

        // 2. Parâmetros e Rotas Personalizáveis com Resolução Dinâmica de Base URL
        $loginText = (string)($data['login_text'] ?? 'Entrar');
        $rawLoginUrl = (string)($data['login_url'] ?? '/aluno/login');
        $loginUrl = $this->urlResolver->resolve($rawLoginUrl);
        $loginUrl = $this->htmlSanitizer->sanitizeUrl($loginUrl, $this->urlResolver->resolve('/aluno/login'));

        $registerText = (string)($data['register_text'] ?? 'Cadastrar');
        $rawRegisterUrl = (string)($data['register_url'] ?? '/aluno/cadastro');
        $registerUrl = $this->urlResolver->resolve($rawRegisterUrl);
        $registerUrl = $this->htmlSanitizer->sanitizeUrl($registerUrl, $this->urlResolver->resolve('/aluno/cadastro'));

        $dashboardText = (string)($data['dashboard_text'] ?? '🎓 Área do Aluno');
        $rawDashboardUrl = (string)($data['dashboard_url'] ?? '/aluno/painel');
        $dashboardUrl = $this->urlResolver->resolve($rawDashboardUrl);
        $dashboardUrl = $this->htmlSanitizer->sanitizeUrl($dashboardUrl, $this->urlResolver->resolve('/aluno/painel'));

        $logoutText = (string)($data['logout_text'] ?? 'Sair');
        $rawLogoutUrl = (string)($data['logout_url'] ?? '/aluno/logout');
        $logoutUrl = $this->urlResolver->resolve($rawLogoutUrl);
        $logoutUrl = $this->htmlSanitizer->sanitizeUrl($logoutUrl, $this->urlResolver->resolve('/aluno/logout'));

        // 3. Alinhamento e Espaçamento
        $align = (string)($data['align'] ?? $data['text_align'] ?? 'right');
        $justify = match ($align) {
            'left'   => 'flex-start',
            'center' => 'center',
            default  => 'flex-end',
        };

        $safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');
        $safeRegisterUrl = htmlspecialchars($registerUrl, ENT_QUOTES, 'UTF-8');
        $safeDashboardUrl = htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8');
        $safeLogoutUrl = htmlspecialchars($logoutUrl, ENT_QUOTES, 'UTF-8');

        $safeLoginText = htmlspecialchars($loginText, ENT_QUOTES, 'UTF-8');
        $safeRegisterText = htmlspecialchars($registerText, ENT_QUOTES, 'UTF-8');
        $safeDashboardText = htmlspecialchars($dashboardText, ENT_QUOTES, 'UTF-8');
        $safeLogoutText = htmlspecialchars($logoutText, ENT_QUOTES, 'UTF-8');

        $absorbAttr = (($data['absorb_in_mobile'] ?? true) !== false) ? ' data-mobile-absorb="true"' : ' data-mobile-absorb="false"';

        // 4. Renderização do Estado Autenticado (Limpo, sem expor o nome do usuário/admin como sufixo)
        if ($isLoggedIn) {
            return <<<HTML
<div class="ds-block-user-auth"{$absorbAttr} style="display: flex; justify-content: {$justify}; align-items: center; gap: 10px; width: 100%;">
    <a href="{$safeDashboardUrl}" class="ds-auth-btn ds-auth-dashboard" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 6px; background: rgba(69, 243, 255, 0.12); border: 1px solid rgba(69, 243, 255, 0.35); color: #45f3ff; font-weight: 700; font-size: 0.85rem; text-decoration: none; transition: all 0.2s ease;">
        <span>{$safeDashboardText}</span>
    </a>
    <a href="{$safeLogoutUrl}" class="ds-auth-btn ds-auth-logout" style="display: inline-flex; align-items: center; padding: 7px 12px; border-radius: 6px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); color: #cbd5e1; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.2s ease;">
        {$safeLogoutText}
    </a>
</div>
HTML;
        }

        // 5. Renderização do Estado Visitante / Deslogado
        return <<<HTML
<div class="ds-block-user-auth"{$absorbAttr} style="display: flex; justify-content: {$justify}; align-items: center; gap: 10px; width: 100%;">
    <a href="{$safeLoginUrl}" class="ds-auth-btn ds-auth-login" style="display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 6px; background: transparent; border: 1px solid rgba(255, 255, 255, 0.2); color: #f8fafc; font-weight: 600; font-size: 0.86rem; text-decoration: none; transition: all 0.2s ease;">
        <span>🔑</span>
        <span>{$safeLoginText}</span>
    </a>
    <a href="{$safeRegisterUrl}" class="ds-auth-btn ds-auth-register" style="display: inline-flex; align-items: center; gap: 5px; padding: 7px 15px; border-radius: 6px; background: #00d284; border: 1px solid #00d284; color: #0b0c10; font-weight: 700; font-size: 0.86rem; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 2px 10px rgba(0, 210, 132, 0.2);">
        <span>✨</span>
        <span>{$safeRegisterText}</span>
    </a>
</div>
HTML;
    }

    public function getSchema(): array
    {
        return [
            'type' => 'user_auth',
            'fields' => [
                'login_text'     => ['type' => 'text', 'label' => 'Texto do Botão Login', 'default' => 'Entrar'],
                'login_url'      => ['type' => 'text', 'label' => 'URL de Login', 'default' => '/aluno/login'],
                'register_text'  => ['type' => 'text', 'label' => 'Texto do Botão Cadastrar', 'default' => 'Cadastrar'],
                'register_url'   => ['type' => 'text', 'label' => 'URL de Cadastro', 'default' => '/aluno/cadastro'],
                'dashboard_text' => ['type' => 'text', 'label' => 'Texto da Área do Aluno', 'default' => '🎓 Área do Aluno'],
                'dashboard_url'  => ['type' => 'text', 'label' => 'URL do Painel do Aluno', 'default' => '/aluno/painel'],
                'logout_text'    => ['type' => 'text', 'label' => 'Texto de Logout', 'default' => 'Sair'],
                'logout_url'     => ['type' => 'text', 'label' => 'URL de Logout', 'default' => '/aluno/logout'],
                'align'          => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'right'],
                'absorb_in_mobile' => ['type' => 'boolean', 'label' => 'Incluir no Menu Hambúrguer (Mobile)', 'default' => true],
            ],
        ];
    }
}
