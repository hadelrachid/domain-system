<?php
// Certifique-se de que estamos no ecossistema e temos o Layout (se existir, ou crie a view aqui)
$pageTitle = "Pronturio Eletrnico: " . htmlspecialchars($appointment['patient_name']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-blue-900 text-white shadow-md p-4">
            <div class="container mx-auto flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <a href="<?= BASE_URL ?>/admin/appointments" class="text-blue-200 hover:text-white">&larr; Voltar à Fila</a>
                    <h1 class="text-xl font-bold">Pronturio Clnico</h1>
                </div>
                <div class="text-sm">
                    Mdico: <b><?= htmlspecialchars($appointment['doctor_name']) ?></b>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow container mx-auto p-4 md:p-6 flex flex-col lg:flex-row gap-6">
            
            <!-- Sidebar: Dados do Paciente -->
            <aside class="w-full lg:w-1/4">
                <div class="bg-white p-5 rounded-lg shadow-sm border border-gray-200 sticky top-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Dados do Paciente</h2>
                    
                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="block text-gray-500 text-xs uppercase tracking-wider">Nome Completo</span>
                            <span class="font-medium text-gray-900 text-base"><?= htmlspecialchars($appointment['patient_name']) ?></span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs uppercase tracking-wider">CPF</span>
                            <span class="text-gray-800"><?= htmlspecialchars($appointment['cpf']) ?></span>
                        </div>
                        <div>
                            <span class="block text-gray-500 text-xs uppercase tracking-wider">Data de Nascimento</span>
                            <span class="text-gray-800"><?= htmlspecialchars(date('d/m/Y', strtotime($appointment['patient_dob']))) ?></span>
                        </div>
                        <div class="pt-3 border-t">
                            <span class="block text-gray-500 text-xs uppercase tracking-wider mb-1">Motivo / Queixa (Recepco)</span>
                            <div class="bg-yellow-50 p-3 rounded text-yellow-800 text-sm italic">
                                "<?= htmlspecialchars($appointment['reception_notes'] ?? 'Sem notas') ?>"
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Formulrio do Pronturio -->
            <section class="w-full lg:w-3/4 bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <?php if(isset($_GET['success'])): ?>
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        ?? Pronturio salvo com sucesso!
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/admin/appointments/record/<?= $appointment['id'] ?>" class="space-y-6">
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Anamnese (Histria Clnica)</label>
                        <textarea name="anamnese" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Relato do paciente, incio dos sintomas..."><?= htmlspecialchars($record['anamnese']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Exame Fsico</label>
                        <textarea name="exame_fisico" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="PA, FC, aspecto geral, dor  palpaco..."><?= htmlspecialchars($record['exame_fisico']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Hipótese Diagnóstica (CID-10)</label>
                        <textarea name="cid_10" rows="3" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Ex: J03.9 - Amigdalite aguda não especificada..."><?= htmlspecialchars($record['cid_10']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Prescrico e Conduta</label>
                        <textarea name="prescricao" rows="4" class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Medicamentos, dosagem, vias de administraco, atestados..."><?= htmlspecialchars($record['prescricao']) ?></textarea>
                    </div>

                    <div class="pt-6 border-t flex justify-end space-x-3">
                        <a href="<?= BASE_URL ?>/admin/appointments/record/<?= $appointment['id'] ?>/print" target="_blank" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded shadow-sm flex items-center">
                            🖨️ Imprimir Receita
                        </a>
                        <button type="submit" name="salvar" class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-6 rounded shadow-sm">
                            Salvar (Continuar)
                        </button>
                        <button type="submit" name="finalizar" class="bg-blue-700 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded shadow-sm">
                            Finalizar Atendimento
                        </button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
