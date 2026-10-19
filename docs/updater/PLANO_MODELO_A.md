# Plano de Implementação — Atualizador do Núcleo (Modelo A)

**Modelo A:** um único repositório e uma única Release. O pacote traz **o núcleo e todos os plugins oficiais juntos**, e a atualização é feita de uma vez, com um botão **Atualizar** no painel admin.

> **Para quem vai implementar (Gemini):** este plano é executado **um passo por vez**. Não pule passos, não antecipe código de passos futuros e não "melhore" o que não foi pedido. Em caso de dúvida, **pergunte ao usuário** em vez de decidir (lista na seção 8).

**Leitura obrigatória antes do Passo 0:**
1. [`AI_CONTRACT_GUIDE.md`](../../AI_CONTRACT_GUIDE.md) — regras de contrato, Ring 0/Ring 3, DI.
2. [`CONTRATOS.md`](CONTRATOS.md) — assinaturas das interfaces (catálogo). **Este plano prevalece sobre o catálogo** onde houver conflito (seção 4 lista as emendas).
3. Esta página inteira.

---

## 1. Escopo

| Dentro (Modelo A) | Fora (não implementar agora) |
|---|---|
| Aviso de nova versão: aba em Configurações, widget no painel, notificação | Atualização individual de plugins (`PluginUpdaterInterface`, `tag_prefix`, `update_source` no `plugin.json`) |
| Download, verificação (SHA-256 + `manifest.json`), pré-checagem | Assinatura Ed25519 |
| Verificação e atualização do schema do banco, por contrato | Atualização automática por cron |
| Backup de código e de banco, manutenção, aplicação, saúde, rollback | Canais beta, downgrade |
| CLI (`system:*`) e pipeline de Release (GitHub Actions) | Atualização de plugins de terceiros |

**Versão:** o sistema continua em `2.3.0`. Não altere `DS_VERSION`, `composer.json` nem badges. O número da primeira Release com o atualizador é decisão do usuário (seção 8).

---

## 2. Estado atual do repositório (verificado em 19/10/2026)

| # | Fato | Consequência para o plano |
|---|---|---|
| 1 | Tags `v2.1.0`, `v2.2.0`, `v2.3.0` existem. A tag **`v2.3.0` aponta para `24e6c53`, 5 commits atrás do `main`** | A Release `v2.3.0` publicada está **defasada**. **Nunca mover tag publicada sozinho**; decidir com o usuário (seção 8) |
| 2 | Já existe `.github/workflows/release.yml` (tag `v*`): `composer install --no-dev`, apaga arquivos de dev, gera `domain-system-core.zip` (árvore inteira), `radio_online.zip` e `clock_widget.zip` | **Evoluir** este workflow, não criar outro. Manter os três assets atuais intactos |
| 3 | `.github/workflows/tests.yml` roda `composer test` em push/PR (PHP 8.2) | O `release.yml` deve rodar os testes antes de publicar |
| 4 | Modo manutenção **já existe no Core**: arquivo `<raiz>/.maintenance`, saída antecipada em `public/index.php` (linhas ~59–75) e `MaintenancePolicy`. Rotas `/admin`, `/login`, `/logout`, `/api`, `/setup` são isentas. O botão fica em `SettingsController::toggleMaintenance` (plugin `settings`) | O atualizador **não cria** outro mecanismo: `MaintenanceModeInterface` é um adaptador fino sobre o mesmo `.maintenance`. As rotas de `/admin/...` do atualizador continuam acessíveis durante a manutenção |
| 5 | `MigrationRunner::runForPlugin()` **segue para as próximas migrations** após uma falha | Corrigir no Passo 0 (parar na primeira falha) |
| 6 | `MigrationRunner` cria `kernel_migrations` no construtor e conhece `sqlite`/`mysql` | O plano de schema é **somente leitura** e não usa o runner. Só o adaptador de execução o usa |
| 7 | Só o `academy` tem `migrations/`. As demais tabelas são criadas em `activate()` via `core.db.schema` | Migrations do núcleo ficam em `src/Core/Database/migrations/` (escopo `__core__`); plugins oficiais usam a própria `migrations/` (escopo = slug) |
| 8 | `StateSnapshotManager` só copia `plugins.json`, `disarmed.json`, `database.sqlite` | Insuficiente para MySQL e para código. Backup passa a ser contrato |
| 9 | `ZipArchiveExtractor` exige `plugin.json` e bloqueia pacotes `core` | **Não reutilizar** para o núcleo. Criar extrator próprio (Passo 8) |
| 10 | Template de SystemApp: `src/SystemApps/SystemMonitor/` (`plugin.json` com `"core": true`, `"entrypoint": "Plugin"`; `Plugin.php`; `Controllers/`; `views/`) | Copiar essa estrutura para `SystemUpdater` |
| 11 | Hooks em uso: `router.register`, `admin.menu`, `dashboard.register_widgets` (registrados em `osRegister` com `listenHook` e usados em `osBoot` com `onHook`) | Conferir lendo `settings/Plugin.php` e `SystemMonitor/Plugin.php`. **Não inventar nomes de hook** |
| 12 | Existem testes em `tests/{Architecture,Integration,Regression,Support,UI,Unit}`; baseline: **183 testes, 751 asserções, 4 warnings** | Cada passo mantém a suíte verde e **aumenta** a contagem |
| 13 | `composer.json` exige `php >=8.0`; o código já usa recursos do PHP 8.1 | Subir para `>=8.1` (Passo 0) |

