# Roteiro de Validação em Staging (Hostinger) — Passo 18 (Marco M3)

Este documento registra o roteiro e os resultados de homologação em ambiente real de hospedagem compartilhada (**Hostinger**, Linux + Apache/LiteSpeed + MySQL) do atualizador automático do **Domain System (Modelo A)**.

---

## Pré-requisitos do Ambiente de Teste
1. Repositório público **sandbox** no GitHub (ex.: `hadelrachid/domain-system-sandbox`).
2. Na instância de staging da Hostinger, apontar `config/update.json` → `"core_repository": "hadelrachid/domain-system-sandbox"`.
3. Publicar no repositório sandbox uma Release com versão superior (ex.: `v2.4.0`) contendo os artefatos gerados por `php tools/build-release.php` (`domain-system-2.4.0.zip` e `domain-system-2.4.0.zip.sha256`), incluindo uma migration aditiva de teste.

---

## Checklist de Homologação (Marco M3)

| # | Cenário de Teste | Resultado Esperado | Status | Tempo / Observações |
|---|---|---|---|---|
| 1 | **Detecção e Aviso (M1)** | Aba `Configurações → Atualizações`, widget `Versão do Sistema` no `/admin` e notificação única no sino exibem a nova versão disponível. | `[ ] Pendente` | |
| 2 | **Atualização Completa de Ponta a Ponta** | Manutenção ativada (visitante recebe HTTP 503; admin continua navegando), arquivos aplicados preservando `.env` e `config/`, migration executada, `SchemaVerifier` + `CoreHealthCheck` aprovados, manutenção desligada automaticamente ao atingir `Done`. | `[ ] Pendente` | |
| 3 | **Pacote com Hash SHA-256 Adulterado** | `Sha256PackageVerifier` recusa o pacote na etapa `Verified` antes de qualquer alteração em disco ou banco; quarentena é limpa. | `[ ] Pendente` | |
| 4 | **Migration com Falha Proposital (Auto-Rollback)** | Release de teste com SQL inválido dispara falha em `Migrated`; `CoreUpdater` executa rollback automático de código e banco, desliga manutenção e encerra em `RolledBack` com site íntegro no ar. | `[ ] Pendente` | |
| 5 | **Concorrência (Dois Navegadores Simultâneos)** | O segundo navegador que acionar `Atualizar` recebe erro de lock ativo (`LockException`) sem corromper a execução do primeiro. | `[ ] Pendente` | |
| 6 | **Tempo por Etapa HTTP na Hostinger** | Todas as requisições AJAX (`begin`, `advance`, `health`) respondem em `< 25s` sem timeout de FPM/LiteSpeed. | `[ ] Pendente` | Etapa mais lenta: `—` |
| 7 | **Operação via SSH (`os-cli.php`)** | Comandos `php os-cli.php system:check`, `system:update --yes`, `system:status`, `system:health` e `system:rollback` funcionam via terminal SSH. | `[ ] Pendente` | |
| 8 | **Backup e Restauração MySQL Real** | Dump em lotes (`MysqlDatabaseBackup`) e restauração funcionam na base MySQL da Hostinger; suíte `phpunit --group mysql` passa com `DS_TEST_MYSQL_DSN`. | `[ ] Pendente` | |
