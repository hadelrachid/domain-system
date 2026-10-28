# 🗺️ Roteiro de Desenvolvimento — Domain System (v2.5)

Este documento resume as etapas de evolução do **Domain System**. O objetivo é consolidar uma base de código modular, estável e bem testada em PHP 8.2+.

---

## 🟢 Etapa 1: Infraestrutura Básica e Resiliência (Concluída)

* [x] **Tratamento de Exceções e Circuit Breaker:** Mecanismo centralizado para capturar falhas no ciclo de vida de extensões e preservar o funcionamento das rotas principais.
* [x] **Persistência Atômica de Configurações (`AtomicJsonStore`):** Escrita atômica com bloqueio exclusivo (`LOCK_EX`) e pontos de restauração do estado do sistema.
* [x] **Verificação de Esquema (`SchemaDoctorInterface`):** Verificação de integridade estrutural das tabelas de plugins em SQLite e MySQL.
* [x] **Container de Injeção de Dependências:** Resolução de dependências por construtor, eliminando variáveis globais e acoplamentos estáticos.
* [x] **Controle de Acesso (RBAC) e Segurança:** Verificação de permissões por capabilities, proteção anti-CSRF e suporte a autenticação em dois fatores (2FA).
* [x] **Assistente de Instalação:** Fluxo inicial para configuração de banco de dados e criação do usuário administrador.

---

## 🟢 Etapa 2: Módulos de Aplicação, Flex Builder e Atualizador (Concluída na v2.5)

### 2.1. Flex Builder (Full Site Editing)
- [x] **Estrutura por Contratos (`src/Core/Theme/Parts`):** Composição de cabeçalhos, rodapés e seções centrais em 14 blocos semânticos nativos, com suporte a extensões via `BlockProviderInterface`.
- [x] **Compilação e Validação Unificada:** Paridade entre visualização no painel e renderização pública via `ThemePartCompilerInterface` e validação estrutural com `ThemePartValidatorInterface`.
- [x] **Visibilidade Responsiva (4 Resoluções) e Auto-Reflow:** Configuração de visibilidade por elemento (Desktop, Laptop 1024px, Tablet 768px e Mobile 375px) com reajuste automático das colunas do CSS Grid.
- [x] **Vinculação de Seções a Templates:** Associação de seções centrais a templates do tema ativo (`home.php`, `sobre.php`, etc.) e shortcode `[section id="..."]`.

### 2.2. Academy (LMS)
- [x] **Editor em Blocos para Conteúdo:** Blocos estruturados com sanitização server-side.
- [x] **Estruturação de Pastas e Aulas:** Geração de arquivos `.php` de aulas no tema ativo a partir do título cadastrado.
- [x] **Simulador Multi-Dispositivos:** Visualização prévia em diferentes resoluções de tela e opções de layout mobile (gaveta, barra inferior, cinema).

### 2.3. Sistema de Atualização (`SystemUpdater`)
- [x] **Atualizações via GitHub Releases:** Verificação de novas versões, validação de integridade por SHA-256 e aplicação atômica de arquivos preservando dados locais.
- [x] **Rotinas de Backup e Rollback:** Backup preventivo de código e banco de dados antes da aplicação de atualizações.

### 2.4. Integração com Modelos de IA e Documentação Aberta
- [x] **Mapa Consolidado (`project.txt`):** Arquivo estruturado na raiz do repositório para permitir auditorias completas e análise por ferramentas de IA sem fragmentação de contexto.
- [x] **Guia de Contratos (`AI_CONTRACT_GUIDE.md`):** Especificação de regras para geração de código aderente às interfaces do projeto.
- [x] **Descoberta Dinâmica de Manuais (`ManualModuleInterface`):** Módulos de documentação carregados automaticamente no painel administrativo.

---

## 🔵 Etapa 3: Planejamento Futuro (Próximos Ciclos)

### 3.1. Gerenciamento Multi-Tenant Virtual
- [ ] **Roteamento por Subdomínio:** Suporte opcional a múltiplos inquilinos com isolamento de banco de dados via configuração de host.
- [ ] **Provisionamento Automatizado:** Criação de novas instâncias virtuais a partir do painel de administração.

### 3.2. APIs e Integrações Externas
- [ ] **Endpoints REST e Webhooks:** Padronização de endpoints para consumo externo de dados de páginas e cursos.
- [ ] **Autenticação via Tokens/OAuth2:** Mecanismo para autenticação de clientes externos e aplicações mobile.

### 3.3. Aprimoramento da Interface do Usuário
- [ ] **Personalização do Cockpit:** Reorganização personalizável dos cards informativos no painel principal.
- [ ] **Editor Visual Integrado:** Ferramentas aprimoradas para edição de arquivos de tema diretamente pelo navegador.