---

## 3. Regras para o implementador

**Processo (por passo):**
1. Leia os arquivos citados no passo. **Não presuma assinaturas: confira.**
2. Implemente **só** o que o passo descreve.
3. Rode a suíte: `php -dextension=zip phpunit.phar` (a extensão `zip` é necessária). Tudo verde antes de seguir.
4. Faça **commit local** com a mensagem `feat(updater): passo N — <resumo>`.
5. **Não faça `git push`** sem autorização do usuário.
6. Ao terminar, responda com: arquivos criados/alterados, testes novos, resultado da suíte, e dúvidas pendentes.

**Código:**
- `declare(strict_types=1);` em todo arquivo novo; PHP ≥ 8.1; objetos de valor `final readonly`.
- Interfaces em `src/Core/Contracts/Update/`; objetos de valor, enums e exceções em `src/Core/Update/{Value,Enum,Exception}/`.
- **Ring 0 sem banco e sem SO:** classes em `src/Core/Update/Service/` e em `src/SystemApps/SystemUpdater/` **não podem** conter `PDO`, `sqlite`, `mysql`, `curl_`, `ZipArchive`, `file_put_contents`, `unlink(`, `rename(`, `exec(`, nem superglobais. Quem toca em rede, arquivo ou banco são os **adaptadores** (`src/Core/Update/Infrastructure/`, `src/Core/Database/Update/`, `src/SystemApps/Database/Backup/`). Há teste de arquitetura para isso (Passo 2).
- Os serviços recebem **interfaces** no construtor. Nada de `new` de classe concreta dentro de serviço.
- Caminhos: sempre `/` e `str_replace('\\', '/', ...)`. O desenvolvimento é no Windows (XAMPP) e a produção é Linux.
- Textos de interface em **português do Brasil**; todo texto externo (changelog, mensagens do GitHub) é escapado com `htmlspecialchars`.
- Controllers: sem `$_SESSION`, `$_GET`, `$_POST`, `$_SERVER` (testes existentes já proíbem).
- **Falha fechada:** qualquer dado inesperado aborta com exceção tipada. Nunca "tentar seguir".

**Proibido:** alterar versão; mover tags; mexer em arquivos fora do escopo do passo; remover testes; desabilitar teste que falha; tocar em `.env`, `database.sqlite`, `config/*.json` reais durante os testes (usar diretórios temporários).

---

## 4. Emendas ao catálogo de contratos (prevalecem)

1. **Local dos tipos:** interfaces em `Contracts/Update/`; `Enum`, `Value`, `Exception` em `src/Core/Update/`.
2. **Manifest, arquivos:** cada entrada de `files[]` ganha `mode`: `replace` (padrão) ou `default_if_missing` (copiar só se o arquivo não existir; usado para novos padrões em `config/`).
3. **Manifest, migrations:** cada entrada de `migrations[]` ganha `scope`: `__core__` ou o slug de um plugin empacotado.
4. **Manutenção:** `MaintenanceModeInterface` é adaptador do `.maintenance` existente (achado 4). Corrige o achado 4 do catálogo, que dizia que a manutenção estava só no plugin `settings`.
5. **Saúde em duas fases:** o navegador chama um endpoint novo (requisição nova, código novo) que grava o `HealthReport` no estado; `advance()` apenas lê o resultado. Evita chamada HTTP do servidor para ele mesmo (pode ser bloqueada na hospedagem).
6. **Hash do pacote:** vem do asset `domain-system-X.Y.Z.zip.sha256` da Release (conteúdo: o hash hexadecimal de 64 caracteres).
7. **Fora do escopo:** `PluginUpdaterInterface`, `UpdateTargetResolverInterface::plugins()`, `update_source`, assinatura Ed25519. Interfaces existentes no catálogo que só servem a isso **não são criadas**.
8. **Compatibilidade de plugin (`os_version`):** só gera **aviso** (nunca bloqueio) quando o major do `os_version` de um plugin **não empacotado** difere do major do novo núcleo.

---

## 5. Estrutura-alvo

```
src/Core/Contracts/Update/            interfaces (só interfaces)
src/Core/Update/
  Enum/ Value/ Exception/             tipos puros
  Service/                            lógica pura (só contratos): SemverComparator, UpdateChecker, CoreUpdater,
                                      Preflight, SchemaPlanner, SchemaVerifier, JsonUpdateManifestParser
  Infrastructure/                     adaptadores de rede/arquivo: CurlHttpClient, HostAllowlist, GitHubReleaseSource,
                                      ZipPackageExtractor, Sha256PackageVerifier, JsonUpdateStateStore, FileUpdateLock,
                                      FileMaintenanceMode, ZipDirectoryBackup, CoreProtectedPaths, StagedUpdateApplier,
                                      FilesystemInstalledState, UpdateConfig, Preflight/*Check
src/Core/Database/Update/             adaptadores de banco: PdoMigrationRegistry, PdoSchemaProbe, RunnerSchemaMigrator
src/Core/Database/migrations/         migrations do núcleo (escopo __core__)
src/SystemApps/Database/Backup/       adaptadores de dump/restore por driver
src/SystemApps/SystemUpdater/         SystemApp: Plugin.php, plugin.json, Controllers/, Widgets/, views/, manual/
tools/release/ReleaseBuilder.php      gerador do pacote e do manifest
tools/build-release.php               comando que chama o ReleaseBuilder
release/                              schema-expects.json, exclude.txt
config/update.json                    configuração (repositório, TTL, rollback automático)
docs/updater/                         CONTRATOS.md, PLANO_MODELO_A.md, STAGING.md
tests/{Unit,Architecture,Integration}/Update/   testes; tests/Support/ fakes e PackageBuilder
```

