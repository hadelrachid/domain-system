# 🎓 Tutoriais para Desenvolvedores (Domain-System v2.3.0)

Aqui estão reunidos os guias passo-a-passo ensinando como utilizar e expandir o ecossistema do **Domain System**.

## O que você vai aprender?

1. **[Desenvolvimento de Plugins (v2.3)](criando-plugins.md):**
   - Criando sua estrutura base em `src/Plugins/meu_plugin`
   - Implementando `OsExtensionInterface` e `AbstractPlugin`
   - Registrando Rotas com Filtros (ACL) e utilizando Injeção de Dependência (`Container`)
   - **Auto-Descoberta de Manuais (`ManualModuleInterface`):** Como criar `manual/module.php` para que seu plugin apareça automaticamente no menu **Manual de Instruções (`/admin/manual`)** do Cockpit.

2. **[Sistema de Prioridades e Escotilha de Emergência](02-a-escotilha-de-emergencia.html):**
   - Como o `EventDispatcher` e as prioridades de Hooks funcionam
   - O padrão *Emergency Hatch* para recuperação de missão crítica.

3. **[Pacotes de Plugins (Hubs) & Academy LMS](03-pacotes-de-plugins.html):**
   - Como estruturar pacotes modulares e conhecer a arquitetura de referência SOLID do **Academy LMS Híbrido** (HTML/Markdown puro + Editor em Blocos com Preview em Tempo Real e Simulador Multi-Dispositivos).
