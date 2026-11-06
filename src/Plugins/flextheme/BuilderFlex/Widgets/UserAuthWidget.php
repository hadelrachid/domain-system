<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\BuilderFlex\Widgets;

use DomainSystem\Plugins\flextheme\BuilderFlex\Core\AbstractWidget;

/**
 * Widget de Autenticação / Login & Cadastro para o Flex Builder.
 */
class UserAuthWidget extends AbstractWidget
{
    public function getName(): string
    {
        return 'Login & Cadastro (Aluno)';
    }

    public function getShortcodeTag(): string
    {
        return 'flex_user_auth';
    }

    public function getControls(): array
    {
        return [
            'login_text'    => ['type' => 'text', 'label' => 'Texto Login', 'default' => 'Entrar'],
            'login_url'     => ['type' => 'text', 'label' => 'Link Login', 'default' => '/aluno/login'],
            'register_text' => ['type' => 'text', 'label' => 'Texto Cadastro', 'default' => 'Cadastrar'],
            'register_url'  => ['type' => 'text', 'label' => 'Link Cadastro', 'default' => '/aluno/cadastro'],
            'align'         => ['type' => 'select', 'label' => 'Alinhamento', 'options' => ['left', 'center', 'right'], 'default' => 'right'],
        ];
    }

    public function toDto(array $attributes, array $children = []): array
    {
        $dto = parent::toDto($attributes, $children);

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $resolveUrl = function(string $raw, string $default) use ($baseUrl): string {
            $raw = trim($raw);
            if ($raw === '') {
                $raw = $default;
            }
            if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://') || str_starts_with($raw, '//') || str_starts_with($raw, '#')) {
                return $raw;
            }
            if ($baseUrl !== '' && str_starts_with($raw, $baseUrl . '/')) {
                return $raw;
            }
            return ($baseUrl !== '' ? $baseUrl : '') . '/' . ltrim($raw, '/');
        };

        $loginText = (string)($attributes['login_text'] ?? 'Entrar');
        $loginUrl = $resolveUrl((string)($attributes['login_url'] ?? ''), '/aluno/login');
        $registerText = (string)($attributes['register_text'] ?? 'Cadastrar');
        $registerUrl = $resolveUrl((string)($attributes['register_url'] ?? ''), '/aluno/cadastro');
        $dashboardText = (string)($attributes['dashboard_text'] ?? 'Área do Aluno');
        $dashboardUrl = $resolveUrl((string)($attributes['dashboard_url'] ?? ''), '/aluno/painel');
        $logoutText = (string)($attributes['logout_text'] ?? 'Sair');
        $logoutUrl = $resolveUrl((string)($attributes['logout_url'] ?? ''), '/aluno/logout');

        // Verifica se há aluno logado via sessão isolada da academia
        $isLoggedIn = !empty($_SESSION['academy_student_id']);
        $studentName = (string)($_SESSION['academy_student_name'] ?? 'Aluno');

        if ($isLoggedIn) {
            $dto['tag'] = 'div';
            $dto['type'] = 'div';
            $dto['styles']['display'] = 'inline-flex';
            $dto['styles']['align-items'] = 'center';
            $dto['styles']['gap'] = '10px';
            $dto['children'] = [
                [
                    'type'     => 'a',
                    'tag'      => 'a',
                    'attrs'    => ['href' => $dashboardUrl],
                    'styles'   => [
                        'display'         => 'inline-flex',
                        'align-items'     => 'center',
                        'gap'             => '6px',
                        'padding'         => '8px 14px',
                        'border-radius'   => '6px',
                        'background'      => 'rgba(69, 243, 255, 0.12)',
                        'border'          => '1px solid rgba(69, 243, 255, 0.35)',
                        'color'           => '#45f3ff',
                        'font-weight'     => '700',
                        'font-size'       => '13px',
                        'text-decoration' => 'none',
                    ],
                    'content'  => '🎓 ' . htmlspecialchars($dashboardText),
                    'children' => [],
                ],
                [
                    'type'     => 'a',
                    'tag'      => 'a',
                    'attrs'    => ['href' => $logoutUrl],
                    'styles'   => [
                        'display'         => 'inline-flex',
                        'align-items'     => 'center',
                        'padding'         => '8px 12px',
                        'border-radius'   => '6px',
                        'background'      => 'rgba(255, 255, 255, 0.05)',
                        'border'          => '1px solid rgba(255, 255, 255, 0.15)',
                        'color'           => '#cbd5e1',
                        'font-weight'     => '600',
                        'font-size'       => '13px',
                        'text-decoration' => 'none',
                    ],
                    'content'  => htmlspecialchars($logoutText),
                    'children' => [],
                ],
            ];
            return $dto;
        }

        $dto['tag'] = 'div';
        $dto['type'] = 'div';
        $dto['styles']['display'] = 'inline-flex';
        $dto['styles']['align-items'] = 'center';
        $dto['styles']['gap'] = '10px';
        $dto['children'] = [
            [
                'type'     => 'a',
                'tag'      => 'a',
                'attrs'    => ['href' => $loginUrl],
                'styles'   => [
                    'display'         => 'inline-flex',
                    'align-items'     => 'center',
                    'gap'             => '6px',
                    'padding'         => '8px 14px',
                    'border-radius'   => '6px',
                    'background'      => 'transparent',
                    'border'          => '1px solid rgba(255, 255, 255, 0.25)',
                    'color'           => '#ffffff',
                    'font-weight'     => '600',
                    'font-size'       => '13px',
                    'text-decoration' => 'none',
                ],
                'content'  => '🔑 ' . htmlspecialchars($loginText),
                'children' => [],
            ],
            [
                'type'     => 'a',
                'tag'      => 'a',
                'attrs'    => ['href' => $registerUrl],
                'styles'   => [
                    'display'         => 'inline-flex',
                    'align-items'     => 'center',
                    'gap'             => '6px',
                    'padding'         => '8px 16px',
                    'border-radius'   => '6px',
                    'background'      => '#00d284',
                    'border'          => '1px solid #00d284',
                    'color'           => '#0b0c10',
                    'font-weight'     => '700',
                    'font-size'       => '13px',
                    'text-decoration' => 'none',
                    'box-shadow'      => '0 2px 10px rgba(0, 210, 132, 0.25)',
                ],
                'content'  => '✨ ' . htmlspecialchars($registerText),
                'children' => [],
            ],
        ];

        return $dto;
    }
}
