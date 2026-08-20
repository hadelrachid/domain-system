# 🗺️ Roteiro de Desenvolvimento (Roadmap)

Este documento registra os próximos passos e plugins planejados para a evolução do **Domain-System** (SaaS Médico).

## 🟢 Prioridade Alta (Próximos Passos Imediatos)

- [ ] **1. Dashboard Inteligente (Plugin ou Expansão)**
  - Substituir a tela de boas-vindas vazia por métricas reais.
  - Exibir "Consultas de Hoje", "Total de Pacientes" e gráficos rápidos.
  - Listagem de acesso rápido para a secretária.

- [ ] **2. Notificações e Mensageria (Plugin `whatsapp`)**
  - Integração com Z-API ou Evolution API.
  - Disparos automáticos: "Consulta agendada", "Lembrete 24h", "Pesquisa de satisfação".
  - *Hooks* escutando os eventos do plugin de Agendamentos.

## 🟡 Prioridade Média (Estruturação e Escala)

- [ ] **3. Perfis e Permissões (Plugin `acl` ou Expansão do `auth`)**
  - Níveis de acesso: `Admin`, `Médico`, `Secretária`.
  - Médicos só veem seus próprios pacientes e agenda.
  - Secretárias não podem alterar configurações globais.

- [ ] **4. Prontuários Avançados (Upload de Exames)**
  - Adicionar suporte para upload de anexos (PDFs, imagens de exames) no prontuário do paciente.
  - Visualizador nativo de anexos.

## 🔵 Prioridade Baixa (Recursos Premium)

- [ ] **5. Módulo Financeiro (Plugin `finance`)**
  - Controle de Caixa (Entradas e Saídas).
  - Vincular um "Agendamento" a um "Pagamento" (Status: Pago, Pendente).
  - Relatório de faturamento mensal.

- [ ] **6. Auto-Agendamento (Integração com o Tema/Site Front-end)**
  - Página pública onde o paciente escolhe o horário disponível do médico.
  - O paciente agenda sozinho e o sistema apenas notifica a clínica.


## 🟣 Fase Final (A Vitrine)

- [ ] **7. Motor de Temas Front-end (Estilo WordPress)**
  - Integrar o front-end público (o site da clínica) consumindo os dados do Kernel.
  - Criar o sistema de injeção de tags (ex: `<?= get_header() ?>`, `<?= the_content() ?>`).
  - Permitir troca de temas dinâmica pela pasta `themes/`.

