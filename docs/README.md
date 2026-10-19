# 📚 Documentação Oficial: Domain System (v2.3.0)

Bem-vindo à documentação oficial do **Domain System** (Ambiente Operacional Web ultraleve com **~30MB** descompactado em servidores Linux).

Aqui você encontrará a auditoria da nossa arquitetura, os princípios de design do nosso Kernel, contratos de auto-descoberta (`ManualModuleInterface`, `SchemaDoctorInterface`, `JsonStoreInterface`), a fábrica automática de aulas `.php` (`LessonFileScaffolderInterface`) e o guia oficial para desenvolvimento assistido por IA (*Vibe Coding on Rails*).

## 🗂️ Índice

### 1. Guias Arquiteturais & Contexto para I.A.
- [🤖 AI Contract Guide (`AI_CONTRACT_GUIDE.md`)](../AI_CONTRACT_GUIDE.md) - **Leitura Obrigatória para IAs** (ChatGPT, Claude, DeepSeek, Gemini, Copilot, Cursor, Cline, Kilo Code): como criar plugins, páginas e aulas respeitando os contratos do Ring 3.
- [🛡️ Auditoria do Sistema](auditoria.md) - Uma visão profunda da injeção de dependências, eventos, roteamento, `AtomicJsonStore`, `SchemaDoctorInterface` e segurança (v2.3.0).
- [📈 Changelog de Atualizações](CHANGELOG.md) - Histórico de versões e lapidações implementadas na v2.3.0.
- [🌌 O Multiverso de Plugins](multiverse.md) - Como a plataforma lida com módulos e isolamento Ring 0 / Ring 3.
- [📜 Contratos e Interfaces](contratos.html) - `PluginInterface`, `OsExtensionInterface`, `ManualModuleInterface`, `SchemaDoctorInterface`, `JsonStoreInterface` e `DashboardWidgetProviderInterface`.

### 2. Tutoriais e Desenvolvimento (SDK v2.3.0)
- [🧩 Guia Interativo de Criação de Plugins](tutorial-dev.html) - Aprenda a usar `php os-cli.php make:plugin <slug>`, `OsExtensionInterface`, Widgets e Auto-Descoberta de Manuais (`manual/module.php`).
- [🎨 Guia Interativo de Criação de Temas](tutorial-theme.html) - Aprenda a usar o `ThemeManager` e `SafeThemeResolver` para criar layouts isolados sem acoplamento back-end.
- [🎓 Academy LMS Híbrido](../src/Plugins/academy/manual/AcademyManualModule.php) - Implementação de referência SOLID com Fábrica Automática de Pastas e Aulas `.php` (`pages/estrutura-de-dados.php` $\rightarrow$ `/cursos/estrutura-de-dados`), Editor em Blocos (8 tipos), Preview Dinâmico e 3 Perfis de Layout Mobile (`drawer`, `bottom_bar`, `cinema`).

