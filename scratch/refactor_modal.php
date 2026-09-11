<?php
$file = "src/Plugins/clinic_pack/Views/AbstractCockpitView.php";
$code = file_get_contents($file);

$search = "                  <h2 style=\"margin-top:0;color:var(--primary);margin-bottom:20px;font-size:18px;text-align:center;\"><i class=\"fas fa-user-cog\"></i> Configurações do Perfil</h2>";

$replace = "                  <?php \$doctorId = \$this->data[\"doctorId\"] ?? null; ?>
                  <?php if (\$doctorId): ?>
                  <style>
                      .modal-nav-tabs { display:flex; border-bottom:1px solid #e2e8f0; margin-bottom: 25px; gap:20px; justify-content:center; }
                      .modal-nav-tabs a { text-decoration:none; padding:10px 15px; color:#64748b; font-weight:600; cursor:pointer; font-size:15px; transition:color 0.2s; border-bottom:3px solid transparent; }
                      .modal-nav-tabs a:hover { color:var(--primary); }
                      .modal-nav-tabs a.active { color:var(--primary); border-bottom:3px solid var(--primary); }
                      .modal-tab-pane { display:none; }
                      .modal-tab-pane.active { display:block; }
                  </style>
                  <div class=\"modal-nav-tabs\">
                      <a class=\"active\" onclick=\"document.getElementById(\"pane-conta\").classList.add(\"active\"); document.getElementById(\"pane-agenda\").classList.remove(\"active\"); this.classList.add(\"active\"); this.nextElementSibling.classList.remove(\"active\");\"><i class=\"fas fa-user-cog\"></i> Minha Conta</a>
                      <a onclick=\"document.getElementById(\"pane-agenda\").classList.add(\"active\"); document.getElementById(\"pane-conta\").classList.remove(\"active\"); this.classList.add(\"active\"); this.previousElementSibling.classList.remove(\"active\");\"><i class=\"fas fa-calendar-alt\"></i> Minha Agenda</a>
                  </div>
                  <div id=\"pane-conta\" class=\"modal-tab-pane active\">
                  <?php else: ?>
                  <h2 style=\"margin-top:0;color:var(--primary);margin-bottom:20px;font-size:18px;text-align:center;\"><i class=\"fas fa-user-cog\"></i> Configurações do Perfil</h2>
                  <?php endif; ?>";

$search2 = "                      <!-- Botões de Ação na parte inferior do grid -->";
$replace2 = "                      </div>\n\n                      <!-- Botões de Ação na parte inferior do grid -->";

$code = str_replace($search, str_replace("\"", "'", $replace), $code); // Fix quotes later

// Wait, doing this via script is risky for quotes.

