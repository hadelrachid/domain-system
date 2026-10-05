> **ATENÇÃO ARQUITETURAL / PARADIGM SHIFT (2026-10-04)**
> 
> O Domain System **NÃO** é mais um sistema de "SaaS Multi-Tenant". Ele evoluiu para se tornar um **Sistema Operacional Web (Web OS) independente** e puro.
> 
> No passado, o núcleo foi desenhado focado em separar ambientes de SaaS, mas esse acoplamento limitava o projeto. Hoje, o Domain System funciona como um Sistema Operacional puro (como Linux/Windows), que pode ser instalado em uma máquina ou container.
> Se for desejado que ele preste serviços Multi-Tenant, isso deverá ser feito virtualmente ou através de um aplicativo (Plugin Ring 3) construído especificamente para isso, **sem afetar o Micro-Kernel (Ring 0)**.

# 🗺️ Roteiro de Desenvolvimento — Domain System OS v2.0

Este documento descreve a visão de futuro para o **Domain System OS**. As prioridades são definidas com base no impacto para o usuário final, na resiliência do sistema e na expansão do ecossistema para bater de frente com as ferramentas consolidadas no mercado (WordPress, Laravel).

---

## 🟢 Fase 1: Consolidação e Resiliência (Concluída em v1.3.0)

*   [x] **No-Break Shield (Circuit Breaker V2)** — Proteção contra falhas fatais em plugins e painel de telemetria visual.
*   [x] **Emergency Hatch** — Rota de emergência para recuperação do sistema.
*   [x] **Dependency Injection Container** — Migração total para injeção de dependências e eliminação de Service Locators.
*   [x] **RBAC e Auth Nativo** — Limpeza de acoplamentos legados. Criação de Roles universais (Admin, Manager, Subscriber, User).
*   [x] **Instalador Visual (Zero-Friction)** — Setup wizard com auto-destruição para proteção de rotas, configurando banco e primeiro usuário em segundos.

---

## 🟡 Fase 2: Experiência do Desenvolvedor e do Usuário (Em Andamento)

### 2.1. Construtor Visual (Builder-Flex)
- [ ] **Integração com JSON Schema:** Abandonar salvamento de HTML cru no banco; salvar a estrutura do layout das páginas em formato JSON estruturado.
- [ ] **Editor Drag-and-Drop VCL:** Construtor de blocos visuais reais em JavaScript, com manipulação de propriedades (Object Inspector) no painel administrativo.
- [ ] **Renderização Dinâmica no Core:** O `PageFrontController` será responsável por ler o JSON e injetar os componentes PHP em tempo de execução.

### 2.2. Ferramentas para Criação Autônoma (AI Hub)
- [ ] **Editor de Plugins Code-in-Browser:** Uma mini IDE integrada no painel do OS, permitindo criar a estrutura (`plugin.json`, `Plugin.php`) sem precisar abrir ferramentas externas.
- [ ] **Integração com IA (LLMs):** Um assistente integrado ao painel capaz de gerar rotas e cruds dinamicamente injetando código no Builder-Flex e no Editor de Plugins.
- [ ] **Prompt Terminal (CLI Visual):** Linha de comando no Dashboard capaz de entender códigos de erro do `ERROR_DICTIONARY.md` e corrigir automaticamente falhas (Automação Autônoma).

### 2.3. Dashboard Inteligente e Modular
- [ ] **Widgets Customizáveis:** Permitir que o usuário final adicione, remova e arraste widgets criados por plugins na tela inicial.
- [ ] **Telemetria Centralizada:** Gráficos mostrando uso de RAM, requisições por segundo e relatórios de uptime baseados no log do No-Break Shield.

### 2.4. View & Response Engine (Contratos de Renderização)
- [ ] **Interfaces de Resposta:** Padronização das saídas HTTP (ViewResponse, JsonResponse, RedirectResponse) para que *Controllers* não retornem strings cruas.
- [ ] **Renderização Previsível (Fim do Inception):** O Kernel definirá o envelopamento (Layout/Sidebar) estritamente baseado no Contrato retornado (ex: `LayoutResponseInterface`), economizando código e automatizando menus e layouts corretamente.

