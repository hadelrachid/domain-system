# Histórico de Versões (Changelog)

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/), 
e este projeto adota o [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---
[ 🇺🇸 Read in English ](CHANGELOG-en.md)
## [1.1.0] - A Era da Resiliência Arquitetural - 2026-08-24

Esta atualização introduz proteções críticas no núcleo do sistema, além de resolver débitos técnicos identificados por auditoria profunda.

### Adicionado
- **QTA (Automatic Transfer Switch):** Função de Último Suspiro acoplada ao `register_shutdown_function` para interceptar Erros Fatais e Quedas de Memória Absoluta, ejetando automaticamente o plugin responsável antes da morte térmica do PHP.
- **Método genérico de Upsert:** Adicionado `upsert()` ao `QueryBuilder` nativo do banco de dados SQLite, permitindo operações "Insert On Conflict Do Update" de forma orientada a objetos.
- **Rotas de Prontuário Médico:** Novas rotas `GET` e `POST` para `/admin/appointments/record`.

### Modificado
- **Arquitetura 2FA (Princípios SOLID):** Extração total da lógica de envio (E-mail, Aplicativo) do `TwoFactorService` para suas respectivas interfaces e classes provedoras autônomas (Plugs).
- **Controlador de Configurações (SettingsController):** Remoção de código SQL cruzado (PDO) a favor do novo método `upsert()` do QueryBuilder.
- **Caminho de Uploads:** Substituição de caminhos engessados (`dirname(__DIR__, 4)`) pela constante `BASE_PATH`.

### Removido
- Lixo residual e scripts soltos de testes na raiz (`test_plugins.php` e `test_record.php`).
- **Código morto inatingível:** Limpeza de múltiplos comandos `exit;` perdidos após retornos na `ApiController`.
- Simulação de mock hardcoded de médicos (convertido para TODO para futura integração real com WordPress).

### Segurança e Desenvolvimento
- **Dev Simulator Plugin:** Um novo plugin isolado capaz de sequestrar a fiação mestre de e-mails em ambiente de desenvolvimento, interceptando códigos 2FA e gravando-os em `temp/auth-2fa.txt` para prevenir vazamento de dados reais de usuários.
- **Bomb Plugin:** Plugin malicioso (desativado) mantido no projeto como prova viva para testar a ativação dos sistemas Disjuntor V2 e QTA.

---

## [1.0.0] - O Nascimento do Motor Orgânico - 2026-08-23

Lançamento inicial do Domain System.

### Adicionado
- Arquitetura Mestre baseada em Kernel & Plugins.
- Container de Injeção de Dependências Customizado.
- `EventDispatcher` para comunicação desacoplada entre módulos (Hooks).
- Roteador Nativo e Suporte a SQLite.
- Plugins base inaugurais: `auth`, `appointments`, `doctors`, `patients`, `settings`, `triage`, `SystemAdmin`.
- Disjuntor V1 (Circuit Breaker básico) para isolamento de falhas de Exceptions de Plugins.
- Sistema de controle de acesso (ACL) focado em Workspaces (Médicos, Recepcionistas, Advogados).
