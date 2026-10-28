> 🇧🇷 [Ler em Português](#-modo-de-recuperação-administrativa-fallback-auth) | 🇺🇸 [Read in English](#-administrative-recovery-mode-fallback-auth)

---

# 🇧🇷 Modo de Recuperação Administrativa (Fallback Auth)

Em aplicações modulares, a indisponibilidade temporária de um módulo de autenticação ou de uma conexão de banco de dados pode impedir o login de operadores no painel administrativo (`/admin`).

Para evitar cenários de bloqueio administrativo (*lockout*), o **Domain System** disponibiliza um mecanismo nativo de contingência no núcleo: a rota de recuperação `/admin/emergency`.

---

## 🎯 Cenário de Aplicação

O modo de recuperação é utilizado em situações excepcionais, tais como:
1. O módulo de autenticação (`Auth`) foi temporariamente desativado ou sofreu falha crítica durante o boot.
2. O banco de dados está inacessível ou corrompido, impossibilitando a consulta de credenciais de usuários.
3. O administrador necessita de acesso ao painel de supervisão para desativar extensões com conflito ou restaurar configurações.

---

## ⚙️ Fluxo de Funcionamento

1. **Detecção no Roteamento:** Durante o despacho de rotas administrativas (`/admin/*`), o sistema verifica se o serviço de autenticação está ativo e operacional. Se o serviço não responder, o acesso é redirecionado para `/admin/emergency`.
2. **Interface Minimalista de Contingência:** A tela de recuperação é renderizada diretamente pelo núcleo, sem depender de temas, assets externos ou consultas a banco de dados.
3. **Autenticação por Chave de Aplicação (`APP_KEY`):** Em vez de validar e-mail e senha no banco, a autenticação exige a chave de segurança definida no arquivo de ambiente do servidor (`.env`).
4. **Sessão Administrativa Temporária:** Após a validação da chave, o núcleo emite uma sessão temporária via `SessionManagerInterface`, concedendo permissão restrita para acessar o painel de gerenciamento de extensões, logs de supervisão e ferramentas de restauração.

---

## 🔒 Considerações de Segurança

- A rota de contingência só aceita a `APP_KEY` configurada no ambiente do servidor.
- O acesso a essa chave exige permissões diretas de leitura no sistema de arquivos do servidor, garantindo que apenas quem possui acesso à infraestrutura possa utilizá-la.
- Recomenda-se manter o arquivo `.env` com permissões restritas de leitura (ex: `chmod 600 .env`) no servidor de hospedagem.

<br><br><br><hr>

# 🇺🇸 Administrative Recovery Mode (Fallback Auth)

In modular applications, the temporary unavailability of an authentication module or a database connection can prevent operators from logging into the administration panel (`/admin`).

To prevent administrative lockout scenarios, **Domain System** provides a built-in contingency mechanism in its core: the `/admin/emergency` recovery route.

---

## 🎯 Use Cases

Administrative recovery is designed for exceptional operational situations, such as:
1. The authentication module (`Auth`) was temporarily deactivated or encountered a critical failure during initialization.
2. The primary database is unreachable or corrupted, preventing credential verification.
3. The administrator needs immediate panel access to deactivate conflicting extensions or restore system configurations.

---

## ⚙️ How It Works

1. **Route Detection:** While dispatching administrative routes (`/admin/*`), the system verifies whether the authentication service is active and responsive. If unavailable, the request is redirected to `/admin/emergency`.
2. **Minimalist Contingency Interface:** The recovery view is rendered directly by the core, without external theme assets or database queries.
3. **Application Key Authentication (`APP_KEY`):** Instead of querying users in the database, the mechanism authenticates using the `APP_KEY` configured in the server's `.env` environment file.
4. **Temporary Administrative Session:** Once the key is verified, the core issues a temporary session via `SessionManagerInterface`, providing restricted access to extension management, supervision logs, and recovery tools.

---

## 🔒 Security Guidelines

- The emergency route exclusively validates against the `APP_KEY` set in the server environment.
- Access to this key requires filesystem-level read permissions, ensuring only administrators with server-level access can perform recovery.
- Keep the `.env` file protected with strict filesystem permissions (e.g., `chmod 600 .env`) in production environments.
