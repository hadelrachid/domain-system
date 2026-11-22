# 📐 Política de Versionamento SemVer & Ciclo de Entregas

O **Domain-System OS** segue rigorosamente a especificação internacional **Semantic Versioning 2.0.0 (SemVer)**:

$$\text{v} \, \mathbf{X} \, . \, \mathbf{Y} \, . \, \mathbf{Z}$$

Exemplo: **`v2.7.1`**

---

## 🧭 Os Três Níveis de Evolução

| Dígito | Classificação | O que representa | Quando é incrementado | Impacto |
| :---: | :---: | :--- | :--- | :---: |
| **Z** | **PATCH** | **Ciclo de Refinamento** | Correções de bugs, polimento de UI/UX, layouts responsivos, minificação de CSS/JS e ajustes de estabilidade. | **Risco Zero** (Zero quebras) |
| **Y** | **MINOR** | **Novo Marco Funcional** | Lançamento de novos módulos oficiais (`DomainDrop`, `DomainSEO`), novas rotas, tabelas de banco e novos contratos de arquitetura. | **Retrocompatível** |
| **X** | **MAJOR** | **Salto de Geração** | Reformulação radical do Kernel (ex: Domain-System OS 2.0), quebra deliberada de contratos ou transição de paradigma de execução. | **Migração Assistida** |

---

## 🔒 Protocolo de Lançamento de Release

1. **Consistência de Versão Obrigatória:**
   - Tag Git: `vX.Y.Z`
   - `bootstrap.php`: `define('DS_VERSION', 'X.Y.Z');`
   - `composer.json`: `"version": "X.Y.Z"`
   - Manifestos: `src/Plugins/*/plugin.json` e `src/SystemApps/*/plugin.json` com `"version": "X.Y.Z"`
2. **Validação Automatizada:**
   - Teste de consistência: `php phpunit.phar --filter VersionConsistencyTest`
   - Suíte geral de testes: 100% de aprovação (zero falhas).
3. **Build e Distribuição Automática:**
   - O envio da tag `git push origin vX.Y.Z` dispara o GitHub Actions (`.github/workflows/release.yml`), gerando os pacotes compactados assinados com SHA-256 e publicando a Release oficial.
4. **Atualização na Hostinger:**
   - O painel `/admin/settings/updates` identifica a nova release e aplica a atualização com backup e rollback automático.

Para a documentação completa e detalhada, consulte: [docs/politica-versionamento-semver.md](../docs/politica-versionamento-semver.md).
