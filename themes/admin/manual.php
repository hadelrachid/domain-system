<?php
/**
 * View do Manual de Instruções & Testabilidade no Painel Admin.
 */
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$version = defined('DS_VERSION') ? DS_VERSION : '2.3.0';
if (!isset($pluginManuals) || !is_array($pluginManuals)) {
    $scanner = new \DomainSystem\Core\Manual\PluginManualScanner();
    $pluginManuals = $scanner->discover();
}
?>
<div class="wrap" style="max-width: 1200px;">
    <!-- HEADER DO MANUAL -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; flex-wrap: wrap;">
                <span style="background: rgba(6, 182, 212, 0.15); color: #06b6d4; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 20px; border: 1px solid rgba(6, 182, 212, 0.3); text-transform: uppercase;">
                    Documentação & QA
                </span>
                <span style="background: #2563eb; color: #fff; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 12px; font-family: monospace;">
                    v<?= htmlspecialchars($version) ?>
                </span>
                <span style="background: rgba(168, 85, 247, 0.15); color: #c084fc; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; border: 1px solid rgba(168, 85, 247, 0.35);">
                    🔌 Auto-Descoberta Ativa: <?= count($pluginManuals) ?> módulo(s) <code>manual/</code> conectado(s) via <code>ManualModuleInterface</code>
                </span>
            </div>
            <h1 style="margin: 0; font-size: 26px; color: #fff; font-weight: 700;">Manual de Instruções e Testabilidade</h1>
            <p style="color: var(--text-muted); margin: 5px 0 0; font-size: 14px;">
                Guia prático de validação do Kernel e módulos carregados dinamicamente das pastas <code>manual/</code> de cada plugin.
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
        <a href="<?= $baseUrl ?>/admin/manual#item-instalador" style="background: var(--bg-panel); border: 1px solid var(--border); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='var(--accent-blue)'" onmouseout="this.style.borderColor='var(--border)'">
            <div style="color: #06b6d4; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">Capítulo 5</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Instalador & Hostinger</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Pastas runtime, .htaccess e tolerância a erros.</div>
        </a>
        <a href="<?= $baseUrl ?>/admin/manual#item-ai-contracts" style="background: var(--bg-panel); border: 1px solid rgba(0, 210, 132, 0.4); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='#00d284'" onmouseout="this.style.borderColor='rgba(0, 210, 132, 0.4)'">
            <div style="color: #00d284; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">🤖 Capítulo 6 • IA</div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;">Vibe Coding on Rails (IAs)</div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;">Prompt mestre de Contratos SOLID para ChatGPT, Claude, DeepSeek e Copilot.</div>
        </a>
        <?php foreach ($pluginManuals as $mKey => $manualMod): 
            if (!$manualMod instanceof \DomainSystem\Core\Contracts\ManualModuleInterface) continue;
        ?>
        <a href="<?= $baseUrl ?>/admin/manual#item-<?= htmlspecialchars($manualMod->getId()) ?>" style="background: var(--bg-panel); border: 1px solid rgba(168, 85, 247, 0.35); border-radius: 8px; padding: 15px; text-decoration: none; color: inherit; transition: all 0.2s; display: block;" onmouseover="this.style.borderColor='<?= htmlspecialchars($manualMod->getAccentColor()) ?>'" onmouseout="this.style.borderColor='rgba(168, 85, 247, 0.35)'">
            <div style="color: <?= htmlspecialchars($manualMod->getAccentColor()) ?>; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 4px;">🔌 <?= htmlspecialchars($manualMod->getBadge()) ?></div>
            <div style="font-weight: 600; color: #fff; font-size: 15px;"><?= htmlspecialchars($manualMod->getTitle()) ?></div>
            <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px;"><?= htmlspecialchars($manualMod->getSubtitle()) ?></div>
        </a>
        <?php endforeach; ?>
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

        <h3 style="color: #45f3ff; font-size: 15px; margin: 0 0 10px;">Como Funciona o Ciclo de Menus no Domain System (Estilo WordPress & SOLID)</h3>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6; margin-bottom: 15px;">
            O sistema divide a navegação em <strong>3 etapas desacopladas</strong>:
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 15px; margin-bottom: 20px;">
            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: 6px; padding: 12px 15px;">
                <div style="color: #06b6d4; font-weight: 700; font-size: 13px; margin-bottom: 4px;">1. As Páginas & Templates</div>
                <div style="color: var(--text-muted); font-size: 12px; line-height: 1.5;">
                    Cada página (Início, Blog, Sobre, Tutoriais) tem seu <em>slug</em> e seu arquivo PHP de template (ex: <code>templates/home.php</code>).
                </div>
            </div>
            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: 6px; padding: 12px 15px;">
                <div style="color: #00d284; font-weight: 700; font-size: 13px; margin-bottom: 4px;">2. A Estrutura do Menu</div>
                <div style="color: var(--text-muted); font-size: 12px; line-height: 1.5;">
                    Em <code>/admin/menus</code>, você monta a árvore para um local (Header/Footer), arrasta para reordenar e cria submenus/dropdowns.
                </div>
            </div>
            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: 6px; padding: 12px 15px;">
                <div style="color: #8b5cf6; font-weight: 700; font-size: 13px; margin-bottom: 4px;">3. Exibição Pública</div>
                <div style="color: var(--text-muted); font-size: 12px; line-height: 1.5;">
                    O tema renderiza o menu via shortcode <code>[nav_menu location="header"]</code>, gerando HTML semântico com classes ativas.
                </div>
            </div>
        </div>

        <h4 style="color: #fff; font-size: 14px; margin: 15px 0 8px;">Tutorial Passo a Passo de Operação:</h4>
        <ol style="color: var(--text-main); font-size: 13px; line-height: 1.8; margin-left: 20px; margin-bottom: 15px;">
            <li><strong>Selecione o Menu:</strong> No topo de <code>/admin/menus</code>, escolha <em>Menu Principal (Header)</em> ou <em>Menu Rodapé (Footer)</em> e clique em <em>Selecionar</em>.</li>
            <li><strong>Adicione Itens:</strong> Na coluna esquerda ("Páginas Disponíveis"), marque as páginas desejadas e clique em <em>"Adicionar ao Menu"</em>. Para links externos (redes sociais), use o acordeão <em>"Links Personalizados"</em>.</li>
            <li><strong>Organize a Ordem e Crie Submenus:</strong> Arraste os cards para cima/baixo para ordenar. Arraste ligeiramente para a direita para transformar um item em <strong>subitem</strong> (criando um menu dropdown).</li>
            <li><strong>Salve com Atomicidade:</strong> Clique em <em>"Salvar Itens"</em>. O sistema dispara uma requisição segura via transação PDO no banco (sem risco de apagar itens caso ocorra erro).</li>
            <li><strong>Renderize no Tema:</strong> Use o shortcode <code>[nav_menu location="header"]</code> no cabeçalho ou nas páginas para exibir a navegação pública.</li>
        </ol>

        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid var(--accent-green); padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Blindagem de Arquitetura (SOLID):</strong> O módulo de menus nunca consulta diretamente a tabela de páginas (DIP); ele conversa exclusivamente através da interface <code>PageLookupInterface</code>. Em caso de falha no salvamento, o <code>PdoMenuRepository</code> realiza um <code>ROLLBACK</code> automático preservando o menu anterior intacto.
        </div>

        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-load-pages">
                <span style="font-size: 13px;">Lista de páginas pronta para seleção no painel de menus.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-add-page-item">
                <span style="font-size: 13px;">Adicionar itens de página na coluna da direita e organizar hierarquia.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-save-atomic">
                <span style="font-size: 13px;">Salvar itens do menu completa com notificação verde de sucesso.</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="menu-shortcode">
                <span style="font-size: 13px;">Shortcode <code>[nav_menu location="header"]</code> gera o HTML do menu na visualização pública.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 5: INSTALADOR & RUNTIME -->
    <div id="item-instalador" class="postbox" style="margin-bottom: 30px; padding: 25px;">
        <div style="border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px;">
            <h2 style="margin: 0; font-size: 18px; color: #fff;">5. Instalador & Runtime Agnóstico (XAMPP / Hostinger)</h2>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.6;">
            <strong>O que foi solucionado:</strong> Resolução 100% matemática e agnóstica de <code>BASE_URL</code> em <code>public/index.php</code>, pastas de runtime protegidas (<code>temp/</code>, <code>storage/</code>) com <code>.htaccess</code> e estado inicial limpo <code>SITE_STATUS=coming_soon</code>.
        </p>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação</div>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="runtime-folders">
                <span style="font-size: 13px;">Diretórios de runtime e segurança física .htaccess ativos no servidor.</span>
            </label>
        </div>
    </div>

    <!-- CAPÍTULO 6: VIBE CODING ON RAILS (GUIA OFICIAL PARA IAs) -->
    <?php
        $aiGuidePath = (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 2)) . '/AI_CONTRACT_GUIDE.md';
        $aiGuideContent = is_file($aiGuidePath) ? (string)file_get_contents($aiGuidePath) : '';
    ?>
    <div id="item-ai-contracts" class="postbox" style="margin-bottom: 30px; padding: 25px; border: 1px solid rgba(0, 210, 132, 0.4);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div>
                <span style="display: inline-block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #00d284; margin-bottom: 4px;">
                    🤖 DX para Inteligência Artificial • AI_CONTRACT_GUIDE.md
                </span>
                <h2 style="margin: 0; font-size: 19px; color: #fff;">6. Vibe Coding sobre Trilhos: Contratos para IAs (ChatGPT, Claude, DeepSeek, Copilot, Cursor)</h2>
            </div>
            <button type="button" class="button button-primary" onclick="copyAiMasterPrompt(this)" style="background: #00d284; border-color: #00d284; color: #090d16; font-weight: 700; font-size: 12px;">
                📋 Copiar Prompt Mestre para IA
            </button>
        </div>
        <p style="color: var(--text-main); font-size: 14px; line-height: 1.7;">
            O <strong>Domain-System OS</strong> permite que qualquer desenvolvedor programe em alta velocidade com auxílio de IA (<em>Vibe Coding</em>) sem jamais gerar código espaguete. Os arquivos <code>AI_CONTRACT_GUIDE.md</code>, <code>.github/copilot-instructions.md</code>, <code>.cursorrules</code> e <code>.clinerules</code> na raiz do projeto funcionam como os <strong>trilhos do trem</strong>: eles obrigam qualquer LLM a respeitar a separação <strong>Ring 0 / Ring 3</strong>, a injeção de dependência pura por interfaces (<code>Contracts/</code>), zero superglobais nos Controllers e auto-descoberta via <code>ManualModuleInterface</code>.
        </p>
        <div style="background: rgba(0, 210, 132, 0.08); border-left: 4px solid #00d284; padding: 12px 16px; margin: 15px 0; border-radius: 0 4px 4px 0; font-size: 13px;">
            <strong>Scaffolding Instantâneo via Terminal (OS-CLI):</strong> Execute <code>php os-cli.php make:plugin nome_do_plugin</code> para gerar automaticamente toda a árvore de pastas Ring 3 com <code>Plugin.php</code> (<code>OsExtensionInterface</code>), <code>Contracts/</code>, <code>Controllers/</code>, <code>views/</code> e <code>manual/module.php</code> (<code>ManualModuleInterface</code>).
        </div>
        <textarea id="ds-ai-master-prompt" style="display:none;"><?= htmlspecialchars($aiGuideContent) ?></textarea>
        <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação — IA &amp; Contratos</div>
            <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                <input type="checkbox" data-test-id="ai-contract-guide-copy">
                <span style="font-size: 13px;">Clicar em "📋 Copiar Prompt Mestre para IA" e validar a cópia das 10 Leis do Ring 3 (<code>AI_CONTRACT_GUIDE.md</code>).</span>
            </label>
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" data-test-id="ai-cli-scaffold">
                <span style="font-size: 13px;">Validar geração de esqueleto de plugin via <code>php os-cli.php make:plugin</code> respeitando os contratos SOLID.</span>
            </label>
        </div>
    </div>

    <!-- MÓDULOS DE INSTRUÇÃO DE PLUGINS (AUTO-DESCOBERTOS VIA CONTRATO ManualModuleInterface) -->
    <?php foreach ($pluginManuals as $mKey => $manualMod):
        if (!$manualMod instanceof \DomainSystem\Core\Contracts\ManualModuleInterface) continue;
        $modId = $manualMod->getId();
        $adminUrl = $manualMod->getAdminUrl();
        $checkItems = $manualMod->getChecklistItems();
    ?>
    <div id="item-<?= htmlspecialchars($modId) ?>" class="postbox" style="margin-bottom: 30px; padding: 25px; border: 1px solid rgba(168, 85, 247, 0.35);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
            <div>
                <span style="display: inline-block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: <?= htmlspecialchars($manualMod->getAccentColor()) ?>; margin-bottom: 4px;">
                    🔌 <?= htmlspecialchars($manualMod->getBadge()) ?>
                </span>
                <h2 style="margin: 0; font-size: 19px; color: #fff;"><?= htmlspecialchars($manualMod->getTitle()) ?></h2>
            </div>
            <?php if ($adminUrl): ?>
                <a href="<?= $baseUrl . htmlspecialchars($adminUrl) ?>" class="button" style="font-size: 12px;">Abrir Painel do Plugin &rarr;</a>
            <?php endif; ?>
        </div>

        <?= $manualMod->renderContent($baseUrl) ?>

        <?php if (!empty($checkItems)): ?>
            <div style="background: rgba(0,0,0,0.3); border: 1px solid var(--border); border-radius: 6px; padding: 15px; margin-top: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">Checklist de Validação do Módulo</div>
                <?php foreach ($checkItems as $item): ?>
                    <label style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; cursor: pointer;">
                        <input type="checkbox" data-test-id="<?= htmlspecialchars((string)$item['id']) ?>">
                        <span style="font-size: 13px;"><?= htmlspecialchars((string)$item['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
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

    function copyAiMasterPrompt(btn) {
        const el = document.getElementById('ds-ai-master-prompt');
        if (!el) return;
        navigator.clipboard.writeText(el.value).then(() => {
            const orig = btn.textContent;
            btn.textContent = '✓ Prompt Mestre Copiado!';
            setTimeout(() => { btn.textContent = orig; }, 2200);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadAdminChecklist();
        document.querySelectorAll('input[type="checkbox"][data-test-id]').forEach(cb => {
            cb.addEventListener('change', saveAdminChecklist);
        });
    });
</script>
