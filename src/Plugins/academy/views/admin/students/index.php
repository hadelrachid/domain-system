<?php
/**
 * View Administrativa: Gestão de Alunos e Matrículas (Cockpit Academy)
 *
 * @var array<int, array<string, mixed>> $students
 * @var int $total
 * @var int $page
 * @var int $perPage
 * @var int $totalPages
 * @var string $currentSearch
 * @var string $currentStatus
 * @var array<string, int> $metrics
 * @var array<int, array<string, mixed>> $allCourses
 * @var string $csrfToken
 * @var string $baseUrl
 * @var string|null $flashType
 * @var string|null $flashMsg
 */
?>
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <a href="<?= $baseUrl ?>/admin/academy" style="color: var(--text-muted, #94a3b8); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                <i class="fas fa-graduation-cap"></i> Cursos Academy
            </a>
            <span style="color: var(--text-muted, #94a3b8); font-size: 12px;">/</span>
            <span style="color: var(--accent-blue, #45f3ff); font-size: 13px; font-weight: 600;">Gestão de Alunos</span>
        </div>
        <h1 style="margin: 0; font-size: 26px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-user-graduate" style="color: var(--accent-blue, #45f3ff);"></i>
            Alunos & Matrículas
        </h1>
        <p style="margin: 6px 0 0 0; color: var(--text-muted, #94a3b8); font-size: 13px;">
            Pesquisa rápida, concessão e revogação de acessos, moderação e suporte a alunos do ecossistema.
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="<?= $baseUrl ?>/admin/academy" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Voltar aos Cursos
        </a>
    </div>
</div>

<?php if (!empty($flashMsg) && $flashType === 'error'): ?>
    <div style="background: rgba(220,50,50,0.15); border: 1px solid #dc3232; color: #ff8080; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
    </div>
<?php elseif (!empty($flashMsg) && $flashType === 'success'): ?>
    <div style="background: rgba(40,167,69,0.15); border: 1px solid #28a745; color: #5cd67d; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 10px;">
        <i class="fas fa-check-circle" style="font-size: 18px;"></i> <?= htmlspecialchars((string)$flashMsg) ?>
    </div>
<?php endif; ?>

