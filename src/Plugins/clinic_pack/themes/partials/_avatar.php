<?php
/**
 * Partial: _avatar.php
 *
 * Exibe foto do usuário OU iniciais do nome como placeholder.
 * Fonte única de verdade — todos os cockpits incluem este arquivo.
 *
 * Variáveis esperadas:
 *   string $avatarName   nome completo
 *   string $avatarPhoto  URL/caminho da foto (ou vazio)
 *   string $avatarId     id do elemento DOM
 *   string $avatarSize   "sm" (48 px, header) | "lg" (130 px, modal)
 */
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;

$_ini = implode('', array_map(
    fn($w) => mb_strtoupper(mb_substr($w, 0, 1)),
    array_slice(explode(' ', trim($avatarName ?? '')), 0, 2)
));
$_cls = ($avatarSize ?? 'sm') === 'lg' ? 'avatar avatar-lg' : 'avatar';
?>
<div class="<?= $_cls ?>" id="<?= htmlspecialchars($avatarId ?? 'avatar') ?>">
    <?php if (!empty($avatarPhoto)): ?>
        <img src="<?= htmlspecialchars($avatarPhoto) ?>" alt="Foto de <?= htmlspecialchars($avatarName ?? '') ?>">
    <?php else: ?>
        <?= htmlspecialchars($_ini ?: '?') ?>
    <?php endif; ?>
</div>
