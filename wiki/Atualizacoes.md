# 🔄 Sistema de Atualizações Automáticas (GitHub Releases — Modelo A)

O **Domain System** possui um subsistema nativo de verificação e atualização do núcleo (`SystemUpdater`), projetado para operar com segurança em servidores dedicados e ambientes de hospedagem compartilhada (como a Hostinger).

---

## 🏛️ Arquitetura Orientada a Contratos

O fluxo de atualização é regido pelos **25 contratos** definidos em `src/Core/Contracts/Update/`:
- **Isolamento de Infraestrutura:** Os serviços de domínio (`src/Core/Update/Service/`) e o aplicativo de sistema (`src/SystemApps/SystemUpdater/`) não contêm chamadas diretas a superglobais (`$_GET`, `$_POST`, `$_SESSION`) ou rotinas não desacopladas de `PDO`, `curl_*` e `ZipArchive`, sendo validados por testes automatizados em `UpdateArchitectureTest`.
- **Modelo A (Monorepo):** O núcleo e as extensões oficiais acompanham o mesmo versionamento (`2.5.0`) e são distribuídos em pacotes assinados por SHA-256 (`domain-system-X.Y.Z.zip` + `domain-system-X.Y.Z.zip.sha256`).

---

## 🔐 Segurança e Integridade

1. **Lista de Hosts Autorizados (`HostAllowlist` + `CurlHttpClient`):** Apenas os domínios `api.github.com`, `github.com`, `objects.githubusercontent.com` e `codeload.github.com` são aceitos, com conexões HTTPS e verificação TLS ativa.
2. **Dupla Validação SHA-256 (`Sha256PackageVerifier`):** Valida a integridade do arquivo `.zip` contra o hash publicado na Release e inspeciona individualmente cada arquivo listado no `manifest.json` em um diretório temporário de quarentena, prevenindo Zip-Slip e links simbólicos maliciosos.
3. **Preservação de Dados Locais (`CoreProtectedPaths`):** Arquivos essenciais da instalação local (`.env`, `.maintenance`, `config/`, `database.sqlite`, `storage/`, `temp/`, `uploads/`, `public/uploads/` e temas customizados) **nunca** são sobrescritos. Arquivos padrão de configuração novos utilizam o modo `default_if_missing`.
4. **Backup Preventivo e Rollback Automático:** Antes de iniciar a substituição de arquivos ou migrações de banco, o sistema gera uma cópia de segurança em `storage/backups/core/<id>/code.zip` e realiza o backup do banco (`SqliteDatabaseBackup` ou `MysqlDatabaseBackup`). Em caso de interrupção ou falha, o rollback é acionado automaticamente.

---

## 🖥️ Atualização pelo Painel Administrativo

1. Acesse **Configurações Globais → aba Atualizações** (`/admin/settings/updates`) ou selecione o indicador **Versão do Sistema** no Painel (`/admin`).
2. Clique em **🔍 Verificar agora** para consultar a última versão estável disponibilizada no GitHub Releases.
3. Havendo versão disponível, revise o changelog e os itens de pré-verificação antes de confirmar.
4. O progresso é exibido etapa por etapa:
   `Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Concluído`.

---

## 💻 Atualização via Linha de Comando (CLI)

O utilitário `os-cli.php` na raiz do projeto permite executar todas as rotinas pelo terminal:

```bash
# Verificar se há nova versão disponível
php os-cli.php system:check

# Exibir status do sistema de atualização
php os-cli.php system:status

# Executar a atualização completa com confirmação automática
php os-cli.php system:update --yes

# Reverter a última atualização ou restaurar um backup específico
php os-cli.php system:rollback
php os-cli.php system:rollback <backup-id>

# Executar verificação de saúde da aplicação e integridade do banco
php os-cli.php system:health
```

---

## 📦 Empacotamento de Releases

Para gerar o pacote compactado de uma nova versão:

```bash
php tools/build-release.php
```

O script lê a versão definida em `bootstrap.php` (`DS_VERSION`), aplica as regras de exclusão de `release/exclude.txt`, inclui a definição de esquema de `release/schema-expects.json` e gera os artefatos na pasta `dist/`:
- `dist/domain-system-X.Y.Z.zip`
- `dist/domain-system-X.Y.Z.zip.sha256`
