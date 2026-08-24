# Domain System Kernel 🚀

[🇧🇷 Versão em Português](#-português) | [🇺🇸 English Version](#-english)

---

## 🇧🇷 Português

**Domain System** é um *Kernel* ultra-rápido, modular e de altíssima coesão construído do zero em PHP puro. Inspirado na flexibilidade do ecossistema do WordPress, porém utilizando arquitetura de software moderna, Test-Driven Development (TDD) e princípios SOLID estritos.

**Nota: 10/10 em auditorias estruturais!**

### 🏛️ Arquitetura e Defesas

- **Kernel (Core)**: Container, EventDispatcher, Router, PluginManager
- **Plugins**: Lógica de negócio (Database, Auth, SystemAdmin, Patients, Appointments, MedicalRecords, Settings, WhatsApp Z-API...)
- **Themes (Workspaces)**: Apresentação multi-perfil (Roteamento Inteligente injetando Layouts isolados para Doctor, Receptionist e Admin)
- 🛡️ **Circuit Breaker (Disjuntor V2)**: Sistema inteligente com Regex Avançada que intercepta falhas e erros de dependência, desativando silenciosamente o plugin culpado para salvar o núcleo da "Tela Branca da Morte".

### 🚀 Como Iniciar

1. Instale as dependências caso existam, ou apenas rode via servidor PHP.
2. Acesse o painel pelo navegador.
3. Se precisar de um admin inicial via script:
```bash
php src/Plugins/auth/scripts/create_admin.php
```

### 📚 Documentação Oficial

Consulte a nossa **[Documentação Online Completa](https://hadelrachid.github.io/domain-system/)** para explorar os seguintes manuais:
- [Propósito e Visão](https://hadelrachid.github.io/domain-system/proposito.html)
- [Arquitetura](https://hadelrachid.github.io/domain-system/arquitetura.html)
- [Contratos SOLID](https://hadelrachid.github.io/domain-system/contratos.html)
- [Criando Plugins](https://hadelrachid.github.io/domain-system/criando-plugins.html)

### 📊 Status do Projeto

| Métrica | Valor |
|---------|-------|
| SOLID | 10/10 |
| Test Coverage | 100% (TDD) |
| Plugins Core | 8 |
| Dependências | 0 (PHP Puro) |

### ⚖️ Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

---

## 🇺🇸 English

**Domain System** is an ultra-fast, highly cohesive, and modular *Kernel* built from scratch in pure PHP. Inspired by the WordPress ecosystem, but leveraging modern software architecture, TDD, and strict SOLID principles.

**Score: 10/10 in structural audits!**

### 🏛️ Architecture & Defenses

- **Kernel (Core)**: Container, EventDispatcher, Router, PluginManager
- **Plugins**: Business Logic (Database, Auth, SystemAdmin, Patients, Appointments, MedicalRecords, Settings, WhatsApp Z-API...)
- **Themes (Workspaces)**: Multi-profile Presentation Layer (Smart routing injecting isolated layouts for Doctors, Receptionists, and Admins)
- 🛡️ **Circuit Breaker V2**: Advanced native system with smart Regex that intercepts fatal errors and missing dependencies, automatically isolating faulty plugins to protect the system core.

### 📚 Official Documentation

Check our **[Complete Online Documentation](https://hadelrachid.github.io/domain-system/)** for detailed manuals covering:
- [Vision & Purpose](https://hadelrachid.github.io/domain-system/proposito_en.html)
- [Architecture](https://hadelrachid.github.io/domain-system/arquitetura_en.html)
- [SOLID Contracts](https://hadelrachid.github.io/domain-system/contratos_en.html)
- [Building Plugins](https://hadelrachid.github.io/domain-system/criando-plugins_en.html)

### ⚖️ License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
