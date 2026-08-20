# Domain System Kernel 🚀

[🇧🇷 Versão em Português](#-português) | [🇺🇸 English Version](#-english)

---

## 🇧🇷 Português

**Domain System** é um *Kernel* ultra-rápido, modular e de altíssima coesão construído do zero em PHP puro. Inspirado na flexibilidade do ecossistema do WordPress, porém utilizando arquitetura de software moderna, Test-Driven Development (TDD) e princípios SOLID estritos.

**Nota: 10/10 em auditorias estruturais!**

### 🏗️ Arquitetura

- **Kernel (Core)**: Container, EventDispatcher, Router, PluginManager
- **Plugins**: Lógica de negócio (Database, Auth, SystemAdmin, Patients, Appointments, MedicalRecords, Settings...)
- **Themes**: Apresentação (Tema default com layout admin SSR)

### 🚀 Como Iniciar

1. Instale as dependências caso existam, ou apenas rode via servidor PHP.
2. Acesse o painel pelo navegador.
3. Se precisar de um admin inicial via script:
```bash
php src/Plugins/auth/scripts/create_admin.php
```

### 📚 Documentação Oficial

Consulte a pasta `docs/` no projeto ou acesse os manuais em HTML:
- [Propósito e Visão](docs/proposito.html)
- [Arquitetura](docs/arquitetura.html)
- [Contratos SOLID](docs/contratos.html)
- [Criando Plugins](docs/criando-plugins.html)

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

### 🏗️ Architecture

- **Kernel (Core)**: Container, EventDispatcher, Router, PluginManager
- **Plugins**: Business Logic (Database, Auth, SystemAdmin, Patients, Appointments, MedicalRecords, Settings...)
- **Themes**: Presentation Layer (Default theme with SSR admin layout)

### 📚 Official Documentation

Check the `docs/` folder in the project for detailed HTML manuals covering Architecture, SOLID Contracts, and Plugin Development.

### ⚖️ License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
