<?php
/**
 * View do Manual de Instruções & Testabilidade no Painel Admin.
 */
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$version = defined('DS_VERSION') ? DS_VERSION : '2.2.0';
?>
<div class="wrap" style="max-width: 1200px;">
    <!-- HEADER DO MANUAL -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                <span style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(6, 182, 212, 0.3); text-transform: uppercase;">
                    Documentação & QA
                </span>
                <span style="background: #2563eb; color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-family: monospace;">
                    v<?= htmlspecialchars($version) ?>
                </span>
            </div>
            <h1 style="margin: 0; font-size: 26px; color: #fff; font-weight: 700;">Manual de Instruções e Testabilidade</h1>
            <p style="color: var(--text-muted); margin: 5px 0 0; font-size: 14px;">
                Guia prático de validação, atalhos do sistema e provas de resolutividade para cada módulo.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= $baseUrl ?>/manual" target="_blank" class="button button-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                Abrir Manual em Tela Cheia ↗
            </a>
            <button onclick="resetAdminChecklist()" class="button" style="background: rgba(255,255,255,0.06); border-color: var(--border);">
                Resetar Checklist
            </button>
        </div>
    </div>

    <!-- BARRA DE PROGRESSO DE TESTES -->
    <div style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 16px 20px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 13px; font-weight: 600; color: #fff;">Status da Validação dos Atalhos:</span>
            <span id="adminProgressText" style="font-size: 13px; font-weight: 700; color: var(--accent-green); font-family: monospace;">0%</span>
        </div>
        <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
            <div id="adminProgressBar" style="height: 100%; width: 0%; background: linear-gradient(90deg, #06b6d4, #00d284); border-radius: 4px; transition: width 0.3s ease;"></div>
        </div>
    </div>

    <!-- ÍNDICE NAVEGÁVEL RÁPIDO (GRID) -->
    <h2 style="font-size: 16px; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px;">Índice Rápido dos Módulos</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 40px;">
        <a href="<?= $baseUrl ?>/admin/manual#item-temas" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: #45f3ff; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 1</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Gerenciador de Temas</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Live Preview flutuante & ativação em 1 clique.</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-logout" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: var(--accent-orange); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 2</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Logout e Anti-Cache</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Eliminação de telas em cache (LiteSpeed/Hostinger).</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-estado" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: #eab308; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 3</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Estado do Site (Em Breve)</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Transição sem 404 e proteção SEO noindex.</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-menus" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: var(--accent-green); font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 4</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Menus & Páginas</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Associação de templates e transações atômicas.</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-academy" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: #a855f7; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 5</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Plugin Academy (Cursos)</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Validação 16:9, finfo e slugs acentuados.</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-instalador" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: #06b6d4; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 6</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Instalador & Hostinger</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Pastas runtime, .htaccess e tolerância a erros.</div>
        </a>
    </div>

    <!-- DETALHAMENTO DE CADA CAPÍTULO -->

    <!-- CAPÍTULO 1: TEMAS -->
    <div id="item-temas" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">1. Gerenciador de Temas & Live Preview</h2>
            <a href="<?= $baseUrl ?>/admin/themes" class="button" style="font-size: 12px;">Ir para Temas →</a>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> O botão "Ver" dos temas inativos agora abre uma rota dedicada de pré-visualização (<code>/admin/themes/preview?theme=nome</code>) isolada da produção.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Como provar que funciona:</strong> Ao clicar em "Ver" em um tema inativo, observe a barra flutuante preta no topo. O tema renderizado é o selecionado. Clicar em "✓ Ativar este Tema" ativa-o no sistema com token CSRF seguro.
        </div>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="theme-card-buttons">
                <span style="font-size: 13px;">Card do tema ativo exibe "Ver Site ↗" e os inativos exibem "Ver" e "Ativar".</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="theme-preview-topbar">
                <span style="font-size: 13px;">Ao clicar em "Ver", o tema abre com a barra superior de preview isolada.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="theme-1click-activate">
                <span style="font-size: 13px;">Botão de ativação rápida na barra superior atualiza o tema ativo.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 2: LOGOUT & CACHE -->
    <div id="item-logout" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">2. Logout e Blindagem Anti-Cache</h2>
            <a href="<?= $baseUrl ?>/logout" class="button" style="font-size: 12px; color: var(--accent-orange) !important;">Testar Logout →</a>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> O logout agora expira os cookies com timestamp no passado, limpa <code>$_SESSION = []</code> e envia <code>Cache-Control: no-store</code> e <code>Clear-Site-Data</code>.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Como provar que funciona:</strong> Após clicar em "Encerrar Sessão", tente clicar no botão de "Voltar" do navegador. O navegador é forçado a revalidar e redirecionar para o login, sem jamais reabrir a tela do painel em cache.
        </div>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="logout-browser-back">
                <span style="font-size: 13px;">Após logout, o botão Voltar do navegador não exibe dados do admin.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="logout-direct-url">
                <span style="font-size: 13px;">Digitar <code>/admin</code> diretamente na barra de URL redireciona para a tela de login.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 3: ESTADO DO SITE -->
    <div id="item-estado" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">3. Estado do Site ("Em Breve" / coming_soon)</h2>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> Instalações novas iniciam com <code>SITE_STATUS=coming_soon</code>, impedindo erros 404 e protegendo a indexação nos mecanismos de busca.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Como provar que funciona:</strong> Ao abrir a home do site <code>/</code> com o modo coming_soon ativado, uma página amigável é retornada em HTTP 200 com <code>X-Robots-Tag: noindex, nofollow</code>.
        </div>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="state-coming-soon">
                <span style="font-size: 13px;">Home pública exibe tela de "Em Breve" sem quebrar o sistema.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 4: MENUS E PÁGINAS -->
    <div id="item-menus" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">4. Gerenciador de Menus & Associação com Páginas</h2>
            <a href="<?= $baseUrl ?>/admin/menus" class="button" style="font-size: 12px;">Ir para Menus →</a>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> A persistência de itens do menu foi blindada com transação atômica PDO e desacoplada da tabela de páginas através da interface <code>PageLookupInterface</code>.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Como provar que funciona:</strong> Acesse <code>/admin/menus</code> e verifique que as páginas prontas (Início, Blog, Sobre, Termos, Privacidade, Tutoriais) estão listadas. Adicionar itens e salvar grava sem perda de dados e sem falhas de CSRF.
        </div>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-load-pages">
                <span style="font-size: 13px;">Lista de páginas pronta para seleção no painel de menus.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-save-atomic">
                <span style="font-size: 13px;">Salvar itens do menu completa com mensagem verde de sucesso.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 5: ACADEMY -->
    <div id="item-academy" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">5. Plugin Academy (Cursos & Cards 16:9)</h2>
            <a href="<?= $baseUrl ?>/admin/academy" class="button" style="font-size: 12px;">Ir para Academy →</a>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> CRUD completo (rotas de edição e update adicionadas), upload com validação de proporção 16:9 via <code>finfo</code> e remoção automática de imagens órfãs ao substituir capa.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Como provar que funciona:</strong> Crie um curso com título acentuado (ex: *"Gestão & Inovação Tecnológica"*). O slug será limpo (<code>gestao-inovacao-tecnologica</code>). Teste a edição na rota <code>/admin/academy/edit/{id}</code>.
        </div>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="academy-create-accent">
                <span style="font-size: 13px;">Criação de curso gera slug sem acentos único no banco de dados.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="academy-edit-course">
                <span style="font-size: 13px;">Edição e atualização do curso conclui sem erros.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 6: INSTALADOR & RUNTIME -->
    <div id="item-instalador" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">6. Instalador & Runtime Hostinger</h2>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> Pastas de runtime protegidas (<code>temp/</code>, <code>storage/</code>) com <code>.htaccess</code> e tolerância a avisos do PHP no Hostinger.
        </p>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="runtime-folders">
                <span style="font-size: 13px;">Diretórios de runtime e segurança física .htaccess ativos no servidor.</span>
            </label>
        </div>
    </div>