---

## 6. Passos

Cada passo termina com a suíte verde e um commit. Marcos de entrega: **M1** após o Passo 7 (aviso funcionando), **M2** após o Passo 15 (atualização completa), **M3** após o Passo 18 (validado na Hostinger).

### Passo 0 — Pré-requisitos e higiene
**Objetivo:** corrigir bases que o atualizador exige.
- `composer.json`: `"php": ">=8.1"`.
- `MigrationRunner::runForPlugin()`: **parar na primeira falha** (não executar as migrations seguintes). Manter o formato de retorno e acrescentar a chave `stopped_at` (nome da migration que falhou, ou `null`). Conferir quem usa o runner (`PluginMigrationScanner::healAll`, Auto-Heal do monitor) e ajustar a exibição para mostrar que o processo parou.
- Teste `tests/Unit/MigrationRunnerStopsOnFirstFailureTest.php` (SQLite em memória, pasta temporária com 3 migrations: ok, falha, ok → a terceira **não** roda e a segunda **não** é registrada).
- Teste `tests/Unit/VersionConsistencyTest.php`: o valor de `DS_VERSION` em `bootstrap.php` (ler por regex, sem `include`) é igual a `version` do `composer.json`.
- Relatar ao usuário a situação da tag `v2.3.0` (achado 1). **Não mover a tag.**

**Aceite:** suíte verde; comportamento do Auto-Heal documentado no comentário da classe.

### Passo 1 — Tipos (enums, objetos de valor, exceções)
**Objetivo:** tipos imutáveis e validados.
- Enums: `UpdateKind`, `UpdateStatus`, `CheckLevel`, `UpdateStep` (catálogo, seção 3).
- Objetos de valor `final readonly`: `UpdateTarget`, `ReleaseInfo`, `DownloadedPackage`, `VerifiedPackage`, `UpdateManifest`, `UpdateCheckResult`, `PreflightCheck`, `PreflightReport`, `SchemaPlan`, `SchemaResult`, `SchemaVerification`, `BackupScope`, `BackupRecord`, `DumpResult`, `ApplyResult`, `HealthReport`, `UpdateState`, `HttpResponse`, `InstalledPlugin`. Campos conforme catálogo + emendas 2 e 3.
- Validação no construtor: `sha256` com 64 hex, strings obrigatórias não vazias, listas tipadas. Valor inválido lança `InvalidArgumentException`.
- Exceções do catálogo (seção 4.10), todas herdando de `UpdateException`.
- `PreflightReport::canProceed()` é `false` se existir `CheckLevel::Block`. `UpdateState` tem `withStep()`, `withError()`, `withContext()` que **devolvem novo objeto**.

**Testes:** `tests/Unit/Update/ValueObjectsTest.php` (imutabilidade, validações, `canProceed`, `withStep`).
**Aceite:** nenhum tipo tem setter; nenhum tipo depende de infraestrutura.

### Passo 2 — Interfaces (contratos) e teste de arquitetura
**Objetivo:** fixar o contrato antes de qualquer implementação.
- Criar em `src/Core/Contracts/Update/` as interfaces do catálogo (seção 4), **exceto** as de plugin (emenda 7): `HttpClientInterface`, `ReleaseSourceInterface`, `VersionComparatorInterface`, `UpdateCheckerInterface`, `UpdateTargetResolverInterface` (só `core()`), `UpdateManifestParserInterface`, `UpdatePackageVerifierInterface`, `InstalledStateProviderInterface`, `PreflightCheckInterface`, `PreflightInterface`, `MigrationRegistryInterface`, `SchemaPlannerInterface`, `SchemaMigratorInterface`, `SchemaProbeInterface`, `SchemaVerifierInterface`, `BackupInterface`, `DatabaseBackupInterface`, `ProtectedPathsInterface`, `UpdateApplierInterface`, `UpdateStateStoreInterface`, `UpdateLockInterface`, `MaintenanceModeInterface`, `HealthCheckInterface`, `UpdaterInterface`, `CoreUpdaterInterface`.
- Todas com docblock em português descrevendo obrigações e exceções.
- `tests/Architecture/UpdateArchitectureTest.php` (cria agora, vale para os próximos passos):
  1. `Contracts/Update/` contém **só interfaces**;
  2. arquivos em `src/Core/Update/Service/` e `src/SystemApps/SystemUpdater/` não contêm: `PDO`, `sqlite`, `mysql`, `curl_`, `ZipArchive`, `file_put_contents`, `unlink(`, `rename(`, `exec(`, `$_SESSION`, `$_GET`, `$_POST`, `$_SERVER`, `new Curl`, `new Zip`, `new Pdo`;
  3. `Value/` não importa nada de `Infrastructure/` ou `Database/`.

**Aceite:** teste de arquitetura passa (inclusive com as pastas ainda vazias).

