<?php
/**
 * Partial: _avatar_js.php
 *
 * Inclua DENTRO de uma tag <script> para obter a função previewAvatar().
 * Único lugar onde essa lógica de preview vive — todos os cockpits reutilizam.
 */
if (!defined('DOMAIN_SYSTEM_ROOT')) exit;
?>
/**
 * previewAvatar — atualiza avatares (modal + header) com a foto selecionada.
 * @param {HTMLInputElement} input    o input[type=file]
 * @param {string}           modalId  id do avatar no modal
 * @param {string}           headerId id do avatar no header
 */
function previewAvatar(input, modalId, headerId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const html = '<img src="' + e.target.result + '" alt="Foto"'
                   + ' style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        [modalId, headerId].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = html;
        });
    };
    reader.readAsDataURL(input.files[0]);
}