<!-- Cards de Métricas em Tempo Real -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: var(--text-muted, #94a3b8); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Total de Alunos
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #fff;">
            <?= (int)($metrics['total_students'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: #00d284; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Alunos Ativos
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #00d284;">
            <?= (int)($metrics['active_students'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: #f87171; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Suspensos / Banidos
        </div>
        <div style="font-size: 28px; font-weight: 700; color: #f87171;">
            <?= (int)($metrics['suspended_students'] ?? 0) ?>
        </div>
    </div>

    <div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; padding: 18px 20px;">
        <div style="font-size: 12px; color: var(--accent-blue, #45f3ff); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
            Matrículas Concedidas
        </div>
        <div style="font-size: 28px; font-weight: 700; color: var(--accent-blue, #45f3ff);">
            <?= (int)($metrics['total_enrollments'] ?? 0) ?>
        </div>
    </div>
</div>

<!-- Painel Principal de Alunos -->
<div style="background: var(--bg-panel, #1d2327); border: 1px solid var(--border, rgba(255,255,255,0.1)); border-radius: 8px; overflow: hidden;">
    
    <!-- Barra de Filtros e Busca -->
    <div style="padding: 16px 20px; border-bottom: 1px solid var(--border, rgba(255,255,255,0.08)); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <form method="GET" action="<?= $baseUrl ?>/admin/academy/students" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1; max-width: 650px;">
            <div style="position: relative; flex: 1; min-width: 220px;">
                <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted, #94a3b8); font-size: 13px;"></i>
                <input type="text" name="q" placeholder="Buscar aluno por nome ou e-mail..." value="<?= htmlspecialchars((string)($currentSearch ?? '')) ?>" style="width: 100%; padding: 8px 12px 8px 34px; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box; outline: none;">
            </div>

            <select name="status" style="padding: 8px 12px; background: rgba(0,0,0,0.3); border: 1px solid var(--border, rgba(255,255,255,0.15)); border-radius: 6px; color: #fff; font-size: 13px; outline: none;">
                <option value="all" <?= ($currentStatus ?? 'all') === 'all' ? 'selected' : '' ?>>Todos os Status</option>
                <option value="active" <?= ($currentStatus ?? '') === 'active' ? 'selected' : '' ?>>🟢 Ativos</option>
                <option value="suspended" <?= ($currentStatus ?? '') === 'suspended' ? 'selected' : '' ?>>🟡 Suspensos</option>
                <option value="banned" <?= ($currentStatus ?? '') === 'banned' ? 'selected' : '' ?>>🔴 Banidos</option>
            </select>

            <button type="submit" class="btn btn-activate" style="padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer;">
                Filtrar
            </button>

            <?php if (!empty($currentSearch) || ($currentStatus ?? 'all') !== 'all'): ?>
                <a href="<?= $baseUrl ?>/admin/academy/students" style="font-size: 12px; color: var(--text-muted, #94a3b8); text-decoration: underline;">
                    Limpar
                </a>
            <?php endif; ?>
        </form>

        <div style="font-size: 13px; color: var(--text-muted, #94a3b8);">
            Listando <strong><?= count($students) ?></strong> de <strong><?= (int)$total ?></strong> alunos
        </div>
    </div>

    <!-- Tabela -->
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; color: var(--text-main, #fff);">
            <thead>
                <tr style="background: rgba(0,0,0,0.25); border-bottom: 1px solid var(--border, rgba(255,255,255,0.1));">
                    <th style="padding: 14px 18px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted, #94a3b8); text-transform: uppercase;">Aluno</th>
                    <th style="padding: 14px 18px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted, #94a3b8); text-transform: uppercase;">Status</th>
                    <th style="padding: 14px 18px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted, #94a3b8); text-transform: uppercase;">Cursos Matriculados</th>
                    <th style="padding: 14px 18px; text-align: right; font-size: 12px; font-weight: 600; color: var(--text-muted, #94a3b8); text-transform: uppercase;">Ações Rápidas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="4" style="padding: 40px 20px; text-align: center; color: var(--text-muted, #94a3b8); font-size: 14px;">
                            <i class="fas fa-users-slash" style="font-size: 28px; display: block; margin-bottom: 10px; opacity: 0.4;"></i>
                            Nenhum aluno encontrado com os critérios de busca selecionados.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($students as $s): ?>
                    <?php
                    $sStatus = $s['status'] ?? 'active';
                    $statusBadge = match($sStatus) {
                        'banned' => '<span style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;">🔴 Banido</span>',
                        'suspended' => '<span style="background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #fde68a; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;">🟡 Suspenso</span>',
                        default => '<span style="background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #6ee7b7; padding: 3px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600;">🟢 Ativo</span>',
                    };
                    ?>
                    <tr style="border-bottom: 1px solid var(--border, rgba(255,255,255,0.06));">
                        <!-- Aluno -->
                        <td style="padding: 16px 18px; vertical-align: top;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0ea5e9, #38bdf8); color: #000; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <?= strtoupper(mb_substr((string)$s['name'], 0, 2)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; font-size: 14px; color: #fff;">
                                        <?= htmlspecialchars((string)$s['name']) ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted, #94a3b8); margin-top: 2px;">
                                        <?= htmlspecialchars((string)$s['email']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 2px;">
                                        ID #<?= (int)$s['id'] ?> &bull; Cadastro: <?= htmlspecialchars((string)$s['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Status -->
                        <td style="padding: 16px 18px; vertical-align: middle;">
                            <?= $statusBadge ?>
                        </td>

                        <!-- Cursos Matriculados -->
                        <td style="padding: 16px 18px; vertical-align: middle;">
                            <?php if (empty($s['enrolled_courses'])): ?>
                                <span style="font-size: 12px; color: var(--text-muted, #94a3b8); font-style: italic;">
                                    Nenhum curso matriculado
                                </span>
                            <?php else: ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    <?php foreach ($s['enrolled_courses'] as $c): ?>
                                        <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.25); border-radius: 4px; padding: 3px 8px; font-size: 11px; color: #e0f2fe;">
                                            <span>🎓 <?= htmlspecialchars((string)$c['title']) ?></span>
                                            <form method="POST" action="<?= $baseUrl ?>/admin/academy/students/<?= (int)$s['id'] ?>/revoke" style="margin: 0; display: inline;" onsubmit="event.preventDefault(); const f = this; OS.confirm('Revogar matrícula de <?= htmlspecialchars(addslashes((string)$s['name'])) ?> no curso <?= htmlspecialchars(addslashes((string)$c['title'])) ?>?', () => f.submit());">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="course_id" value="<?= (int)$c['course_id'] ?>">
                                                <button type="submit" title="Revogar Matrícula" style="background: none; border: none; color: #f87171; cursor: pointer; padding: 0; font-size: 10px; margin-left: 2px;">&times;</button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Ações Rápidas -->
                        <td style="padding: 16px 18px; vertical-align: middle; text-align: right;">
                            <div style="display: inline-flex; gap: 8px; align-items: center; justify-content: flex-end;">
                                <!-- Botão Matricular -->
                                <button type="button" onclick="openEnrollModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes((string)$s['name'])) ?>')" class="btn" style="background: rgba(69, 243, 255, 0.12); border: 1px solid var(--accent-blue, #45f3ff); color: var(--accent-blue, #45f3ff); padding: 5px 10px; font-size: 12px; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-plus-circle"></i> Matricular
                                </button>

                                <!-- Botão Moderar / Status -->
                                <button type="button" onclick="openStatusModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes((string)$s['name'])) ?>', '<?= htmlspecialchars($sStatus) ?>')" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 5px 10px; font-size: 12px; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-shield-alt"></i> Moderar
                                </button>

                                <!-- Botão Redefinir Senha -->
                                <button type="button" onclick="openPasswordModal(<?= (int)$s['id'] ?>, '<?= htmlspecialchars(addslashes((string)$s['name'])) ?>')" class="btn" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; padding: 5px 10px; font-size: 12px; border-radius: 5px; cursor: pointer;">
                                    <i class="fas fa-key"></i> Senha
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <?php if ($totalPages > 1): ?>
        <?php 
        $queryParams = [
            'q' => $currentSearch ?? '',
            'status' => $currentStatus ?? 'all',
        ];
        $buildPageUrl = function(int $p) use ($baseUrl, $queryParams) {
            $params = array_filter(array_merge($queryParams, ['page' => $p]), fn($v) => $v !== '' && $v !== 'all');
            return $baseUrl . '/admin/academy/students' . (!empty($params) ? '?' . http_build_query($params) : '');
        };
        ?>
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-top: 1px solid var(--border, rgba(255,255,255,0.08)); flex-wrap: wrap; gap: 8px;">
            <div style="font-size: 12px; color: var(--text-muted, #94a3b8);">
                Página <strong><?= (int)$page ?></strong> de <strong><?= (int)$totalPages ?></strong> (Total de <?= (int)$total ?> alunos)
            </div>
            <div style="display: flex; gap: 6px;">
                <?php if ($page > 1): ?>
                    <a href="<?= $buildPageUrl($page - 1) ?>" class="btn" style="padding: 5px 12px; font-size: 12px; background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; text-decoration: none; border-radius: 4px;">&larr; Anterior</a>
                <?php endif; ?>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p == $page): ?>
                        <span style="padding: 5px 12px; font-size: 12px; font-weight: 700; background: var(--accent-blue, #45f3ff); color: #000; border-radius: 4px;"><?= $p ?></span>
                    <?php elseif ($p == 1 || $p == $totalPages || abs($p - $page) <= 2): ?>
                        <a href="<?= $buildPageUrl($p) ?>" class="btn" style="padding: 5px 12px; font-size: 12px; background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; text-decoration: none; border-radius: 4px;"><?= $p ?></a>
                    <?php elseif (abs($p - $page) == 3): ?>
                        <span style="padding: 5px 8px; font-size: 12px; color: var(--text-muted, #94a3b8);">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= $buildPageUrl($page + 1) ?>" class="btn" style="padding: 5px 12px; font-size: 12px; background: rgba(255,255,255,0.06); border: 1px solid var(--border, rgba(255,255,255,0.15)); color: #fff; text-decoration: none; border-radius: 4px;">Próxima &rarr;</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ================= MODAIS ================= -->

<!-- Modal Matricular Aluno -->
<div id="modalEnroll" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; width: 100%; max-width: 480px; padding: 24px; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <h3 style="margin-top:0; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus-circle" style="color: var(--accent-blue, #45f3ff);"></i> Conceder Matrícula
        </h3>
        <p style="font-size: 13px; color: var(--text-muted, #94a3b8); margin-bottom: 18px;">
            Selecione o curso para matricular o aluno <strong id="modalEnrollStudentName" style="color:#fff;"></strong>.
        </p>

        <form id="formEnroll" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Curso Disponível</label>
                <select name="course_id" required style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <option value="">Selecione um curso...</option>
                    <?php foreach ($allCourses as $c): ?>
                        <option value="<?= (int)$c['id'] ?>">🎓 <?= htmlspecialchars((string)$c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('modalEnroll')" class="btn" style="background: rgba(255,255,255,0.1); color: #fff; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="padding: 8px 18px; font-weight: 600; cursor: pointer;">Matricular Aluno</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Moderação / Alterar Status -->
<div id="modalStatus" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; width: 100%; max-width: 480px; padding: 24px; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <h3 style="margin-top:0; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-shield-alt" style="color: #f59e0b;"></i> Moderação de Conta
        </h3>
        <p style="font-size: 13px; color: var(--text-muted, #94a3b8); margin-bottom: 18px;">
            Moderar o acesso à sala de aula de <strong id="modalStatusStudentName" style="color:#fff;"></strong>.
        </p>

        <form id="formStatus" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Status de Acesso</label>
                <select name="status" id="modalStatusSelect" required style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <option value="active">🟢 Ativo (Acesso normal à sala de aula)</option>
                    <option value="suspended">🟡 Suspenso (Acesso temporariamente bloqueado)</option>
                    <option value="banned">🔴 Banido (Bloqueio total por violação de diretrizes)</option>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('modalStatus')" class="btn" style="background: rgba(255,255,255,0.1); color: #fff; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="padding: 8px 18px; font-weight: 600; cursor: pointer;">Salvar Moderação</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Redefinir Senha -->
<div id="modalPassword" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center;">
    <div style="background: #1e293b; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; width: 100%; max-width: 480px; padding: 24px; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
        <h3 style="margin-top:0; font-size: 18px; color: #fff; display: flex; align-items: center; gap: 8px;">
            <i class="fas fa-key" style="color: var(--accent-blue, #45f3ff);"></i> Redefinir Senha do Aluno
        </h3>
        <p style="font-size: 13px; color: var(--text-muted, #94a3b8); margin-bottom: 18px;">
            Digite uma nova senha segura para <strong id="modalPasswordStudentName" style="color:#fff;"></strong>.
        </p>

        <form id="formPassword" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Nova Senha</label>
                <input type="password" name="new_password" id="inputNewPassword" required minlength="6" placeholder="Mínimo 6 caracteres..." style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid rgba(255,255,255,0.2); border-radius: 6px; color: #fff; font-size: 14px; box-sizing: border-box; outline: none;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('modalPassword')" class="btn" style="background: rgba(255,255,255,0.1); color: #fff; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">Cancelar</button>
                <button type="submit" class="btn btn-activate" style="padding: 8px 18px; font-weight: 600; cursor: pointer;">Atualizar Senha</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEnrollModal(studentId, studentName) {
    document.getElementById('modalEnrollStudentName').textContent = studentName;
    document.getElementById('formEnroll').action = '<?= $baseUrl ?>/admin/academy/students/' + studentId + '/enroll';
    document.getElementById('modalEnroll').style.display = 'flex';
}

function openStatusModal(studentId, studentName, currentStatus) {
    document.getElementById('modalStatusStudentName').textContent = studentName;
    document.getElementById('modalStatusSelect').value = currentStatus;
    document.getElementById('formStatus').action = '<?= $baseUrl ?>/admin/academy/students/' + studentId + '/status';
    document.getElementById('modalStatus').style.display = 'flex';
}

function openPasswordModal(studentId, studentName) {
    document.getElementById('modalPasswordStudentName').textContent = studentName;
    document.getElementById('inputNewPassword').value = '';
    document.getElementById('formPassword').action = '<?= $baseUrl ?>/admin/academy/students/' + studentId + '/reset-password';
    document.getElementById('modalPassword').style.display = 'flex';
}

function closeModal(modalId) {
    const el = document.getElementById(modalId);
    if (el) el.style.display = 'none';
}

// Fechar ao clicar fora
window.addEventListener('click', function(e) {
    ['modalEnroll', 'modalStatus', 'modalPassword'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>
