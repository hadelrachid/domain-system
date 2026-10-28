# 🔄 Sistema de Atualizações Automáticas (GitHub Releases — Modelo A)

O **Domain System** possui um subsistema nativo de verificação e atualização automática do núcleo (`SystemUpdater`), projetado especificamente para operar com segurança tanto em servidores dedicados quanto em hospedagens compartilhadas (como a **Hostinger**).

---

## 🏛️ Arquitetura Orientada a Contratos (Ring 0)

Todo o fluxo de atualização é governado pelos **25 contratos** em `src/Core/Contracts/Update/`:
- **Isolamento de Infraestrutura:** Os serviços de domínio (`src/Core/Update/Service/`) e o aplicativo de sistema (`src/SystemApps/SystemUpdater/`) não contêm chamadas diretas a `PDO`, `curl_*`, `ZipArchive` ou superglobais (`$_GET`, `$_POST`, `$_SESSION`), sendo auditados automaticamente por `UpdateArchitectureTest`.
- **Modelo A (Monorepo Oficial):** O núcleo e os 18 plugins/SystemApps oficiais compartilham a mesma versão (`2.5.0`) e são distribuídos em um pacote único assinado por SHA-256 (`domain-system-X.Y.Z.zip` + `domain-system-X.Y.Z.zip.sha256`).

---

## 🔐 Segurança e Proteção de Dados

1. **Allowlist HTTPS Estrita (`HostAllowlist` + `CurlHttpClient`):** Apenas os hosts `api.github.com`, `github.com`, `objects.githubusercontent.com` e `codeload.github.com` são aceitos sobre HTTPS com verificação TLS obrigatória.
2. **Verificação SHA-256 Dupla (`Sha256PackageVerifier`):** Confere o hash SHA-256 do arquivo `.zip` contra o `.sha256` da Release e valida individualmente o hash e o tamanho de cada arquivo listado no `manifest.json` dentro de um diretório de quarentena, bloqueando Zip-Slip, links simbólicos e *zip bombs*.
3. **Caminhos Protegidos (`CoreProtectedPaths`):** Arquivos sensíveis da instalação (`.env`, `.maintenance`, `config/`, `database.sqlite`, `storage/`, `temp/`, `uploads/`, `public/uploads/` e temas privados) **jamais** são sobrescritos. Novos arquivos de configuração padrão entram exclusivamente com `mode: default_if_missing`.
4. **Backup Antes de Aplicar + Auto-Rollback:** Antes de entrar em manutenção ou tocar em qualquer arquivo, o sistema gera `storage/backups/core/<id>/code.zip` e o dump do banco (`SqliteDatabaseBackup` ou `MysqlDatabaseBackup`). Qualquer falha posterior aciona o rollback automático de código e banco.

---

## 🖥️ Uso pelo Painel Administrativo

1. Acesse **Configurações Globais → aba Atualizações** (`/admin/settings/updates`) ou clique no card **Versão do Sistema** no Painel (`/admin`).
2. Clique em **🔍 Verificar agora** para consultar imediatamente a última Release estável no GitHub.
3. Quando houver versão superior disponível, revise o changelog, os avisos de pré-checagem e clique em **🚀 Atualizar para vX.Y.Z**.
4. Acompanhe a barra de progresso etapa a etapa (`Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Done`).

---

## 💻 Uso via Terminal (OS-CLI)

```bash
# Verificar se há nova versão no GitHub
php os-cli.php system:check

# Consultar estado atual do atualizador
php os-cli.php system:status

# Executar atualização completa passo a passo
php os-cli.php system:update --yes

# Reverter última atualização ou restaurar um backup específico
php os-cli.php system:rollback
php os-cli.php system:rollback <backup-id>

# Executar verificação de saúde do núcleo e do banco
php os-cli.php system:health
```

---

## 📦 Gerando um Pacote de Release Oficial

Para empacotar uma nova versão localmente ou no CI (`.github/workflows/release.yml`):

```bash
php tools/build-release.php
```

O script valida a presença do `vendor/autoload.php`, lê `DS_VERSION` em `bootstrap.php`, aplica as exclusões de `release/exclude.txt`, incorpora o contrato de banco de `release/schema-expects.json` e gera:
- `dist/domain-system-X.Y.Z.zip`
- `dist/domain-system-X.Y.Z.zip.sha256`
