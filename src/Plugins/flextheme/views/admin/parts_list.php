<?php
/**
 * View: Lista de Partes de Tema do Flex Builder
 * 
 * @var array $headers
 * @var array $footers
 * @var array $sections
 * @var array $themes
 * @var string $activeTheme
 * @var array $assignments
 * @var ?string $globalHeader
 * @var ?string $globalFooter
 * @var string $baseUrl
 * @var ?\DomainSystem\Core\Contracts\SessionManagerInterface $session
 */
?>

<div class="wrap" style="max-width: 1300px; margin: 0 auto; padding: 25px 20px;">
    <!-- HEADER DA PÁGINA -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; letter-spacing: -0.5px;">Flex Builder</h1>
                <span style="background: rgba(69, 243, 255, 0.12); color: #45f3ff; border: 1px solid rgba(69, 243, 255, 0.3); font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase;">
                    Motor em Blocos FSE
                </span>
            </div>
            <p style="margin: 0; font-size: 14px; color: var(--text-muted, #8b949e);">
                Componha Headers, Footers e Seções em divisórias responsivas com simulação multi-dispositivo e contratos SOLID.
            </p>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="<?= $baseUrl ?>/admin/builder/tokens" class="btn" style="background: rgba(147, 51, 234, 0.15); border: 1px solid rgba(147, 51, 234, 0.4); color: #c084fc; padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                🎨 Design Tokens (Cores & Fontes)
            </a>
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="document.getElementById('dropdownCreate').classList.toggle('show');" class="btn button-primary" style="background: #2563eb; color: #fff; border: 1px solid #1d4ed8; padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    + Criar Nova Parte ▾
                </button>
                <div id="dropdownCreate" style="display: none; position: absolute; right: 0; top: 110%; background: var(--bg-panel, #161b22); border: 1px solid var(--border, #30363d); border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); min-width: 200px; z-index: 100; overflow: hidden;">
                    <a href="<?= $baseUrl ?>/admin/builder/create?type=header" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #fff; text-decoration: none; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        🛡️ Novo Header
                    </a>
                    <a href="<?= $baseUrl ?>/admin/builder/create?type=footer" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #fff; text-decoration: none; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                        ⚓ Novo Footer
                    </a>
                    <a href="<?= $baseUrl ?>/admin/builder/create?type=section" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #fff; text-decoration: none; font-size: 13px;">
                        🧩 Nova Seção Central
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    <?php 
        $flash = $session ? $session->getFlash() : null;
        if ($flash && !empty($flash['msg'])): 
            $isSuccess = ($flash['type'] ?? 'info') === 'success';
            $bgColor = $isSuccess ? 'rgba(0, 210, 132, 0.15)' : 'rgba(239, 68, 68, 0.15)';
            $borderColor = $isSuccess ? '#00d284' : '#ef4444';
            $textColor = $isSuccess ? '#00d284' : '#f87171';
            $icon = $isSuccess ? '✓' : '✕';
    ?>
        <div style="background: <?= $bgColor ?>; border: 1px solid <?= $borderColor ?>; color: <?= $textColor ?>; padding: 12px 18px; border-radius: 6px; margin-bottom: 20px; font-size: 14px;">
            <?= $icon ?> <?= htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- CARD INFORMATIVO: STATUS DO TEMA ATIVO E REDE DE SEGURANÇA -->
    <div style="background: var(--bg-panel, #161b22); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 16px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 42px; height: 42px; border-radius: 8px; background: rgba(0, 210, 132, 0.15); border: 1px solid rgba(0, 210, 132, 0.3); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                🎨
            </div>
            <div>
                <div style="font-size: 13px; color: var(--text-muted, #8b949e);">Tema Ativo no Domínio:</div>
                <div style="font-size: 16px; font-weight: 700; color: #fff;">
                    <?= htmlspecialchars(ucfirst($activeTheme)) ?> <code style="font-size: 12px; font-weight: normal; color: var(--text-muted);">(<?= htmlspecialchars($activeTheme) ?>)</code>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px; font-size: 13px;">
            <div>
                <span style="color: var(--text-muted);">Header Ativo:</span>
                <strong style="color: <?= !empty($assignments[$activeTheme]['header']) ? '#00d284' : '#58a6ff' ?>;">
                    <?= !empty($assignments[$activeTheme]['header']) ? 'Flex Builder Dinâmico' : 'Nativo do Tema (Fallback)' ?>
                </strong>
            </div>
            <div>
                <span style="color: var(--text-muted);">Footer Ativo:</span>
                <strong style="color: <?= !empty($assignments[$activeTheme]['footer']) ? '#00d284' : '#58a6ff' ?>;">
                    <?= !empty($assignments[$activeTheme]['footer']) ? 'Flex Builder Dinâmico' : 'Nativo do Tema (Fallback)' ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- SEÇÃO 1: HEADERS -->
    <div style="margin-bottom: 35px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--border, #30363d); padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🛡️</span>
                <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #fff;">Cabeçalhos (Headers)</h2>
                <span style="background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 11px; color: var(--text-muted);"><?= count($headers) ?></span>
            </div>
            <a href="<?= $baseUrl ?>/admin/builder/create?type=header" style="font-size: 13px; color: var(--accent-blue, #58a6ff); text-decoration: none; font-weight: 600;">+ Adicionar Header</a>
        </div>

        <?php if (empty($headers)): ?>
            <div style="background: var(--bg-panel, #161b22); border: 2px dashed var(--border, #30363d); border-radius: 8px; padding: 30px; text-align: center; color: var(--text-muted);">
                <p style="margin: 0 0 12px; font-size: 14px;">Nenhum Header customizado criado ainda. O sistema está usando o <code>partials/header.php</code> nativo do tema.</p>
                <a href="<?= $baseUrl ?>/admin/builder/create?type=header" class="btn" style="background: #2563eb; color: #fff; padding: 7px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">+ Criar Primeiro Header</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px;">
                <?php foreach ($headers as $h): ?>
                    <?php 
                        $isCurrentThemeHeader = isset($assignments[$activeTheme]['header']) && $assignments[$activeTheme]['header'] === $h->getId();
                        $isGlobalDefault = ($globalHeader === $h->getId());
                    ?>
                    <div style="background: var(--bg-panel, #161b22); border: 1px solid <?= $isCurrentThemeHeader ? '#00d284' : 'var(--border, #30363d)' ?>; border-radius: 8px; padding: 18px; display: flex; flex-direction: column; position: relative;">
                        
                        <?php if ($isCurrentThemeHeader): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #00d284; color: #000; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                ✓ Ativo no Tema
                            </span>
                        <?php elseif ($isGlobalDefault): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #fbbf24; color: #000; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                ⭐ Padrão Global
                            </span>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">🛡️</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #fff;"><?= htmlspecialchars($h->getTitle()) ?></h3>
                        </div>

                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px;">
                            <span>Grade: <code><?= htmlspecialchars($h->getLayoutPreset()) ?></code></span>
                            <span>•</span>
                            <span>Toggle: <code><?= htmlspecialchars($h->getMobileToggleStyle()) ?></code></span>
                            <span>•</span>
                            <span>ID: <code><?= htmlspecialchars(substr($h->getId(), 0, 10)) ?>...</code></span>
                        </div>

                        <!-- Formulário de Atribuição Rápida -->
                        <form method="POST" action="<?= $baseUrl ?>/admin/builder/assign" style="margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                            <input type="hidden" name="part_type" value="header">
                            <input type="hidden" name="part_id" value="<?= htmlspecialchars($h->getId()) ?>">
                            
                            <select name="theme_folder" style="flex-grow: 1; background: var(--bg-deep, #0d1117); color: #fff; border: 1px solid var(--border, #30363d); padding: 5px 8px; border-radius: 4px; font-size: 12px;">
                                <option value="<?= htmlspecialchars($activeTheme) ?>">Tema Ativo (<?= htmlspecialchars($activeTheme) ?>)</option>
                                <?php foreach ($themes as $th): ?>
                                    <?php if ($th['folder'] !== $activeTheme): ?>
                                        <option value="<?= htmlspecialchars($th['folder']) ?>"><?= htmlspecialchars($th['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn" style="background: rgba(255,255,255,0.08); color: #fff; border: 1px solid var(--border); padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                Atribuir
                            </button>

                            <a href="<?= $baseUrl ?>/admin/builder/edit?id=<?= urlencode($h->getId()) ?>" class="btn button-primary" style="background: #2563eb; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                Editar
                            </a>

                            <button type="button" onclick="deletePart('<?= htmlspecialchars($h->getId()) ?>')" style="background: transparent; border: none; color: #ef4444; font-size: 14px; cursor: pointer; padding: 4px;" title="Excluir">
                                🗑️
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SEÇÃO 2: FOOTERS -->
    <div style="margin-bottom: 35px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--border, #30363d); padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">⚓</span>
                <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #fff;">Rodapés (Footers)</h2>
                <span style="background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 11px; color: var(--text-muted);"><?= count($footers) ?></span>
            </div>
            <a href="<?= $baseUrl ?>/admin/builder/create?type=footer" style="font-size: 13px; color: var(--accent-blue, #58a6ff); text-decoration: none; font-weight: 600;">+ Adicionar Footer</a>
        </div>

        <?php if (empty($footers)): ?>
            <div style="background: var(--bg-panel, #161b22); border: 2px dashed var(--border, #30363d); border-radius: 8px; padding: 30px; text-align: center; color: var(--text-muted);">
                <p style="margin: 0 0 12px; font-size: 14px;">Nenhum Footer customizado criado ainda. O sistema está usando o <code>partials/footer.php</code> nativo do tema.</p>
                <a href="<?= $baseUrl ?>/admin/builder/create?type=footer" class="btn" style="background: #2563eb; color: #fff; padding: 7px 16px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600;">+ Criar Primeiro Footer</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px;">
                <?php foreach ($footers as $f): ?>
                    <?php 
                        $isCurrentThemeFooter = isset($assignments[$activeTheme]['footer']) && $assignments[$activeTheme]['footer'] === $f->getId();
                        $isGlobalDefault = ($globalFooter === $f->getId());
                    ?>
                    <div style="background: var(--bg-panel, #161b22); border: 1px solid <?= $isCurrentThemeFooter ? '#00d284' : 'var(--border, #30363d)' ?>; border-radius: 8px; padding: 18px; display: flex; flex-direction: column; position: relative;">
                        
                        <?php if ($isCurrentThemeFooter): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #00d284; color: #000; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                ✓ Ativo no Tema
                            </span>
                        <?php elseif ($isGlobalDefault): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #fbbf24; color: #000; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                ⭐ Padrão Global
                            </span>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">⚓</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #fff;"><?= htmlspecialchars($f->getTitle()) ?></h3>
                        </div>

                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px; display: flex; flex-wrap: wrap; gap: 8px;">
                            <span>Grade: <code><?= htmlspecialchars($f->getLayoutPreset()) ?></code></span>
                            <span>•</span>
                            <span>ID: <code><?= htmlspecialchars(substr($f->getId(), 0, 10)) ?>...</code></span>
                        </div>

                        <form method="POST" action="<?= $baseUrl ?>/admin/builder/assign" style="margin-top: auto; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.06); display: flex; gap: 8px; align-items: center;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                            <input type="hidden" name="part_type" value="footer">
                            <input type="hidden" name="part_id" value="<?= htmlspecialchars($f->getId()) ?>">
                            
                            <select name="theme_folder" style="flex-grow: 1; background: var(--bg-deep, #0d1117); color: #fff; border: 1px solid var(--border, #30363d); padding: 5px 8px; border-radius: 4px; font-size: 12px;">
                                <option value="<?= htmlspecialchars($activeTheme) ?>">Tema Ativo (<?= htmlspecialchars($activeTheme) ?>)</option>
                                <?php foreach ($themes as $th): ?>
                                    <?php if ($th['folder'] !== $activeTheme): ?>
                                        <option value="<?= htmlspecialchars($th['folder']) ?>"><?= htmlspecialchars($th['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn" style="background: rgba(255,255,255,0.08); color: #fff; border: 1px solid var(--border); padding: 5px 10px; border-radius: 4px; font-size: 12px; cursor: pointer;">
                                Atribuir
                            </button>

                            <a href="<?= $baseUrl ?>/admin/builder/edit?id=<?= urlencode($f->getId()) ?>" class="btn button-primary" style="background: #2563eb; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                Editar
                            </a>

                            <button type="button" onclick="deletePart('<?= htmlspecialchars($f->getId()) ?>')" style="background: transparent; border: none; color: #ef4444; font-size: 14px; cursor: pointer; padding: 4px;" title="Excluir">
                                🗑️
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- SEÇÃO 3: SEÇÕES E LAYOUTS -->
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; border-bottom: 1px solid var(--border, #30363d); padding-bottom: 10px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">🧩</span>
                <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #fff;">Seções & Layouts Centrais</h2>
                <span style="background: rgba(255,255,255,0.08); padding: 2px 8px; border-radius: 12px; font-size: 11px; color: var(--text-muted);"><?= count($sections) ?></span>
            </div>
            <a href="<?= $baseUrl ?>/admin/builder/create?type=section" style="font-size: 13px; color: var(--accent-blue, #58a6ff); text-decoration: none; font-weight: 600;">+ Adicionar Seção</a>
        </div>

        <?php if (empty($sections)): ?>
            <div style="background: var(--bg-panel, #161b22); border: 2px dashed var(--border, #30363d); border-radius: 8px; padding: 25px; text-align: center; color: var(--text-muted);">
                <p style="margin: 0 0 10px; font-size: 13px;">Crie seções modulares reutilizáveis para páginas ou templates através de blocos e divisórias.</p>
                <a href="<?= $baseUrl ?>/admin/builder/create?type=section" class="btn" style="background: rgba(255,255,255,0.08); color: #fff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 12px;">+ Nova Seção</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 18px;">
                <?php foreach ($sections as $s): ?>
                    <?php
                        // Descobre se esta seção está atribuída a algum template do tema ativo
                        $assignedTemplateKey = '';
                        foreach ($assignments[$activeTheme] ?? [] as $partKey => $assignedId) {
                            if ($assignedId === $s->getId() && str_starts_with($partKey, 'template:')) {
                                $assignedTemplateKey = substr($partKey, strlen('template:'));
                                break;
                            }
                        }
                    ?>
                    <div style="background: var(--bg-panel, #161b22); border: 1px solid <?= !empty($assignedTemplateKey) ? '#00d284' : 'var(--border, #30363d)' ?>; border-radius: 8px; padding: 18px; display: flex; flex-direction: column; position: relative;">
                        
                        <?php if (!empty($assignedTemplateKey)): ?>
                            <span style="position: absolute; top: 12px; right: 12px; background: #00d284; color: #000; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 12px; text-transform: uppercase;">
                                ✓ Ativo em <?= htmlspecialchars($assignedTemplateKey) ?>.php
                            </span>
                        <?php endif; ?>

                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                            <span style="font-size: 18px;">🧩</span>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #fff;"><?= htmlspecialchars($s->getTitle()) ?></h3>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 12px;">
                            <span>Grade: <code><?= htmlspecialchars($s->getLayoutPreset()) ?></code></span> •
                            <span>ID: <code><?= htmlspecialchars($s->getId()) ?></code></span>
                        </div>

                        <!-- 1. SEÇÃO DO CARD: SHORTCODE REUTILIZÁVEL (SEM ESPREMER, ESPAÇOSA E ELEGANTE) -->
                        <div style="background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Shortcode da Seção</span>
                                <span style="font-size: 10px; color: #38bdf8; background: rgba(56, 189, 248, 0.1); padding: 1px 6px; border-radius: 4px; font-weight: 600;">Uso em Páginas</span>
                            </div>
                            
                            <!-- Código em linha própria com largura total -->
                            <div style="background: #06090e; border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 8px 12px; margin-bottom: 8px; display: flex; align-items: center;">
                                <code style="font-size: 12px; color: #38bdf8; font-weight: 700; font-family: 'Fira Code', monospace; width: 100%; overflow-x: auto; white-space: nowrap; user-select: all;">[section id="<?= htmlspecialchars($s->getId()) ?>"]</code>
                            </div>

                            <!-- Botão em linha própria, largo, confortável e sem espremer -->
                            <button type="button" class="btn" onclick="copyShortcode('[section id=\'<?= htmlspecialchars($s->getId(), ENT_QUOTES) ?>\']', this)" style="width: 100%; background: #238636; color: #fff; border: 1px solid rgba(255,255,255,0.15); padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s ease;">
                                📋 Copiar Shortcode
                            </button>

                            <!-- Instrução explicativa com respiro e borda suave -->
                            <div style="font-size: 11px; color: var(--text-muted); line-height: 1.45; margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.05);">
                                💡 <strong>Como usar:</strong> Copie esse shortcode e cole no conteúdo da sua página (no <em>menu Páginas</em> que você preferir) para exibir este layout.
                            </div>
                        </div>

                        <!-- 2. SEÇÃO DO CARD: COMBOBOX DE ATRIBUIÇÃO AO TEMPLATE -->
                        <div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
                            <label style="display: block; font-size: 11px; font-weight: 700; color: #fff; margin-bottom: 6px;">
                                🎯 Atribuir Diretamente a um Template:
                            </label>
                            <select onchange="assignTemplateSection('<?= htmlspecialchars($s->getId(), ENT_QUOTES) ?>', this.value)" style="width: 100%; padding: 8px 10px; background: rgba(0,0,0,0.4); border: 1px solid var(--border, #30363d); color: #fff; border-radius: 6px; font-size: 12px;">
                                <option value="none">Nenhum (Usar livremente via Shortcode)</option>
                                <?php if (!empty($templates) && is_array($templates)): ?>
                                    <?php foreach ($templates as $tSlug => $tFile): ?>
                                        <?php 
                                            $isSelected = ($assignedTemplateKey === $tSlug 
                                                || $assignedTemplateKey === $tSlug . '.php' 
                                                || str_replace('.php', '', $assignedTemplateKey) === str_replace('.php', '', $tSlug)); 
                                        ?>
                                        <option value="<?= htmlspecialchars($tSlug) ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tFile) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <span style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 6px; line-height: 1.4;">
                                Ao selecionar um template, esta seção substituirá automaticamente o conteúdo central daquela página.
                            </span>
                        </div>

                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 12px;">
                            <span style="font-size: 11px; color: var(--text-muted);">Layout Modular</span>
                            <div style="display: flex; gap: 8px;">
                                <a href="<?= $baseUrl ?>/admin/builder/edit?id=<?= urlencode($s->getId()) ?>" class="btn button-primary" style="background: #2563eb; color: #fff; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 600;">
                                    ✏️ Editar
                                </a>
                                <button type="button" onclick="deletePart('<?= htmlspecialchars($s->getId()) ?>')" style="background: transparent; border: none; color: #ef4444; font-size: 14px; cursor: pointer; padding: 4px;" title="Excluir">
                                    🗑️
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyShortcode(text, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopyFeedback(btn);
        }).catch(function() {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try {
        document.execCommand('copy');
        showCopyFeedback(btn);
    } catch(e) {
        prompt('Copie o shortcode abaixo:', text);
    }
    document.body.removeChild(ta);
}

function showCopyFeedback(btn) {
    if (!btn) return;
    var origText = btn.innerHTML;
    btn.innerHTML = '✓ Copiado!';
    btn.style.background = '#10b981';
    btn.style.color = '#000';
    setTimeout(function() {
        btn.innerHTML = origText;
        btn.style.background = '#238636';
        btn.style.color = '#fff';
    }, 2000);
}

function assignTemplateSection(sectionId, templateSlug) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $baseUrl ?>/admin/builder/assign';

    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= htmlspecialchars($csrfToken ?? "") ?>';
    form.appendChild(csrfInput);

    var themeInput = document.createElement('input');
    themeInput.type = 'hidden';
    themeInput.name = 'theme_folder';
    themeInput.value = '<?= htmlspecialchars($activeTheme ?? "default") ?>';
    form.appendChild(themeInput);

    var typeInput = document.createElement('input');
    typeInput.type = 'hidden';
    typeInput.name = 'part_type';
    typeInput.value = (templateSlug === 'none' || !templateSlug) ? 'template:none' : ('template:' + templateSlug);
    form.appendChild(typeInput);

    var idInput = document.createElement('input');
    idInput.type = 'hidden';
    idInput.name = 'part_id';
    idInput.value = (templateSlug === 'none' || !templateSlug) ? 'none' : sectionId;
    form.appendChild(idInput);

    var secInput = document.createElement('input');
    secInput.type = 'hidden';
    secInput.name = 'section_id';
    secInput.value = sectionId;
    form.appendChild(secInput);

    document.body.appendChild(form);
    form.submit();
}

function deletePart(id) {
    if (!confirm('Deseja realmente excluir esta parte de tema?')) return;
    
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= $baseUrl ?>/admin/builder/delete-part';

    var csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = '<?= htmlspecialchars($csrfToken ?? '') ?>';
    form.appendChild(csrfInput);
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'id';
    input.value = id;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

window.addEventListener('click', function(e) {
    var dropdown = document.getElementById('dropdownCreate');
    if (dropdown && !e.target.closest('.button-primary')) {
        dropdown.classList.remove('show');
    }
});
</script>

<style>
#dropdownCreate.show { display: block !important; }
</style>