### Passo 3 — Comunicação (HTTP)
**Objetivo:** cliente HTTP seguro, atrás de `HttpClientInterface`.
- `HostAllowlist` (padrão: `api.github.com`, `github.com`, `objects.githubusercontent.com`, `release-assets.githubusercontent.com`; aceita curinga `*.githubusercontent.com`).
- `CurlHttpClient`: **somente HTTPS**; rejeita URL com *userinfo* (`https://api.github.com@evil.com`), porta não padrão e host fora da allowlist; `CURLOPT_FOLLOWLOCATION = false` e redirecionamento **manual, máximo 3 saltos, revalidando a allowlist a cada salto**; `User-Agent` fixo; timeouts; `download()` em streaming para arquivo, abortando ao passar de `$maxBytes` e apagando o parcial.
- Se `curl` não estiver disponível: lançar `TransportException` clara (o pré-check do Passo 9 bloqueia antes). **Não** implementar fallback por streams.
- Fakes em `tests/Support/`: `ArrayHttpClient` (respostas programadas por URL).

**Testes:** `HostAllowlistTest`; `CurlHttpClientGuardsTest` (só os bloqueios que **não usam rede**: `http://`, host não permitido, userinfo, redirecionamento para host não permitido simulado pela lógica de validação).
**Aceite:** nenhum teste faz acesso real à internet.

### Passo 4 — Versões (SemVer)
- `SemverComparator` implementa `VersionComparatorInterface`. SemVer estrito: `MAJOR.MINOR.PATCH` com `-prerelease` opcional; aceita prefixo `v` apenas em `normalize()` (tags). `2.4.0-beta.1 < 2.4.0`; `2.10.0 > 2.9.9`.
- **Testes** com tabela de casos (válidos, inválidos como `2.3`, `abc`, `2.3.0.1`; comparações; `isNewer`).

### Passo 5 — Estado e bloqueio
- `JsonUpdateStateStore` (`UpdateStateStoreInterface`) sobre `JsonStoreInterface`/`AtomicJsonStore`, arquivo `storage/state/update-core.json`. `load()` sem arquivo devolve estado `Idle`.
- `FileUpdateLock` (`UpdateLockInterface`): `flock` em `storage/state/update-core.lock`, com TTL gravado no arquivo (lock expirado pode ser retomado). `acquire()` não bloqueia: devolve `false` se ocupado.
- **Testes:** persistência e recarga; `Idle` por padrão; dois `acquire` seguidos → segundo `false`; lock expirado é reaproveitado; `release` libera.

### Passo 6 — Origem GitHub, configuração e descoberta
- `config/update.json` (versionado, valores padrão): `{"core_repository":"hadelrachid/domain-system","channel":"stable","check_ttl_hours":12,"auto_rollback":true}`. `UpdateConfig` lê via `JsonStoreInterface` e valida (repositório no formato `owner/repo`).
- `GitHubReleaseSource` (`ReleaseSourceInterface`): `GET https://api.github.com/repos/{repo}/releases/latest` (já exclui rascunhos e *pre-releases*); `If-None-Match` com o ETag conhecido (`304` ⇒ `null`); lê `tag_name`, `body` (changelog), `published_at`, assets `domain-system-X.Y.Z.zip` e `domain-system-X.Y.Z.zip.sha256`; baixa o `.sha256` (pequeno) e valida o formato. Falta de asset ⇒ `SourceUnavailableException`. `fetchPackage()` baixa o ZIP em `temp/updates/<versão>/` com limite de tamanho (150 MB).
- `UpdateTargetResolver` (só `core()`), com `installedVersion` vindo de `InstalledStateProviderInterface::coreVersion()` (implementação mínima agora: lê `DS_VERSION`).
- `UpdateChecker` (`UpdateCheckerInterface`): respeita o TTL salvo no estado; `$force` ignora o cache; **nunca lança** (falha vira `UpdateStatus::Unknown` com motivo); `Available` só se `isNewer`.
- **Fixtures:** `tests/Fixtures/github/releases_latest.json` (resposta real simplificada), `releases_latest_no_asset.json`.
- **Testes** com `ArrayHttpClient`: nova versão ⇒ `Available`; mesma ⇒ `UpToDate`; `304` ⇒ usa cache; resposta malformada ⇒ `Unknown`; asset ausente; `.sha256` inválido; TTL.

### Passo 7 — Interface somente leitura (marco M1: aviso de nova versão)
**Objetivo:** o aviso funcionando, sem nenhuma capacidade de alterar o sistema.
- Criar o SystemApp `src/SystemApps/SystemUpdater/` copiando a estrutura do `SystemMonitor` (`plugin.json` com `"core": true`, `"entrypoint": "Plugin"`, `"os_version": "2.3"`). **Descobrir como o `SystemMonitor` é carregado** (buscar `SystemMonitor` no código) e replicar o mesmo registro.
- `Plugin.php`: `osRegister`/`osBoot` com os hooks do achado 11; faz `bind` dos contratos aos adaptadores (verificar no `ContainerInterface` como registrar *singleton* ou fábrica com parâmetros; se não houver, criar uma fábrica pequena no próprio `Plugin.php`). Registrar a capability `system.update` em `activate()`.
- `Controllers/UpdateController.php`: `GET /admin/settings/updates` (página) e `POST /admin/settings/updates/core/check` (força verificação). Somente `admin`, CSRF, sem superglobais.
- `views/updates.php`: versão instalada, versão disponível, data, changelog **escapado**, botão "Verificar agora". Sem botão de atualizar ainda.
- Menu: item "Atualizações" via `admin.menu`; no `src/Plugins/settings/views/admin_settings.php`, acrescentar um cartão/link para a nova página (alteração mínima).
- `Widgets/UpdateWidgetProvider.php` (`DashboardWidgetProviderInterface`): widget "Versão do Sistema" com badge quando há atualização. Registrar como o `SystemWidgetProvider` é registrado hoje.
- Notificação: ao detectar versão nova, `NotificationManagerInterface::push()` **uma única vez por versão** (guardar `notified_version` no estado).
- **Testes:** controller com fakes (CSRF inválido ⇒ recusado; changelog com `<script>` sai escapado); widget com `UpdateChecker` falso (com e sem atualização); notificação não repete para a mesma versão.
- **Aceite do marco M1:** com um `ReleaseSource` falso apontando versão maior, a aba, o widget e a notificação aparecem. Nada altera arquivos ou banco.

