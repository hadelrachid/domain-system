# 🎓 Tutoriais para Desenvolvedores (Domain-System v2.5.0)

Aqui estão reunidos os guias passo-a-passo ensinando como utilizar e expandir o ecossistema do **Domain System** (uma alternativa leve e resiliente de **~30MB** em servidores Linux).

## O que você vai aprender?

1. **[Desenvolvimento de Plugins & CLI Scaffolding (v2.5.0)](criando-plugins.md):**
   - Gerando a árvore completa de contratos com `php os-cli.php make:plugin <slug>` em `src/Plugins/<slug>`
   - Implementando `OsExtensionInterface` e `AbstractPlugin`
   - Registrando Rotas com Filtros (ACL) e utilizando Injeção de Dependência (`Container`) sem tocar em `$_SESSION` ou `PDO` no Controller
   - **Auto-Descoberta de Manuais (`ManualModuleInterface`):** Como criar `manual/module.php` para que seu plugin apareça automaticamente no menu **Manual de Instruções (`/admin/manual`)** do Cockpit.
   - **Vibe Coding nos Trilhos (`AI_CONTRACT_GUIDE.md`):** Como instruir IAs (ChatGPT, Claude, DeepSeek, Gemini, Copilot, Cursor, Cline, Kilo Code) a programar respeitando os contratos.

2. **[Sistema de Prioridades e Escotilha de Emergência](02-a-escotilha-de-emergencia.html):**
   - Como o `EventDispatcher` e as prioridades de Hooks funcionam
   - O padrão *Emergency Hatch* para recuperação de missão crítica.

3. **[Pacotes de Plugins (Hubs) & Academy LMS](03-pacotes-de-plugins.html):**
   - Como estruturar pacotes modulares e conhecer a arquitetura de referência SOLID do **Academy LMS Híbrido** (Fábrica Automática de Pastas e Aulas `.php` em `pages/estrutura-de-dados.php` $\rightarrow$ `/cursos/estrutura-de-dados`, Editor em 8 Blocos e 3 Perfis de Layout Mobile).

