# 📚 Documentação Oficial: Domain System (v2.3.0)

Bem-vindo à documentação oficial do **Domain System**.

Aqui você encontrará a auditoria da nossa arquitetura, os princípios de design do nosso Kernel, contratos de auto-descoberta (`ManualModuleInterface`) e guias práticos para desenvolvedores.

## 🗂️ Índice

### 1. Guias Arquiteturais
- [🛡️ Auditoria do Sistema](auditoria.md) - Uma visão profunda da injeção de dependências, eventos, roteamento e segurança (v2.3.0).
- [📈 Changelog de Atualizações](CHANGELOG.md) - Histórico de versões e melhorias implementadas.
- [🌌 O Multiverso de Plugins](multiverse.md) - Como a plataforma lida com módulos e isolamento Ring 0 / Ring 3.
- [📜 Contratos e Interfaces](contratos.html) - `PluginInterface`, `OsExtensionInterface`, `ManualModuleInterface` e `DashboardWidgetProviderInterface`.

### 2. Tutoriais e Desenvolvimento (SDK v2.3)
- [🧩 Guia Interativo de Criação de Plugins](tutorial-dev.html) - Aprenda a usar `OsExtensionInterface`, Widgets e Auto-Descoberta de Manuais (`manual/module.php`).
- [🎨 Guia Interativo de Criação de Temas](tutorial-theme.html) - Aprenda a usar o `ThemeManager` para criar layouts isolados sem acoplamento back-end.
- [🎓 Academy LMS Híbrido](../src/Plugins/academy/manual/AcademyManualModule.php) - Implementação de referência SOLID com Editor em Blocos, Preview Dinâmico e Simulador Multi-Dispositivos (acessível no Cockpit em `/admin/manual`).