---


## 🔵 Fase 3: Escalabilidade e Virtualização de Serviços (3 a 6 Meses)

### 3.1. Arquitetura de Subdomínios (Virtual Instance Engine)
- [ ] **Roteador Avançado:** Consolidar a leitura dinâmica do `tenants.json` no boot do sistema para separar o banco de dados antes que qualquer plugin inicie.
- [ ] **Tenant Provisioner Plugin:** Um plugin oficial que permite criar um novo ambiente (banco de dados + subdomínio) com 1 clique a partir de um painel Super Admin.
- [ ] **Gestão de Assinaturas (Stripe):** Bloqueio ou liberação de tenants baseado no status da fatura do cliente.

### 3.2. Abstração Total de Banco de Dados
- [x] **Schema Builder:** Em vez de arquivos `.sql` ou queries hardcoded, os plugins usarão uma classe `Schema` para criar tabelas que funcione tanto no MySQL (Produção Hostinger) quanto no SQLite (Testes).
- [ ] **Migrações Automáticas:** O Kernel comparará o Schema desejado pelo plugin com a tabela real no banco e aplicará as alterações (ALTER TABLE) sem intervenção humana.

### 3.3. API Pública (REST & Webhooks)
- [ ] **Central de Webhooks (Event Gateway):** Um ponto único e seguro onde serviços externos disparam eventos no OS.
- [ ] **Autenticação OAuth2:** Permitir que aplicativos móveis ou terceiros consigam gerar chaves API para acessar os dados do SO.

---

## 🟣 Fase 4: A Grande Interface (Iniciada)

### 4.1. CockPit OS 2.0 (Interface do Usuário & Skin Engine)
- [x] **Design System Unificado:** Contrato visual oficial (`DESIGN_SYSTEM.md`) obrigando plugins a usarem classes unificadas em vez de CSS solto.
- [x] **Skin Engine Cyberpunk Dinâmica:** Sistema baseado em arquivos `.json` e variáveis CSS que permitem injetar temas dark/light sem relar no PHP.
- [ ] **Painel Totalmente SPA:** Transição de abas e menus via "Live Fetch" AJAX, acabando com a sensação de recarregamento (F5) ao transitar nas configurações.

### 4.2. Performance e Otimização de SEO
- [ ] **Otimização de Assets (SEO Engine):** Minificação e compressão profunda de CSS/JS e HTML para bater 99+ no Lighthouse em páginas públicas criadas via Builder-Flex.
- [ ] **Auto-Fixer de Velocidade Baseado em IA:** Integração para o Kernel propor ou gerar automaticamente correções de performance caso a nota da página comece a cair.

---

## ⚙️ Dívida Técnica (Refatoração Contínua)

| Item | Prioridade | Status |
|------|------------|--------|
| Implementar Padronização Completa de Erros (Dicionário de Códigos) | P0 (Crítico) | 🟢 Concluído |
| Expurgo Absoluto de Dependências de Negócio (Desacoplamento para Web OS) | P0 (Crítico) | 🟢 Concluído |
| Decompor `ErrorHandler` nativo e mesclar com o No-Break Shield | P1 (Alta) | 🟡 Em Progresso |
| Migrar Queries Manuais para um QueryBuilder unificado | P2 (Média) | 🕒 Pendente |

---

## 📊 Critérios de Sucesso

- **Agnóstico Total**: O Kernel não deve ter dependências que deduzam que negócio está rodando nele.
- **Zero downtime** durante falhas de código ou ativação de plugins.
- **Tempo de resposta** < 150ms para renderização de páginas front-end públicas.
- **Ecossistema:** Ter a mesma facilidade de criar temas e extensões que o WordPress oferece aos seus desenvolvedores.

