<div align="center">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Version-1.2.0-blueviolet?style=for-the-badge" alt="Version 1.2.0">
  <img src="https://img.shields.io/badge/Status-Stable-brightgreen?style=for-the-badge" alt="Status">
  <img src="https://img.shields.io/badge/License-MIT-blue?style=for-the-badge" alt="License">
  <br><br>
  <h1>🚀 Domain System (CockPit) v1.2.0</h1>
  <p><strong>O Motor de Negócios que não Morre. Um framework modular, hiper-resiliente e extensível.</strong></p>
</div>

---

[🇺🇸 Read in English](README-en.md) | [📚 Documentação & Tutoriais]( https://hadelrachid.github.io/domain-system/) | [🛡️ Auditoria Arquitetural](docs/auditoria.md)

## 🌐 O Que é o Domain System?

O **Domain System** (também conhecido como **CockPit**) é um **CMS, Framework e Plataforma Universal** escrito puramente em PHP (semelhante à filosofia do WordPress, porém utilizando conceitos arquiteturais modernos como SOLID e Injeção de Dependência).

Ele foi projetado para rodar **absolutamente qualquer aplicação empresarial**. O limite é a sua imaginação! Ele funciona como um **verdadeiro Sistema Operacional Web** para o seu negócio:
- O **Núcleo (Kernel)** fornece a infraestrutura essencial de baixo nível: Banco de Dados, Segurança CSRF Global, Roteamento, Sessão e Despachante de Eventos.
- Toda a **lógica de negócio** é encapsulada em **Plugins** independentes, plugáveis e intercambiáveis (como o `clinic_pack` incluso, que transforma o sistema em um poderoso ERP Médico).

## ✨ Principais Diferenciais

### ⚡ 1. Instalação Zero-Friction (Wizard Instalador)
Diga adeus às edições manuais de arquivos de configuração! O sistema conta com um **Wizard de Instalação Automático**. 
Basta abrir o projeto no navegador, e uma interface amigável vai guiar você pela configuração do Banco de Dados, criação do administrador e ajuste de URL em segundos.

### 🛡️ 2. No-Break Shield (O Disjuntor Imortal)
Erros fatais (como um erro de sintaxe) derrubam sistemas tradicionais. **Não o Domain System**.
Graças ao nosso exclusivo *Quadro de Transferência Automática (Circuit Breaker)*, se um módulo ou plugin tentar causar uma pane fatal (Fatal Error), o sistema intercepta a queda de energia, isola e desativa o plugin defeituoso, e mantém o sistema inteiro no ar. O administrador é notificado de forma segura.

### 🔒 3. Blindagem CSRF Global
Segurança nativa e invisível. O núcleo injeta automaticamente proteções contra ataques de falsificação de solicitações em todos os formulários do sistema.

### 🎨 4. Motor de Temas "CockPit" & Shortcodes Avançados
Interfaces separadas da lógica! Crie temas (CockPits) para médicos, secretárias ou pacientes sem tocar no código back-end. 
Na versão 1.2.0, nosso **ShortcodeManager** tornou-se um construtor de Contêineres, suportando recursividade profunda (ex: `[modal_perfil] [child] [/modal_perfil]`), idêntico aos page builders modernos!

### 🔐 5. Autenticação de 2 Fatores (2FA) Nativa
O núcleo agora possui integração direta com o Google Authenticator (TOTP) e envio de códigos por e-mail, configurável via painel de usuário para segurança enterprise.

### 🌑 6. UI Reativa & Dark Mode
Experiência de single-page application (SPA). Abas carregam via requisições assíncronas (Live Fetch) e o design system nativo conta com variáveis CSS que suportam modos Claro e Escuro elegantes e consistentes.

---

Em breve, lançaremos na **[Wiki / Documentação](docs/)** tutoriais completos ensinando:
- *Como desenvolver o seu próprio Tema*
- *Como construir um Plugin do zero*

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
   O sistema detectará que não está configurado e redirecionará você para o **Assistente de Instalação**. Siga os passos simples na tela (banco de dados, usuário admin), e pronto!

---

## 📂 Arquitetura (Visão Geral)

```text
/
├── config/              # Configurações dinâmicas geradas pelo painel (plugins, DB)
├── public/              # Diretório público (assets, index.php)
├── storage/             # Arquivos gerados (logs de erro, cache)
└── src/
    ├── Core/            # O Cérebro do OS (Router, Eventos, Container, Exceptions)
    └── Plugins/         # Onde a mágica acontece.
        ├── auth/        # Sistema base de Autenticação e 2FA
        ├── clinic_pack/ # Super-pacote que transforma o sistema numa Clínica Médica!
        └── installer/   # O assistente de instalação (Auto-desativa após o uso)
```

## 🛠️ Contribuindo e Tutoriais

Nosso objetivo é transformar este projeto em um ecossistema. 
Você já pode acessar nosso **[Guia do Desenvolvedor (DEVELOPER_GUIDE.md)](DEVELOPER_GUIDE.md)** para aprender os fundamentos técnicos de como estender o sistema. A pasta `docs/` e a aba Wiki no GitHub receberão em breve tutoriais ainda mais avançados para programadores.

Feito com ☕ e focado na resiliência arquitetural.

