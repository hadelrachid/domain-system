<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DOMAIN-SYSTEM — MANUAL DE INSTRUÇÕES & TESTABILIDADE
 * ════════════════════════════════════════════════════════════════════════════
 * Guia Oficial de Testabilidade, Resolução e Roteiros Operacionais.
 * Versão: 2.2.0 | Compatível com Acesso Direto e Painel Admin.
 */

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' .
    ($_SERVER['HTTP_HOST'] ?? 'localhost') .
    preg_replace('#/manual.*$#', '', $_SERVER['SCRIPT_NAME'] ?? '')
);
$version = defined('DS_VERSION') ? DS_VERSION : '2.2.0';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual de Instruções e Testabilidade — Domain System v<?= htmlspecialchars($version) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #090d16;
            --bg-surface: #101726;
            --bg-card: #151e32;
            --bg-card-hover: #1c2742;
            --border-color: #232f4a;
            --border-highlight: #334368;
            --text-primary: #f0f4fc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-emerald: #059669;
            --accent-orange: #f97316;
            --accent-purple: #8b5cf6;
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--bg-base);
            color: var(--text-primary);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(6, 182, 212, 0.04) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.04) 0%, transparent 40%);
        }

        /* SIDEBAR DO ÍNDICE */
        .manual-sidebar {
            width: 320px;
            background: rgba(16, 23, 38, 0.95);
            backdrop-filter: blur(16px);
            border-right: 1px solid var(--border-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.4);
        }

        .sidebar-header {
            padding: 24px 20px 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(6, 182, 212, 0.12);
            color: var(--accent-cyan);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 8px;
            border: 1px solid rgba(6, 182, 212, 0.25);
        }

        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .sidebar-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 2px;
        }

        /* BARRA DE PROGRESSO DE TESTABILIDADE */
        .progress-box {
            margin: 16px 20px;
            padding: 12px;
            background: rgba(21, 30, 50, 0.8);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
        }

        .progress-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .progress-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .progress-value {
            color: var(--accent-green);
            font-weight: 700;
            font-family: var(--font-mono);
        }

        .progress-track {
            height: 6px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-green));
            border-radius: 3px;
            transition: width 0.3s ease;
            width: 0%;
        }

        /* BUSCA RÁPIDA NO ÍNDICE */
        .search-box {
            padding: 0 20px 12px;
        }

        .search-input {
            width: 100%;
            padding: 10px 14px 10px 36px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 10px rgba(6, 182, 212, 0.2);
        }

        .search-icon-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        /* NAVEGAÇÃO DOS CAPÍTULOS */
        .sidebar-nav {
            flex: 1;
            overflow-y: auto;
            padding: 8px 12px;
        }

        .nav-group-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            padding: 12px 10px 6px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.2s;
            border-left: 2px solid transparent;
        }

        .nav-item:hover {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: rgba(6, 182, 212, 0.1);
            color: var(--accent-cyan);
            border-left-color: var(--accent-cyan);
            font-weight: 600;
        }

        .nav-badge {
            margin-left: auto;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            font-family: var(--font-mono);
        }

        /* ÁREA PRINCIPAL DO CONTEÚDO */
        .manual-main {
            margin-left: 320px;
            flex: 1;
            padding: 40px 60px 80px;
            max-width: 1100px;
        }

        /* CABEÇALHO DO MANUAL */
        .main-header {
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .badge-version {
            background: linear-gradient(135deg, #0284c7, #2563eb);
            color: #fff;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            font-family: var(--font-mono);
        }

        .badge-status {
            background: rgba(16, 185, 129, 0.15);
            color: var(--accent-green);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .main-title {
            font-size: 32px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }

        .main-desc {
            font-size: 15px;
            color: var(--text-secondary);
            max-width: 750px;
            line-height: 1.7;
        }

        /* AÇÕES RÁPIDAS NO TOPO */
        .quick-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent-cyan);
            color: #05141e;
            border: 1px solid var(--accent-cyan);
        }

        .btn-primary:hover {
            background: #22d3ee;
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.4);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: var(--border-highlight);
        }

        /* SEÇÃO / CAPÍTULO */
        .manual-section {
            margin-bottom: 60px;
            scroll-margin-top: 30px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(6, 182, 212, 0.15);
            color: var(--accent-cyan);
            font-weight: 700;
            font-family: var(--font-mono);
            font-size: 14px;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.3px;
        }

        .section-tag {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        /* CARDS DE TESTABILIDADE E RESOLUÇÃO */
        .card-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .test-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 24px;
            transition: all 0.2s;
            position: relative;
        }

        .test-card:hover {
            border-color: var(--border-highlight);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
        }

        .card-subhead {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent-cyan);
            margin-bottom: 6px;
        }

        .card-title {
            font-size: 17px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 12px;
        }

        .card-desc {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 18px;
            line-height: 1.6;
        }

        /* BLOCOS DE INFORMAÇÃO / ALERTA */
        .info-box {
            background: rgba(6, 182, 212, 0.06);
            border-left: 4px solid var(--accent-cyan);
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-size: 13px;
            color: #e0f2fe;
        }

        .info-box.proof {
            background: rgba(16, 185, 129, 0.06);
            border-left-color: var(--accent-green);
            color: #d1fae5;
        }

        .info-box strong {
            color: #fff;
            display: inline-block;
            margin-bottom: 4px;
        }

        /* CHECKLIST INTERATIVO */
        .checklist-group {
            margin-top: 16px;
            background: rgba(9, 13, 22, 0.5);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
        }

        .checklist-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .check-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            cursor: pointer;
            user-select: none;
        }

        .check-item:last-child {
            border-bottom: none;
        }

        .check-item input[type="checkbox"] {
            margin-top: 3px;
            width: 16px;
            height: 16px;
            accent-color: var(--accent-green);
            cursor: pointer;
        }

        .check-text {
            font-size: 13px;
            color: var(--text-primary);
            transition: color 0.2s;
        }

        .check-item input:checked + .check-text {
            color: var(--accent-green);
            text-decoration: line-through;
            opacity: 0.8;
        }

        /* BLOCOS DE CÓDIGO E ATALHOS */
        .shortcut-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 8px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-family: var(--font-mono);
            font-size: 12px;
            color: #38bdf8;
            margin-right: 6px;
        }

        .code-box {
            background: #060911;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 16px;
            font-family: var(--font-mono);
            font-size: 12px;
            color: #e2e8f0;
            position: relative;
            margin: 12px 0;
            overflow-x: auto;
        }

        .btn-copy {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 11px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-copy:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        /* RESPONSIVIDADE */
        @media (max-width: 900px) {
            .manual-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .manual-sidebar.open {
                transform: translateX(0);
            }
            .manual-main {
                margin-left: 0;
                padding: 24px 20px 60px;
            }
            .mobile-menu-btn {
                display: flex;
            }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR DO ÍNDICE -->
    <aside class="manual-sidebar" id="manualSidebar">
        <div class="sidebar-header">
            <div class="brand-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                Domain System
            </div>
            <h2 class="sidebar-title">Manual de Instruções</h2>
            <p class="sidebar-subtitle">Guia de Testabilidade & Resolutividade</p>
        </div>

        <!-- PROGRESSO DOS TESTES -->
        <div class="progress-box">
            <div class="progress-meta">
                <span class="progress-label">Validação Concluída:</span>
                <span class="progress-value" id="progressText">0%</span>
            </div>
            <div class="progress-track">
                <div class="progress-fill" id="progressBar"></div>
            </div>
        </div>

        <!-- BUSCA INSTANTÂNEA -->
        <div class="search-box">
            <div class="search-icon-wrapper">
                <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" id="manualSearch" class="search-input" placeholder="Filtrar tópicos...">
            </div>
        </div>

        <!-- NAVEGAÇÃO -->
        <nav class="sidebar-nav" id="sidebarNav">
            <div class="nav-group-title">Capítulos & Roteiros</div>
            <a href="#cap-visao-geral" class="nav-item active">
                <span>🛸 Visão Geral do Sistema</span>
            </a>
            <a href="#cap-temas" class="nav-item">
                <span>🎨 1. Gerenciador de Temas</span>
                <span class="nav-badge">Live Preview</span>
            </a>
            <a href="#cap-logout-cache" class="nav-item">
                <span>🔒 2. Logout e Anti-Cache</span>
                <span class="nav-badge">Hostinger</span>
            </a>
            <a href="#cap-estado-site" class="nav-item">
                <span>🚧 3. Estado do Site (Em Breve)</span>
                <span class="nav-badge">SEO</span>
            </a>
            <a href="#cap-menus" class="nav-item">
                <span>🧭 4. Menus & Páginas</span>
                <span class="nav-badge">SOLID</span>
            </a>
            <a href="#cap-academy" class="nav-item">
                <span>🎓 5. Plugin Academy</span>
                <span class="nav-badge">16:9 / Slugs</span>
            </a>
            <a href="#cap-instalador" class="nav-item">
                <span>⚡ 6. Instalador & Runtime</span>
                <span class="nav-badge">Core</span>
            </a>
            <a href="#cap-testes" class="nav-item">
                <span>🧪 7. Testes Automatizados</span>
                <span class="nav-badge">100% OK</span>
            </a>
        </nav>
    </aside>

    <!-- CONTEÚDO PRINCIPAL -->
    <main class="manual-main">
        <header class="main-header">
            <div class="header-meta">
                <span class="badge-version">v<?= htmlspecialchars($version) ?></span>
                <span class="badge-status">● Arquitetura Homologada</span>
                <span style="font-size: 12px; color: var(--text-muted);">PHP 8.2+ | SOLID Compliance</span>
            </div>
            <h1 class="main-title">Manual de Testabilidade e Resolutividade</h1>
            <p class="main-desc">
                Este manual documenta formalmente os procedimentos operacionais e de teste do <strong>Domain System</strong>. Cada seção descreve a causa resolvida, os atalhos de acesso, o passo a passo de reprodução e a <strong>prova de resolutividade</strong> que comprova que o sistema está no caminho correto.
            </p>
            <div class="quick-actions">
                <a href="<?= htmlspecialchars($baseUrl) ?>/admin" class="btn-action btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    Ir para o Painel Admin
                </a>
                <button onclick="resetChecklist()" class="btn-action btn-secondary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                    Resetar Checklist de Testes
                </button>
            </div>
        </header>

        <!-- SEÇÃO: VISÃO GERAL -->
        <section id="cap-visao-geral" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">0</span>
                    <h2 class="section-title">Visão Geral & Metodologia de Testabilidade</h2>
                </div>
                <span class="section-tag">Fundação</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Conceito do Web Operating Environment</div>
                <h3 class="card-title">Por que este manual é uma ferramenta de garantia?</h3>
                <p class="card-desc">
                    O Domain System adota a arquitetura de Anéis de Proteção (Rings) e separação estrita de contratos. Este manual fornece roteiros estruturados onde cada funcionalidade chave é provada através de testes manuais complementares à suíte automatizada de 119 testes unitários.
                </p>
                <div class="info-box">
                    <strong>Como usar este manual:</strong> Ao executar cada etapa no seu navegador, clique nas caixas de seleção da lista de tarefas. Seu progresso será memorizado automaticamente pelo navegador.
                </div>
            </div>
        </section>

        <!-- SEÇÃO 1: TEMAS & LIVE PREVIEW -->
        <section id="cap-temas" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">1</span>
                    <h2 class="section-title">Gerenciador de Temas & Live Preview (Estilo WordPress)</h2>
                </div>
                <span class="section-tag">Themes</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Ativação & Pré-Visualização Isolada</div>
                <h3 class="card-title">Ativação Segura com Barra Flutuante de Controle</h3>
                <p class="card-desc">
                    Permite que múltiplos temas coexistam no sistema (em <code>themes/</code> ou empacotados em plugins como <code>flextheme</code>). O usuário pode pré-visualizar qualquer tema inativo sem afetar os visitantes do site ao vivo.
                </p>
                <p style="margin-bottom: 12px;">
                    <span class="shortcut-badge">Atalho de Acesso:</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/themes" target="_blank" style="font-weight:600; text-decoration:underline;">/admin/themes</a>
                </p>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Ao clicar em "Ver" em um tema inativo, a URL aberta é <code>/admin/themes/preview?theme={pasta}</code>. O tema renderizado é estritamente o selecionado (e NÃO o tema ativo em produção), com a barra flutuante preta <code>#ds-preview-topbar</code> contendo o botão "✓ Ativar este Tema" e "✕ Fechar e Voltar".
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Temas</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="theme-card-buttons">
                        <span class="check-text">Acessar <code>/admin/themes</code> e verificar se o tema ativo exibe "Ver Site ↗" e os inativos exibem "Ver" e "Ativar".</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="theme-preview-topbar">
                        <span class="check-text">Clicar no botão "Ver" de um tema inativo e confirmar a abertura da barra superior flutuante com o nome correto do tema.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="theme-1click-activate">
                        <span class="check-text">Clicar em "✓ Ativar este Tema" na barra superior e verificar o redirecionamento com confirmação de sucesso.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="theme-persistence">
                        <span class="check-text">Verificar se o tema recém-ativado é persistido em <code>config/active_theme.json</code> e passa a renderizar na home pública <code>/</code>.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 2: LOGOUT & ANTI-CACHE -->
        <section id="cap-logout-cache" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">2</span>
                    <h2 class="section-title">Logout e Blindagem Anti-Cache (LiteSpeed & Hostinger)</h2>
                </div>
                <span class="section-tag">Security & Cache</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Política Estrita de Sessão</div>
                <h3 class="card-title">Eliminação de Telas Autenticadas Fantasmas</h3>
                <p class="card-desc">
                    Em servidores com proxies agressivos (Hostinger, Nginx, Cloudflare), o logout anterior mantinha dados residuais em cache. O novo <code>SessionManager</code> e a política <code>CachePolicy</code> garantem o descarte total e imediato de dados sensíveis.
                </p>
                <p style="margin-bottom: 12px;">
                    <span class="shortcut-badge">Atalho de Acesso:</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/logout" target="_blank" style="font-weight:600; text-decoration:underline;">/logout</a>
                </p>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Após clicar em "Encerrar Sessão", o cookie de sessão é invalidado no passado (timestamp negativo). O cabeçalho <code>Clear-Site-Data: "cache", "storage"</code> instrui o navegador a limpar o armazenamento. Ao pressionar o botão "Voltar" (Back) do navegador ou digitar <code>/admin</code>, a tela de login é exibida invariavelmente.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Logout & Cache</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="logout-click">
                        <span class="check-text">Estar logado no painel e clicar em "Encerrar Sessão" no rodapé da barra lateral.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="logout-browser-back">
                        <span class="check-text">Após o redirecionamento para o login, pressionar a seta de Voltar do navegador. Confirmar que o painel NÃO é exibido.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="logout-direct-url">
                        <span class="check-text">Digitar <code>/admin</code> manualmente na barra de endereços. Confirmar o bloqueio e redirecionamento para o login.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 3: ESTADO DO SITE -->
        <section id="cap-estado-site" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">3</span>
                    <h2 class="section-title">Estado do Site ("Em Breve" / `coming_soon`)</h2>
                </div>
                <span class="section-tag">Site State</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Transição Segura Pós-Instalação</div>
                <h3 class="card-title">Página "Em Breve" com Blindagem SEO</h3>
                <p class="card-desc">
                    Quando o sistema é instalado, ele entra automaticamente em modo <code>coming_soon</code> para permitir que o administrador monte o site com calma, sem expor 404 ou páginas quebradas aos visitantes.
                </p>

                <div class="code-box">
                    # Variável no arquivo .env
                    SITE_STATUS=coming_soon
                    <button class="btn-copy" onclick="copyCode(this)">Copiar</button>
                </div>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Acessar <code>/</code> com <code>SITE_STATUS=coming_soon</code> retorna status HTTP <code>200 OK</code> com a página visual de "Em Breve" e cabeçalho <code>X-Robots-Tag: noindex, nofollow</code>. Quando alterado para <code>SITE_STATUS=live</code>, o tema ativo assume imediatamente.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Estado do Site</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="state-coming-soon">
                        <span class="check-text">Com <code>SITE_STATUS=coming_soon</code> no <code>.env</code>, abrir a home <code>/</code> em janela anônima e verificar a tela de "Em Breve".</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="state-live-switch">
                        <span class="check-text">Alterar para <code>SITE_STATUS=live</code> e confirmar o carregamento imediato do tema configurado.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 4: MENUS E PÁGINAS -->
        <section id="cap-menus" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">4</span>
                    <h2 class="section-title">Gerenciador de Menus & Associação com Páginas</h2>
                </div>
                <span class="section-tag">Navigation</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Desacoplamento SOLID & Transações Atômicas</div>
                <h3 class="card-title">Gestão de Menus Hierárquicos com Proteção contra Falhas</h3>
                <p class="card-desc">
                    O gerenciador de menus utiliza <code>PdoMenuRepository</code> com transações PDO (evitando exclusão acidental de itens em falhas) e conecta-se a páginas via <code>PageLookupInterface</code>.
                </p>
                <p style="margin-bottom: 12px;">
                    <span class="shortcut-badge">Atalho de Acesso:</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/menus" target="_blank" style="font-weight:600; text-decoration:underline;">/admin/menus</a>
                </p>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    A lista de páginas no menu lateral inclui as páginas prontas (Início, Blog, Sobre, Termos, Privacidade, Tutoriais). O shortcode <code>[nav_menu location="header"]</code> renderiza o menu hierárquico com sanitização de URLs.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Menus</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="menu-load-pages">
                        <span class="check-text">Acessar <code>/admin/menus</code> e constatar que a lista de páginas está preenchida e disponível para seleção.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="menu-save-atomic">
                        <span class="check-text">Adicionar páginas ao menu, reordenar e clicar em "Salvar Itens". Verificar mensagem de sucesso sem erros 403 de CSRF.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="menu-shortcode">
                        <span class="check-text">Confirmar que uma página com o shortcode <code>[nav_menu location="header"]</code> exibe a estrutura de links formatada.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 5: ACADEMY -->
        <section id="cap-academy" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">5</span>
                    <h2 class="section-title">Plugin Academy (Cursos & Cards 16:9)</h2>
                </div>
                <span class="section-tag">Academy</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">CRUD Completo & Upload Blindado</div>
                <h3 class="card-title">Gestão de Cursos, Proporção 16:9 e Slugs Acentuados</h3>
                <p class="card-desc">
                    Permite criar, editar, listar e excluir cursos. Imagens de capa são validadas estritamente na proporção 16:9 (mínimo 640x360 px, máx 2MB) e tipo MIME verificado via <code>finfo</code>. Ao substituir uma imagem, o arquivo antigo é removido do disco.
                </p>
                <p style="margin-bottom: 12px;">
                    <span class="shortcut-badge">Atalho de Acesso:</span>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/admin/academy" target="_blank" style="font-weight:600; text-decoration:underline;">/admin/academy</a>
                </p>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Títulos com acentuação em português geram slugs amigáveis únicos (ex: <em>"Introdução à Robótica"</em> $\rightarrow$ <code>introducao-a-robotica</code>). A rota <code>/admin/academy/edit/{id}</code> permite atualizar dados e imagem sem erros.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Academy</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="academy-create-accent">
                        <span class="check-text">Criar um curso em <code>/admin/academy/create</code> com título acentuado e verificar a geração correta do slug.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="academy-upload-169">
                        <span class="check-text">Fazer upload de uma imagem 16:9 (ex: 1280x720) e confirmar o salvamento em <code>public/uploads/academy/</code>.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="academy-edit-course">
                        <span class="check-text">Editar o curso em <code>/admin/academy/edit/{id}</code>, alterar o título e confirmar que a atualização é persistida.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="academy-shortcode">
                        <span class="check-text">Verificar se a página com o shortcode <code>[hub_de_cursos]</code> exibe o card responsivo do curso.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 6: INSTALADOR & RUNTIME -->
        <section id="cap-instalador" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">6</span>
                    <h2 class="section-title">Instalador Wizard & Servidor Hostinger</h2>
                </div>
                <span class="section-tag">Bootstrap</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Runtime Directories & Error Tolerance</div>
                <h3 class="card-title">Resiliência em Servidores de Produção</h3>
                <p class="card-desc">
                    O bootstrap do sistema executa <code>RuntimeDirectories::ensure()</code>, garantindo a existência automática das pastas <code>temp/</code>, <code>temp/cache/</code>, <code>temp/logs/</code>, <code>storage/</code> e <code>public/uploads/</code> com arquivos <code>.htaccess</code> contendo <code>Deny from all</code> para bloquear acesso HTTP direto a arquivos internos.
                </p>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Mesmo que as pastas não venham no clone do Git (devido ao <code>.gitignore</code>), o sistema inicializa sem falhas 500 no Hostinger e o <code>ErrorHandler</code> registra avisos sem abortar o fluxo da aplicação.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — Runtime</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="runtime-folders">
                        <span class="check-text">Verificar a existência das pastas <code>temp/</code> e <code>storage/</code> com proteção <code>.htaccess</code> no diretório raiz.</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="runtime-emergency-key">
                        <span class="check-text">Confirmar a presença de <code>EMERGENCY_KEY</code> gerada no arquivo <code>.env</code> para login emergencial.</span>
                    </label>
                </div>
            </div>
        </section>

        <!-- SEÇÃO 7: TESTES AUTOMATIZADOS -->
        <section id="cap-testes" class="manual-section">
            <div class="section-header">
                <div class="section-title-wrap">
                    <span class="section-num">7</span>
                    <h2 class="section-title">Suíte de Testes Automatizados (TDD & CI/CD)</h2>
                </div>
                <span class="section-tag">Quality Assurance</span>
            </div>
            <div class="test-card">
                <div class="card-subhead">Execução Local e no GitHub Actions</div>
                <h3 class="card-title">100% dos Testes Verdes e Blindagem Arquitetural</h3>
                <p class="card-desc">
                    A suíte de testes valida desde a inicialização de containers, middlewares de CSRF e descompactação de ZIPs com proteção Zip-Slip até testes arquiteturais de isolamento de controladores e tipagem.
                </p>

                <div class="code-box">
                    # Executar a suíte completa de testes no terminal
                    php -dextension=zip phpunit.phar
                    <button class="btn-copy" onclick="copyCode(this)">Copiar</button>
                </div>

                <div class="code-box">
                    # Executar os testes de arquitetura
                    php phpunit.phar tests/Architecture
                    <button class="btn-copy" onclick="copyCode(this)">Copiar</button>
                </div>

                <div class="info-box proof">
                    <strong>Prova de Resolutividade:</strong>
                    Todos os 119 testes executam e passam com sucesso (387 asserções, 0 falhas). O workflow no GitHub Actions (Linux) roda com 100% de sucesso.
                </div>

                <div class="checklist-group">
                    <div class="checklist-title">Checklist de Teste — PHPUnit</div>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="phpunit-local-run">
                        <span class="check-text">Executar <code>php phpunit.phar</code> no terminal e obter o sumário "OK (119 tests, 387 assertions)".</span>
                    </label>
                    <label class="check-item">
                        <input type="checkbox" data-test-id="phpunit-arch-run">
                        <span class="check-text">Executar <code>php phpunit.phar tests/Architecture</code> e obter "OK (13 tests, 16 assertions)".</span>
                    </label>
                </div>
            </div>
        </section>
    </main>

    <script>
        // GERENCIAMENTO DO CHECKLIST VIA LOCALSTORAGE
        const CHECKLIST_STORAGE_KEY = 'ds_manual_checklist_state';

        function loadChecklistState() {
            try {
                const saved = JSON.parse(localStorage.getItem(CHECKLIST_STORAGE_KEY)) || {};
                const checkboxes = document.querySelectorAll('input[type="checkbox"][data-test-id]');
                let checkedCount = 0;

                checkboxes.forEach(cb => {
                    const id = cb.getAttribute('data-test-id');
                    if (saved[id] === true) {
                        cb.checked = true;
                        checkedCount++;
                    } else {
                        cb.checked = false;
                    }
                });

                updateProgressBar(checkedCount, checkboxes.length);
            } catch (e) {
                console.error('Erro ao ler estado do checklist', e);
            }
        }

        function saveChecklistState() {
            try {
                const state = {};
                const checkboxes = document.querySelectorAll('input[type="checkbox"][data-test-id]');
                let checkedCount = 0;

                checkboxes.forEach(cb => {
                    const id = cb.getAttribute('data-test-id');
                    state[id] = cb.checked;
                    if (cb.checked) checkedCount++;
                });

                localStorage.setItem(CHECKLIST_STORAGE_KEY, JSON.stringify(state));
                updateProgressBar(checkedCount, checkboxes.length);
            } catch (e) {
                console.error('Erro ao salvar estado do checklist', e);
            }
        }

        function updateProgressBar(checked, total) {
            if (total === 0) return;
            const pct = Math.round((checked / total) * 100);
            const fill = document.getElementById('progressBar');
            const txt = document.getElementById('progressText');
            if (fill) fill.style.width = pct + '%';
            if (txt) txt.textContent = pct + '% (' + checked + '/' + total + ')';
        }

        function resetChecklist() {
            if (confirm('Deseja realmente reiniciar todo o checklist de testes?')) {
                localStorage.removeItem(CHECKLIST_STORAGE_KEY);
                loadChecklistState();
            }
        }

        // BUSCA INSTANTÂNEA NO ÍNDICE E CONTEÚDO
        document.getElementById('manualSearch')?.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const sections = document.querySelectorAll('.manual-section');
            const navItems = document.querySelectorAll('#sidebarNav .nav-item');

            sections.forEach(sec => {
                const text = sec.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    sec.style.display = 'block';
                } else {
                    sec.style.display = 'none';
                }
            });

            navItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (!query || text.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // HIGHLIGHT DO ITEM ATIVO NO SCROLL
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('.manual-section');
            let current = '';

            sections.forEach(sec => {
                const top = sec.offsetTop - 120;
                if (window.pageYOffset >= top) {
                    current = sec.getAttribute('id');
                }
            });

            if (current) {
                document.querySelectorAll('#sidebarNav .nav-item').forEach(item => {
                    item.classList.remove('active');
                    if (item.getAttribute('href') === '#' + current) {
                        item.classList.add('active');
                    }
                });
            }
        });

        // BOTÃO DE COPIAR CÓDIGO
        function copyCode(btn) {
            const box = btn.closest('.code-box');
            if (!box) return;
            // Pega o texto ignorando o botão
            const clone = box.cloneNode(true);
            const copyBtn = clone.querySelector('.btn-copy');
            if (copyBtn) copyBtn.remove();
            const code = clone.textContent.trim();

            navigator.clipboard.writeText(code).then(() => {
                const originalText = btn.textContent;
                btn.textContent = '✓ Copiado!';
                btn.style.color = '#10b981';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.color = '';
                }, 2000);
            });
        }

        // INICIALIZAÇÃO
        document.addEventListener('DOMContentLoaded', () => {
            loadChecklistState();
            document.querySelectorAll('input[type="checkbox"][data-test-id]').forEach(cb => {
                cb.addEventListener('change', saveChecklistState);
            });
        });
    </script>
</body>
</html>
