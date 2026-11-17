# Por Dentro do Domain System (v2.7.0)

> **Guia técnico, tutorial passo a passo e caderno de auditoria (Lógica & Segurança)**  
> Escrito para quem quer entender cada peça do Domain System do zero — mesmo sem experiência prévia com Padrões de Projeto ou arquitetura SOLID — e usar esse conhecimento para encontrar falhas de lógica e brechas de segurança.

---

## Sumário dos Capítulos

| Cap. | Título | Arquivos-Chave Analisados | Status |
| :---: | :--- | :--- | :---: |
| **00** | [Introdução — O Mapa do Território e Vocabulário Base](00-introducao.md) | Visão geral do repositório | ✅ Concluído |
| **01** | [O "Start" no Servidor — `.htaccess`, Front Controller e `public/index.php`](01-start-servidor.md) | `.htaccess`, `public/.htaccess`, `public/index.php`, `public/maintenance.php`, `public/reset_opcache.php` | ✅ Concluído |
| **02** | A Ignição do SO — `bootstrap.php`, `.env`, Autoload PSR-4 e Rede de Proteção (`ErrorHandler` & `CircuitBreaker`) | `bootstrap.php`, `composer.json`, `src/Core/Error/` | ⏭️ Próximo |
| **03** | O Coração e o Padrão Singleton na Prática | `src/Core/Application.php` | ⏳ Pendente |
| **04** | O Almoxarifado (`Container` DI), `CoreServiceProvider` e os Contratos SOLID | `src/Core/Container/`, `src/Core/Providers/`, `src/Core/Contracts/` | ⏳ Pendente |
| **05** | O Ciclo de Boot — `SystemApps`, `PluginManager` e o Rádio de Eventos (`EventDispatcher`) | `src/Core/Plugin/`, `src/Core/Events/`, `src/SystemApps/` | ⏳ Pendente |
| **06** | A Jornada da Requisição — `Request`, `HttpKernel`, `Router`, `Middlewares` e `Response` | `src/Core/Http/`, `src/Core/Routing/` | ⏳ Pendente |
| **07** | Segurança na Prática — Sessão, Autenticação, ACL, CSRF, `RouteBoundaryGuard` e Redirects Seguros | `src/Core/Security/`, `src/SystemApps/auth/` | ⏳ Pendente |
| **08** | Banco de Dados, Armazenamento Atômico, Temas e Workspaces | `src/Core/Database/`, `src/Core/Storage/`, `src/Core/Theme/`, `src/Core/Workspace/` | ⏳ Pendente |
| **09** | Checklist Consolidado de Auditoria (Lógica & Segurança) | Síntese de todos os achados | ⏳ Pendente |

---

## Como ler este guia

1. **Leia na ordem dos capítulos:** cada capítulo acompanha o caminho real que uma requisição percorre no servidor, do milissegundo zero até a resposta no navegador.
2. **Use a seção "Lupa de Auditoria" no fim de cada capítulo:** lá registramos falhas reais de lógica, riscos de segurança e pontos de melhoria descobertos diretamente no código.
3. **Consulte o código lado a lado:** todos os trechos apontam para os arquivos e linhas reais do projeto.
