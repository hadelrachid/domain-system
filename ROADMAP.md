# Roteiro de Desenvolvimento — Domain System (v2.6.0)

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

## 🟢 Etapa 2: Módulos de Aplicação, Flex Builder e Atualizador (Concluída)

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

---

## 🟢 Etapa 3: Portal do Aluno & DomainCommerce (Concluída na v2.6.0)

### 3.1. Portal do Aluno (Academy LMS)
- [x] **Área do Aluno Completa:** Login seguro, recuperação de acesso, painel do estudante e cálculo de progresso em tempo real.
- [x] **Leitor de PDF Embutido:** Suporte a visualização de apostilas em canvas leve (*PDF.js*) ou leitor nativo, com controle de intervalo de páginas e proteção de download.
- [x] **Certificados Automáticos:** Emissão automática de certificados de conclusão em PDF quando o aluno atinge 100% das aulas.

### 3.2. Motor de Pagamentos (DomainCommerce)
- [x] **Mercado Pago Oficial:** Pix instantâneo nativo na tela (com QR Code dinâmico e Copia-e-Cola), verificação a cada 3,5s com matrícula imediata, cartão em até 12x (e opção de 2 cartões) e boleto bancário.
- [x] **PayPal Oficial:** Integração completa com a API REST v2 de Checkout para vendas internacionais com saldo PayPal e cartões globais.
- [x] **Webhooks de Plataformas de Afiliados:** Auto-provisionamento de alunos via webhooks de venda aprovada da Hotmart, Eduzz, Monetizze e ClickBank.
- [x] **Injeção de Dependências Condicional:** Apenas gateways com credenciais cadastradas e válidas aparecem no checkout.
- [x] **536 Testes Automatizados:** Suíte completa no PHPUnit com 2.999 asserções cobrindo segurança, checkout, gateways e matrículas.

---

## 🔵 Etapa 4: Expansão do Hub Academy (Planejamento v2.7.0)

- [ ] **Fórum de Alunos & Comunidade:** Espaço de discussão pública por curso ou geral com tópicos, respostas formatadas e marcação de respostas resolvidas.
- [ ] **Bloco de Notas Pessoal do Aluno:** Caderno digital com auto-save em tempo real para anotações durante as aulas e exportação de estudos.
- [ ] **Central de Ajuda & Gestão de Devoluções:** Sistema de tickets de suporte com canal de atendimento e botão de estorno rápido acionando o hook `payment.refunded`.
- [ ] **Tutor com Inteligência Artificial (Google Gemini API):** Assistente virtual para tirar dúvidas pedagógicas dos alunos 24h com base no conteúdo das aulas.
