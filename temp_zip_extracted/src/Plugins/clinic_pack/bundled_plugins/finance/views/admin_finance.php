<div class="wrap">
    <h1 style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 24px;">💰</span>
        Módulo Financeiro
    </h1>
    <p style="color: #64748b; margin-bottom: 30px;">Gerencie suas contas a pagar, receber e acompanhe o fluxo de caixa.</p>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <?php 
            $flash = $_SESSION['flash_message'];
            $bg = $flash['type'] === 'success' ? '#dcfce3' : '#fee2e2';
            $color = $flash['type'] === 'success' ? '#166534' : '#991b1b';
        ?>
        <div style="background: <?= $bg ?>; color: <?= $color ?>; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($flash['msg']) ?>
            <?php unset($_SESSION['flash_message']); ?>
        </div>
    <?php endif; ?>

    <!-- Renderiza as parciais -->
    <?php include __DIR__ . '/partials/summary_cards.php'; ?>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <?php include __DIR__ . '/partials/transaction_form.php'; ?>
        </div>
        <div style="flex: 2; min-width: 400px;">
            <?php include __DIR__ . '/partials/transaction_list.php'; ?>
        </div>
    </div>
</div>