</div>

<script>
    const ADMIN_CHECKLIST_KEY = 'ds_manual_checklist_state';

    function loadAdminChecklist() {
        try {
            const saved = JSON.parse(localStorage.getItem(ADMIN_CHECKLIST_KEY)) || {};
            const checkboxes = document.querySelectorAll('input[type="checkbox"][data-test-id]');
            let checked = 0;

            checkboxes.forEach(cb => {
                const id = cb.getAttribute('data-test-id');
                if (saved[id] === true) {
                    cb.checked = true;
                    checked++;
                } else {
                    cb.checked = false;
                }
            });

            updateAdminBar(checked, checkboxes.length);
        } catch (e) {
            console.error(e);
        }
    }

    function saveAdminChecklist() {
        try {
            const state = {};
            const checkboxes = document.querySelectorAll('input[type="checkbox"][data-test-id]');
            let checked = 0;

            checkboxes.forEach(cb => {
                const id = cb.getAttribute('data-test-id');
                state[id] = cb.checked;
                if (cb.checked) checked++;
            });

            localStorage.setItem(ADMIN_CHECKLIST_KEY, JSON.stringify(state));
            updateAdminBar(checked, checkboxes.length);
        } catch (e) {
            console.error(e);
        }
    }

    function updateAdminBar(checked, total) {
        if (total === 0) return;
        const pct = Math.round((checked / total) * 100);
        const fill = document.getElementById('adminProgressBar');
        const txt = document.getElementById('adminProgressText');
        if (fill) fill.style.width = pct + '%';
        if (txt) txt.textContent = pct + '% (' + checked + '/' + total + ' itens validados)';
    }

    function resetAdminChecklist() {
        if (confirm('Deseja reiniciar as validações do checklist?')) {
            localStorage.removeItem(ADMIN_CHECKLIST_KEY);
            loadAdminChecklist();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadAdminChecklist();
        document.querySelectorAll('input[type="checkbox"][data-test-id]').forEach(cb => {
            cb.addEventListener('change', saveAdminChecklist);
        });
    });
</script>