### Passo 8 — Manifest e verificação do pacote
- `JsonUpdateManifestParser` (`UpdateManifestParserInterface`, protocolos suportados `[1]`). Formato do `manifest.json`:
  ```json
  {
    "protocol": 1, "minUpdaterProtocol": 1, "kind": "core", "slug": "core", "version": "2.4.0",
    "requires": {"php": "8.1", "extensions": ["pdo","zip","mbstring","openssl","json","curl"], "osVersion": "2.3"},
    "files": [{"path": "src/Core/Foo.php", "sha256": "…", "size": 123, "mode": "replace"}],
    "removed": ["src/Core/Velho.php"],
    "migrations": [{"scope": "__core__", "name": "2026_11_01_000001_x", "sha256": "…", "type": "additive"}],
    "expects": {"academy_modules": ["folder_slug"]}
  }
  ```
  Campo faltando, tipo errado ou protocolo desconhecido ⇒ `InvalidManifestException`.
- `ZipPackageExtractor` (extração em quarentena em `temp/`): rejeita caminhos absolutos, `..`, `\`, letra de unidade, byte nulo, **links simbólicos**, duplicatas que só diferem por maiúsculas/minúsculas; limites: 20.000 arquivos, 150 MB descompactados, razão de compressão máxima (proteção contra *zip bomb*).
- `Sha256PackageVerifier` (`UpdatePackageVerifierInterface`): confere o SHA-256 do ZIP contra `ReleaseInfo::sha256`; extrai; lê `manifest.json`; confere o hash e o tamanho de **cada** arquivo listado; **nenhum arquivo extra** fora do manifest (exceto o próprio `manifest.json`) e **nenhum arquivo do manifest ausente**; todo `path` precisa estar dentro de `ProtectedPathsInterface::allowedRoots()`.
- `tests/Support/PackageBuilder.php`: monta ZIP + manifest em diretório temporário para os testes.
- **Testes obrigatórios:** pacote válido; hash do ZIP errado; hash de um arquivo errado; arquivo extra; arquivo faltando; `../` e caminho absoluto; link simbólico; manifest com protocolo 2; manifest sem campo obrigatório; arquivo fora das raízes permitidas; *zip bomb* (limite estourado). Limpeza da quarentena em qualquer falha.

### Passo 9 — Pré-checagem
- `Preflight` (composite, `PreflightInterface`) agrega todas as `PreflightCheckInterface` injetadas; um check que lança exceção vira `Block`.
- Checks em `Infrastructure/Preflight/`: `PhpVersionCheck`, `ExtensionsCheck` (inclui `curl` e `zip`), `DiskSpaceCheck` (≥ 3× o tamanho do pacote), `WritablePathsCheck` (raízes permitidas, `storage/`, `temp/`, `config/`), `ProtocolCheck` (`minUpdaterProtocol` ≤ suportado), `DowngradeCheck` (bloqueia versão ≤ instalada), `PluginCompatibilityCheck` (emenda 8: **aviso** para plugins não empacotados com major diferente), `SchemaPlanCheck` (usa o `SchemaPlanner` do Passo 10; plano que lança exceção ⇒ `Block`).
- `FilesystemInstalledState` (`InstalledStateProviderInterface`): `DS_VERSION` e `src/Plugins/*/plugin.json` (`version`, `os_version`, `core`).
- **Testes:** cada check com cenário passa/falha/aviso; `Preflight` agrega; exceção em check ⇒ `Block`.

### Passo 10 — Schema do banco (verificação e execução por contrato)
- `src/Core/Database/Update/PdoMigrationRegistry` (`MigrationRegistryInterface`): **somente leitura**; se a tabela `kernel_migrations` não existir, devolve `[]` **sem criá-la**.
- `src/Core/Update/Service/SchemaPlanner` (`SchemaPlannerInterface`): compara `manifest.migrations` com `MigrationRegistry::executed(scope)`; confere que cada arquivo existe na quarentena, tem o hash do manifest e retorna `['up' => callable]` **sem executá-lo** (inspeção estática: ler o arquivo e conferir o hash; não dar `require`); detecta `blocked` (executadas que não existem no pacote); `destructive` ⇒ `requiresDatabaseBackup = true`.
- `src/Core/Database/Update/RunnerSchemaMigrator` (`SchemaMigratorInterface`): usa o `MigrationRunner` corrigido (Passo 0) por escopo, na ordem do plano; **para na primeira falha** e devolve `SchemaResult` com `failed` e `error`. Escopo `__core__` ⇒ `src/Core/Database/migrations/`; escopo de plugin ⇒ `src/Plugins/<slug>/migrations/`.
- `src/Core/Database/Update/PdoSchemaProbe` (`SchemaProbeInterface`): `hasTable`/`hasColumn` com SQL ANSI (`SELECT 1 FROM t LIMIT 1`, `SELECT col FROM t LIMIT 1`), tratando **tanto retorno `false` quanto exceção**, exatamente como a migration do Academy (`2026_10_15_000002_create_academy_structure.php`).
- `src/Core/Update/Service/SchemaVerifier` (`SchemaVerifierInterface`): confere `expects` (tabelas/colunas) via `SchemaProbe` e se todas as migrations planejadas constam como executadas.
- Criar a pasta `src/Core/Database/migrations/` com um `.gitkeep`.
- **Testes** (SQLite em memória): plano com pendentes; plano com hash divergente ⇒ `SchemaPlanException`; migrator executa tudo; migrator **para na segunda de três** e não registra a falha; verificador detecta coluna ausente mesmo com migration "registrada"; `destructive` ⇒ exige backup de banco.

### Passo 11 — Backup (código e banco)
- `ZipDirectoryBackup` (`BackupInterface`): para `BackupScope(core)` gera `storage/backups/core/<id>/code.zip` + `meta.json`, com `.htaccess` de negação. **Inclui:** `src/`, `public/` (menos `uploads`), `themes/`, `vendor/`, `bin/`, `tools/`, arquivos da raiz e `config/*.json`. **Exclui:** `storage/`, `temp/`, `.git/`, `.env`, `database.sqlite`, `uploads/`, `public/uploads/`. `restore()` extrai por cima com as mesmas defesas anti Zip-Slip. `prune($scope, 2)` mantém só os 2 mais recentes.
- Adaptadores de banco em `src/SystemApps/Database/Backup/` (aqui — e só aqui — se conhece o driver): `SqliteDatabaseBackup` (`PRAGMA wal_checkpoint(TRUNCATE)` e cópia do arquivo) e `MysqlDatabaseBackup` (tabelas via `SHOW TABLES`, `SHOW CREATE TABLE`, linhas em lotes de 500, gravação em streaming; `isSupported()` falso acima de 200 MB estimados). `restore()` executa o dump com verificação de chave estrangeira desligada durante a carga. Fábrica que escolhe o adaptador pelo driver da conexão ativa e é ligada ao contrato no `Plugin.php` do SystemApp `Database` (ou onde o `DatabaseConnectionInterface` é ligado hoje).
- **Testes:** backup/restauração de código em pasta temporária (árvore idêntica após restaurar; exclusões respeitadas; `prune`); backup/restauração SQLite (ida e volta); MySQL com `#[Group('mysql')]` **ignorado** a menos que exista a variável de ambiente `DS_TEST_MYSQL_DSN` (validado manualmente no Passo 18).

### Passo 12 — Manutenção, proteção e aplicação
- `FileMaintenanceMode` (`MaintenanceModeInterface`): cria/remove o mesmo `<raiz>/.maintenance` usado hoje (conteúdo `maintenance`). `enable()` registra no estado se a manutenção **já estava ligada** (`maintenanceWasOn`) para não desligar no final uma manutenção manual.
- `SettingsController::toggleMaintenance`: passar a usar a interface (mantendo o comportamento e o redirecionamento atuais; **conferir testes existentes**).
- Teste que prova: caminhos `/admin/settings/updates/core/advance` e `/admin/settings/updates/core/health` são isentos em `MaintenancePolicy::isExemptPath`, e um visitante recebe 503 em `/` com `.maintenance` ligado.
- `CoreProtectedPaths` (`ProtectedPathsInterface`): **protegidos** — `.env`, `.maintenance`, `config/` inteiro (novos padrões entram só via `mode: default_if_missing`), `database.sqlite`, `storage/`, `temp/`, `uploads/`, `public/uploads/`, `token/`, `scratch/`, `auditoria/`, temas privados (`src/Plugins/flextheme/themes/rachidd`). **Raízes permitidas** — `src/`, `public/`, `themes/`, `vendor/`, `bin/`, `tools/`, `release/` e os arquivos da raiz `bootstrap.php`, `os-cli.php`, `composer.json`, `.htaccess`, `LICENSE`. A lista mora no sistema instalado, **nunca no pacote**.
- `StagedUpdateApplier` (`UpdateApplierInterface`): 1) ordena `vendor/` → `src/` → `themes/` → demais → **`public/index.php` e `bootstrap.php` por último**; 2) ignora arquivo cujo hash já é igual ao do manifest; 3) grava em temporário (`.<nome>.dsnew`) e troca (no Windows, apagar o destino antes do `rename`); 4) preserva permissões (`0644`/`0755`); 5) `removed[]` só dentro das raízes permitidas e nunca em caminho protegido; 6) revalida cada caminho (defesa em profundidade) e se recusar ⇒ `ApplyException`; 7) ao final, `opcache_reset()` se existir; 8) `revert()` restaura a partir do `BackupRecord` via `BackupInterface::restore`.
- **Importante (o atualizador se sobrescreve):** o orquestrador deve **instanciar todos os colaboradores antes** da etapa `Applied`, e essa etapa responde com JSON mínimo, sem carregar classes novas do updater na mesma requisição.
- **Testes:** árvore temporária; protegidos intocados; `default_if_missing`; arquivo idêntico pulado; `removed` fora das raízes recusado; falha no meio (ex.: pai do destino é um arquivo) ⇒ `ApplyException` e `revert` deixa a árvore **idêntica** (comparar hashes).

### Passo 13 — Saúde (duas fases)
- `CoreHealthCheck` (`HealthCheckInterface`): roda em requisição nova. Verifica: `DS_VERSION` igual à versão alvo (prova que o código novo foi carregado); contratos-chave resolvem no container (`RouterInterface`, `SessionManagerInterface`, `DatabaseConnectionInterface`, `SchemaDoctorInterface`); conexão com o banco responde; `SchemaVerifier` ok; `config/plugins.json` legível.
- Endpoint `GET /admin/settings/updates/core/health?update_id=…` (admin): executa o check e **grava o `HealthReport` no estado**. `advance()` na etapa de saúde só lê esse resultado; se ausente, devolve o estado com `awaiting = health` para a interface chamar o endpoint.
- **Testes:** check com container falso (ok / contrato ausente / versão divergente); endpoint grava o relatório; `advance` sem relatório ⇒ `awaiting`.

### Passo 14 — Orquestrador `CoreUpdater` (máquina de estados)
- `src/Core/Update/Service/CoreUpdater` (`CoreUpdaterInterface`): `check`, `begin`, `advance`, `rollback`, `status`. Cada `advance()` executa **uma** etapa e persiste o estado antes e depois. Sequência: `Checked → Downloaded → Verified → Preflighted → BackedUp → MaintenanceOn → Applied → Migrated → SchemaVerified → HealthChecked → Done`.
- `begin()` adquire o lock; `Done`/`RolledBack`/`Failed` liberam o lock e desligam a manutenção **se foi o atualizador que ligou**.
- Plano de banco com `requiresDatabaseBackup` ou `DatabaseBackup::isSupported() === false` ⇒ exige a opção explícita `confirmNoDbBackup` em `begin()`; sem ela, `Block`.
- **Falha após `BackedUp`:** se `auto_rollback` (config) ⇒ reverter automaticamente: restaurar código → restaurar dump (se migrations rodaram) → desligar manutenção (se aplicável) → `RolledBack` + notificação de erro. Se o rollback falhar (`RollbackFailedException`): estado `Failed`, **manter a manutenção ligada** (protege os visitantes) e notificação de erro com instruções para a CLI.
- Etapa interrompida por queda: ao retomar, etapas **seguras** (`Downloaded`, `Verified`, `Preflighted`) repetem; `Applied` interrompida é tratada como falha ⇒ rollback.
- Eventos via `EventDispatcherInterface`: `update.available`, `update.started`, `update.step`, `update.completed`, `update.failed`, `update.rolled_back`.
- No `Plugin.php` do SystemUpdater, ligar todos os contratos aos adaptadores.
- **Testes** (tudo com fakes): caminho feliz com a ordem exata de chamadas; falha injetada **em cada etapa** (parametrizado) ⇒ estado final e chamadas de rollback esperadas; `auto_rollback = false`; `RollbackFailedException` mantém manutenção; lock concorrente; retomada após queda; downgrade bloqueado; backup de banco ausente exige confirmação; manutenção manual preexistente não é desligada.

### Passo 15 — Interface de atualização e CLI (marco M2)
- Rotas (admin, capability `system.update`, CSRF): `POST /admin/settings/updates/core/begin`, `.../advance`, `.../rollback`, mais as de `check` e `health` já existentes. Respostas JSON com o `UpdateState`.
- `views/updates.php`: botão "Atualizar para vX.Y.Z" com confirmação (`OS.confirm`, já usado em `admin_monitor.php`); mostra o que será feito, **avisos do pré-check** e a **confirmação de "sem backup de banco"** quando necessária; barra de progresso com a lista de etapas e um laço `fetch` que chama `advance` até `Done`/`Failed`/`awaiting health`; lista de backups com "Restaurar" e "Remover antigos". Tudo em português.
- CLI em `os-cli.php`: `system:check`, `system:status`, `system:update [--yes] [--confirm-no-db-backup]`, `system:rollback [id]`, `system:health`. A CLI executa o laço `advance` e imprime cada etapa. (A saúde pode rodar no mesmo processo, com aviso, se `proc_open` estiver desabilitado.)
- **Testes:** controller com `CoreUpdater` falso (CSRF; papel não-admin recusado; resposta JSON); CLI testada pela camada de comando sem I/O real.
- **Aceite do marco M2:** com fakes de ponta a ponta, atualizar e reverter funcionam pela interface e pela CLI.

### Passo 16 — Pipeline de Release (GitHub Actions)
- `tools/release/ReleaseBuilder.php` e `tools/build-release.php`: lê a versão de `DS_VERSION`; exige `vendor/autoload.php` presente; monta a lista de arquivos = `git ls-files` (menos `release/exclude.txt`) + todo o `vendor/`; calcula hash e tamanho; lê `migrations/*.php` de `src/Core/Database/migrations/` (escopo `__core__`) e de `src/Plugins/*/migrations/` (escopo = slug); lê `release/schema-expects.json`; gera `dist/domain-system-X.Y.Z.zip` (arquivos na raiz do ZIP + `manifest.json`) e `dist/domain-system-X.Y.Z.zip.sha256`.
- `release/exclude.txt`: `.git*`, `tests/`, `docs/`, `wiki/`, `auditoria/`, `scratch/`, `token/`, `storage/`, `temp/`, `*.phar`, `*.zip`, `.env`, `database.sqlite`, `.kilo/`, `workstation/`, `.github/`, `phpunit.xml`, arquivos `PLANO_*`.
- `release/schema-expects.json`: tabelas/colunas obrigatórias (começar com `academy_modules: [folder_slug]` e `academy_lessons: [mobile_profile]`).
- Evoluir `.github/workflows/release.yml`: (1) instalar dependências **com dev** e rodar `composer test`; (2) conferir que a tag (`v2.4.0`) é igual a `DS_VERSION` **e** a `composer.json`; (3) `composer install --no-dev --optimize-autoloader`; (4) rodar `php tools/build-release.php`; (5) publicar **os assets atuais** (`domain-system-core.zip`, `radio_online.zip`, `clock_widget.zip`) **mais** `domain-system-X.Y.Z.zip` e `.sha256`.
- **Teste de ida e volta** `tests/Integration/ReleaseBuilderRoundTripTest.php`: gera um pacote a partir de uma árvore de fixture e faz o `JsonUpdateManifestParser` + `Sha256PackageVerifier` aceitá-lo; adulterar 1 byte ⇒ rejeição.
- **Aceite:** `php tools/build-release.php` roda localmente; o pacote gerado passa no verificador.

### Passo 17 — Documentação e manual
- `src/SystemApps/SystemUpdater/manual/module.php` (`ManualModuleInterface`): passo a passo para o administrador e checklist de homologação.
- `docs/updater/STAGING.md` (criado vazio com o roteiro do Passo 18).
- `DEVELOPER_GUIDE.md` e `AI_CONTRACT_GUIDE.md`: seção **"Releases e migrations"** com as regras: migrations **aditivas** (nunca remover/renomear dentro da mesma versão maior), toda migration nova exige atualizar `release/schema-expects.json`, a tag precisa ser igual a `DS_VERSION`, `AtomicJsonStore` para JSON de estado.
- `wiki/` (página `Atualizacoes.md`), `docs/CHANGELOG.md` e `docs/CHANGELOG-en.md` (em **Unreleased**), `ROADMAP.md` (marcar itens concluídos), `README.md`/`README-en.md` (uma linha na lista de recursos). **Sem alterar a versão.**

### Passo 18 — Validação em staging (Hostinger) — marco M3
- Criar um repositório **sandbox** público (ex.: `hadelrachid/domain-system-sandbox`) e apontar `config/update.json` → `core_repository` para ele **no ambiente de teste**.
- Publicar no sandbox uma Release com versão maior e um pacote gerado por `tools/build-release.php`, incluindo uma migration aditiva (nova coluna).
- Registrar os resultados em `docs/updater/STAGING.md`:
  1. verificação e aviso aparecem (aba, widget, notificação);
  2. atualização completa: manutenção ligada (visitante vê 503, admin continua), coluna criada, schema verificado, saúde ok, manutenção desligada;
  3. **hash adulterado** ⇒ recusado sem alterar nada;
  4. **migration quebrada** (Release do sandbox com erro proposital) ⇒ rollback automático: código e banco voltam, site no ar;
  5. **dois navegadores** atualizando ao mesmo tempo ⇒ o segundo é recusado pelo lock;
  6. cada etapa responde em tempo seguro (< ~25 s) na Hostinger; anotar a mais lenta;
  7. `system:update` e `system:rollback` via SSH;
  8. backup de banco MySQL: dump e restauração com sucesso (rodar também o teste `mysql` com `DS_TEST_MYSQL_DSN`).

---

## 7. Definição de pronto (global)
- [ ] Suíte completa verde; contagem de testes acima do baseline de 183.
- [ ] Teste de arquitetura impede `PDO`/`sqlite`/`mysql`/`curl_`/`ZipArchive`/superglobais fora dos adaptadores.
- [ ] Nenhum teste usa a internet nem altera `.env`, `database.sqlite` ou `config/*.json` reais.
- [ ] Marcos M1, M2 e M3 atingidos e registrados.
- [ ] Documentação e manual atualizados; versão **inalterada** até decisão do usuário.
- [ ] Nada enviado ao GitHub sem autorização.

## 8. Perguntas que o implementador deve fazer ao usuário (não decidir sozinho)
1. **Tag `v2.3.0` defasada** (aponta 5 commits atrás): mover a tag (reescreve histórico público), ou publicar a primeira Release com o atualizador em uma versão nova? Qual número?
2. **Repositório público** (necessário para o GitHub sem token)? Confirmar.
3. **Rollback automático ligado por padrão** (`auto_rollback: true`)? Confirmar.
4. **Backup de banco MySQL acima de 200 MB:** bloquear ou permitir com confirmação explícita? (Plano: permitir só com confirmação.)
5. O repositório **sandbox** do Passo 18 pode ser criado por você?
6. Qualquer comportamento do `SettingsController::toggleMaintenance` ou do Auto-Heal que um teste existente proteja e que o passo exija mudar.

## 9. Riscos específicos do Modelo A
| Risco | Mitigação |
|---|---|
| Temas empacotados serão sobrescritos (customização local se perde) | Avisar no pré-check e na tela; orientar a copiar o tema; temas privados já são protegidos |
| Plugin de terceiros incompatível com o novo núcleo | `PluginCompatibilityCheck` avisa antes de aplicar; o Disjuntor isola em caso de falha |
| Pacote de ~30 MB estoura tempo/memória na hospedagem | Etapas curtas via AJAX; download em streaming; CLI como alternativa |
| MySQL não desfaz DDL | Migrations aditivas, backup de banco antes, `SchemaVerifier` depois |
| Updater sobrescrito durante a execução | Colaboradores instanciados antes de `Applied`; resposta mínima; entradas (`public/index.php`, `bootstrap.php`) por último |
| `release.yml` publicar pacote quebrado | Testes + checagem tag = `DS_VERSION` antes de publicar; teste de ida e volta do pacote |
