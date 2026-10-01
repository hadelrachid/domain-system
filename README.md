<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-2.0.0-blueviolet?style=for-the-badge" alt="Version 2.0.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <br><br>
  <h1>🚀 Domain System OS (v2.0)</h1>
  <p><strong>O Sistema Operacional Web Definitivo. Um Micro-Kernel SaaS Multi-tenant puro, hiper-resiliente e desenhado para competir com os gigantes.</strong></p>
</div>

---

[🇺🇸 Read in English](README-en.md) | [📚 Documentação & Tutoriais]( https://hadelrachid.github.io/domain-system/) | [🛡️ Auditoria Arquitetural](docs/auditoria.md)

## 🌐 O Que é o Domain System?

O **Domain System** evoluiu. Ele deixou de ser um sistema engessado para se tornar um **verdadeiro Sistema Operacional Web (SaaS Core)**, escrito puramente em PHP e com arquitetura limpa (SOLID e Injeção de Dependência). Ele é uma alternativa moderna e ultra-rápida ao WordPress e frameworks como Laravel.

O limite é a sua imaginação! Você pode rodar **absolutamente qualquer aplicação** em cima dele:
- O **Núcleo (Micro-Kernel)** fornece a infraestrutura de baixo nível, completamente agnóstica: Banco de Dados, Roteamento Dinâmico, Segurança CSRF, Sessão, Multi-tenant (Subdomínios isolados) e Despachante de Eventos (Event-Driven).
- Toda a **lógica de negócio** (seja um sistema jurídico, uma loja virtual, ou um blog) é injetada através de **Plugins** independentes. O núcleo nunca sabe o que a aplicação faz, ele apenas orquestra!

## ✨ Principais Diferenciais

### ⚡ 1. Arquitetura Multi-Tenant Nativa
O OS está preparado para ser a fundação de um SaaS (Software as a Service). Através do mapeamento de subdomínios (`tenants.json`), o Kernel roteia clientes diferentes para bancos de dados totalmente isolados, dividindo recursos de forma eficiente e segura sem misturar os dados.

### 🛡️ 2. No-Break Shield (O Disjuntor Imortal)
Esqueça a "Tela Branca da Morte" (WSOD). O Domain System roda com o **No-Break Shield**: um *Circuit Breaker* em nível de Kernel que intercepta falhas fatais geradas por plugins malfeitos, desativa a execução defeituosa, exibe um painel de telemetria rico (Supervisão de Erros com códigos padronizados) e mantém o resto do SO operando perfeitamente.

### 🎨 3. Builder-Flex e Motor de Páginas Dinâmico
Com o plugin `pages` e a engine de temas, o sistema converte requisições dinamicamente baseadas na URI, acoplando templates front-end do seu tema a blocos de dados. O terreno está preparado para a introdução do **Builder-Flex** (um construtor visual drag-and-drop completo).

### 🔐 4. Autenticação de 2 Fatores (2FA) Global e RBAC
O núcleo de segurança (plugin `Auth`) inclui suporte nativo ao Google Authenticator (TOTP) e controle de acesso baseado em roles universais (`admin`, `manager`, `subscriber`, `user`), permitindo construir qualquer hierarquia de permissões de forma genérica.

### 🌑 5. Instalação Zero-Friction e Skin Engine
Instalador assistido via browser (Wizard) que auto-destrói suas próprias rotas após configurar o banco. No Painel Administrativo, você navega em uma interface alimentada por uma *Skin Engine* Cyberpunk baseada em variáveis CSS e modo Dark-First nativo.

---

## 🚀 Como Instalar

1. **Clone o repositório** para a pasta pública do seu servidor Apache/Nginx (ex: `htdocs` ou `www`):
   ```bash
   git clone https://github.com/hadelrachid/domain-system.git
   ```

2. **Permissões (Linux/Mac):** Certifique-se de que a pasta tenha permissões de escrita para o servidor web.
   ```bash
   chmod -R 777 domain-system/storage
   chmod -R 777 domain-system/config
   ```

3. **Abra no Navegador:**
   Acesse `http://localhost/domain-system` (ou o seu domínio).

4. **Instalador Wizard:**
   O sistema detectará que não está configurado (`config/installed.lock` ausente) e o guiará pelo **Assistente de Instalação**. Siga os passos (banco de dados, usuário admin), e o SO estará pronto para uso!

---

## 📂 Arquitetura (Visão Geral)

```text
/
├── config/              # Central de Tenants, Configurações de Plugins e DB
├── public/              # Diretório público (assets, entrypoint index.php)
├── storage/             # Arquivos persistentes (logs de erro, cache)
└── src/
    ├── Core/            # Micro-Kernel do SO (Router, Eventos, Container, Shield)
    └── Plugins/         # Ecossistema de extensões de negócio
        ├── auth/        # Sistema base de RBAC e 2FA 
        ├── pages/       # Gerador dinâmico de páginas web 
        ├── system-admin/# Interface de controle do OS (Painel e Widgets)
        └── installer/   # Assistente de instalação inteligente
```

## 🛠️ Contribuindo e Tutoriais

Nosso objetivo é padronizar e unificar o desenvolvimento web no PHP. 
Acesse o nosso **[Guia do Desenvolvedor (DEVELOPER_GUIDE.md)](DEVELOPER_GUIDE.md)** para dominar a Injeção de Dependências e a criação de hooks no OS. A pasta `docs/` recebe constantemente manuais e dicionários de arquitetura.

Feito com ☕ e forjado na resiliência arquitetural.
