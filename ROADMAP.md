# 🗺️ Roteiro de Desenvolvimento — Domain System

Este documento descreve a visão de futuro para o **Domain System**. As prioridades são definidas com base no impacto para o usuário final, na resiliência do sistema e na expansão do ecossistema.

---

## 🟢 Fase 1: Consolidação e Resiliência (Concluída em v1.1.0)

*   [x] **Circuit Breaker V2** — Proteção contra falhas fatais em plugins.
*   [x] **Emergency Hatch** — Rota de emergência para recuperação do sistema.
*   [x] **Dev Simulator** — Ambiente de desenvolvimento seguro com interceptação de e-mails.
*   [x] **Repository Pattern** — Implementação inicial para desacoplar Controllers do banco.
*   [x] **AI Hub (Plugin Builder)** — Geração de plugins e formulários via IA.

---

## 🟡 Fase 2: Experiência do Usuário e Produtividade (Próximos 3 Meses)

### 2.1. Dashboard Inteligente
- [ ] **Métricas em Tempo Real:** Substituir a tela de boas-vindas por um dashboard com KPIs (consultas de hoje, pacientes totais, faturamento do mês).
- [ ] **Gráficos Interativos:** Visualização de produtividade dos médicos e fluxo de pacientes.
- [ ] **Widgets Customizáveis:** Permitir que o usuário monte seu próprio painel.

### 2.2. Comunicação e Notificações
- [ ] **Módulo WhatsApp (Z-API):** Disparo automático de lembretes de consulta (24h antes) e confirmações.
- [ ] **Notificações por E-mail:** Alertas para novos agendamentos, cancelamentos e atualizações de prontuário.
- [ ] **Sistema de Filas (Queue):** Processamento assíncrono de notificações para não travar a interface.

### 2.3. Melhorias no Prontuário Médico
- [ ] **Upload de Exames:** Adicionar suporte para upload de PDFs e imagens diretamente no prontuário.
- [ ] **Histórico do Paciente:** Timeline visual com todas as consultas, exames e prescrições.
- [ ] **Receituário Digital:** Geração de receitas com assinatura digital e QR Code.

---

## 🔵 Fase 3: Escalabilidade e Integrações (3 a 6 Meses)

### 3.1. Gateway de Pagamentos
- [ ] **Integração com Stripe/PagSeguro:** Permitir que o paciente pague consultas online.
- [ ] **Faturamento Automático:** Geração de boletos e notas fiscais eletrônicas.
- [ ] **Relatórios Financeiros:** Análise de inadimplência e fluxo de caixa.

### 3.2. API Pública (REST)
- [ ] **Documentação Swagger/OpenAPI:** Expor endpoints para integração com sistemas externos.
- [ ] **Autenticação OAuth2:** Segurança para terceiros consumirem a API.
- [ ] **Webhooks:** Permitir que sistemas externos escutem eventos do Domain System (ex: `appointment.created`).

### 3.3. Multi-Tenancy (SaaS)
- [ ] **Isolamento de Dados:** Suporte para múltiplas clínicas/empresas no mesmo servidor.
- [ ] **Gestão de Assinaturas:** Controle de planos (Básico, Pro, Enterprise) e limites de uso.
- [ ] **White-Label:** Permitir que cada cliente personalize a marca (logo, cores, domínio).

---

## 🟣 Fase 4: A Grande Interface (6 a 12 Meses)

### 4.1. Motor de Temas (Theme Engine)
- [ ] **Sistema de Templates Avançado:** Criar um mecanismo de temas completo, com hierarquia de templates (como o WordPress).
- [ ] **Editor Visual (WYSIWYG):** Permitir que usuários finais editem páginas e layouts sem programar.
- [ ] **Biblioteca de Componentes:** Reutilização de Shortcodes e blocos prontos para uso.

### 4.2. Cockpit 2.0 (Interface do Usuário)
- [ ] **Design System Unificado:** Construir uma identidade visual moderna e acessível.
- [ ] **Modo Escuro (Dark Mode):** Suporte nativo para temas claros e escuros.
- [ ] **Responsividade Completa:** Interface otimizada para desktop, tablet e mobile.

### 4.3. Performance e Otimização
- [ ] **Cache de Queries:** Redução de consultas repetidas ao banco de dados.
- [ ] **Lazy Loading:** Carregamento sob demanda de módulos e recursos.
- [ ] **Otimização de Assets:** Minificação e compressão de CSS/JS.

---

## ⚙️ Dívida Técnica (Refatoração Contínua)

| Item | Prioridade | Status |
|------|------------|--------|
| Eliminar Service Locator de Plugins | P0 (Crítico) | 🟢 Concluído |
| Decompor `ErrorHandler` | P1 (Alta) | 🔴 Pendente |
| Migrar Migrations para `activate()` | P1 (Alta) | 🟡 Em Progresso |
| Centralizar Eventos em Constantes | P2 (Média) | 🟡 Em Progresso |
| Padronizar Respostas HTTP (Request/Response) | P2 (Média) | 🟢 Concluído |
| Criar Interfaces para `PatientController` | P1 (Alta) | 🟢 Concluído |
| Criar Interfaces para `FinanceController` | P1 (Alta) | 🟢 Concluído |
| Remover Acesso Direto a `$_SESSION` | P0 (Crítico) | 🟢 Concluído |

---

## 🧪 Experimentos e Pesquisas

- [ ] **Integração com Blockchain:** Registro imutável de prontuários médicos.
- [ ] **Chatbot com IA:** Assistente virtual para pacientes (triagem inicial).
- [ ] **Serverless:** Deploy do Kernel em ambientes como AWS Lambda.

---

## 📊 Critérios de Sucesso

- **Zero downtime** durante atualizações de plugins.
- **Tempo de resposta** < 200ms para 95% das requisições.
- **Cobertura de Testes** > 80% para o Core.
- **Satisfação do Desenvolvedor** — Facilidade para criar novos plugins e temas.