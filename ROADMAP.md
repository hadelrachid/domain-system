# Roteiro de Desenvolvimento — Domain System (v2.5)

Este documento descreve as etapas de evolução do **Domain System**. O objetivo é manter uma base de código modular, previsível e com testes automatizados em PHP 8.2+.

---

## 🟢 Etapa 1: Infraestrutura Básica e Resiliência (Concluída)

* [x] **Tratamento de Exceções e Tolerância a Falhas:** Captura centralizada de falhas no ciclo de vida de extensões para manter as rotas principais em operação.
* [x] **Persistência Atômica de Configurações (`AtomicJsonStore`):** Gravação de arquivos com bloqueio exclusivo (`LOCK_EX`) e gravação atômica temporária com renomeação.
* [x] **Verificação de Esquema (`SchemaDoctorInterface`):** Verificação de integridade das tabelas de banco de dados em SQLite e MySQL.
* [x] **Container de Injeção de Dependências:** Resolução de dependências via construtor, eliminando variáveis globais e acoplamento estático.
* [x] **Controle de Acesso por Capabilities:** Permissões granulares configuráveis no painel administrativo, proteção anti-CSRF e suporte a 2FA.
* [x] **Assistente de Instalação:** Setup inicial para configuração de banco de dados e usuário administrador.

---

## 🟢 Etapa 2: Módulos de Aplicação, Flex Builder e Atualizador (Concluída na v2.5)

### 2.1. Flex Builder (Full Site Editing)
- [x] **Estrutura por Contratos (`src/Core/Theme/Parts`):** Composição de cabeçalhos, rodapés e seções centrais em 14 blocos semânticos nativos, com suporte a extensões via `BlockProviderInterface`.
- [x] **Compilação e Validação Unificada:** Paridade entre visualização no editor e renderização pública via `ThemePartCompilerInterface`, com validação estrutural pré-persistência.
- [x] **Visibilidade Responsiva (4 Resoluções) e Auto-Reflow:** Configuração de visibilidade por elemento (Desktop, Laptop 1024px, Tablet 768px e Mobile 375px) com reajuste automático das colunas do CSS Grid.
- [x] **Vinculação de Seções a Templates:** Associação de seções centrais a templates do tema ativo (`home.php`, `sobre.php`, etc.) e shortcode `[section id="..."]`.

### 2.2. Academy (LMS)
- [x] **Editor em Blocos para Conteúdo:** Editor estruturado com sanitização server-side.
- [x] **Estruturação de Pastas e Aulas:** Geração de arquivos `.php` de aulas no tema ativo a partir do título cadastrado.
- [x] **Simulador Multi-Dispositivos:** Visualização prévia em diferentes resoluções de tela e opções de layout mobile (gaveta, barra inferior, cinema).

### 2.3. Sistema de Atualização (`SystemUpdater`)
- [x] **Atualizações via GitHub Releases:** Verificação de versões, validação de integridade por hash SHA-256 e aplicação atômica de arquivos preservando configurações locais.
- [x] **Rotinas de Backup e Rollback:** Backup preventivo de código e banco de dados antes da aplicação de atualizações.

### 2.4. Contexto Aberto para Auditoria
- [x] **Arquivos de Contexto Consolidado (`domain-system.txt` e `project.txt`):** Estrutura completa do repositório em um único arquivo para permitir análises estáticas e auditorias técnicas por ferramentas e modelos de IA.
- [x] **Descoberta Dinâmica de Manuais (`ManualModuleInterface`):** Módulos de documentação carregados automaticamente no painel administrativo.

---

## 🔵 Etapa 3: Planejamento Futuro (Melhorias Contínuas)

### 3.1. Otimização de Performance e Cache
- [ ] **Headers de Cache HTTP:** Suporte padronizado a cabeçalhos `ETag` e `Cache-Control` nas respostas de páginas estáticas e temas.
- [ ] **Minificação Opcional de Assets:** Otimização de entrega de folhas de estilo e scripts públicos.

### 3.2. Endpoints e Integrações
- [ ] **Endpoints REST Básicos:** Padronização de endpoints para consulta externa de páginas e conteúdo publicado.
- [ ] **Autenticação via Tokens:** Suporte a chaves de API para leitura programática de dados.

### 3.3. Interface e Usabilidade
- [ ] **Personalização do Painel:** Reorganização personalizável dos cards informativos no dashboard administrativo.
- [ ] **Expansão da Biblioteca de Blocos:** Adição de novos blocos semânticos para o Flex Builder conforme demandas práticas de uso.
