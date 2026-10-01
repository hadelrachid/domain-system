<h1>Módulo de Pacientes</h1>

<style>
    .flex-container { display: flex; flex-direction: column; gap: 20px; }
    @media (max-width: 768px) {
        .flex-container { flex-direction: column; }
    }
</style>

<div class="flex-container">
    <!-- Formulário de Cadastro (Injetado via partial) -->
    <?php include __DIR__ . '/partials/form.php'; ?>

    <!-- Tabela de Pacientes (Injetada via partial) -->
    <?php include __DIR__ . '/partials/list.php'; ?>
</div>
