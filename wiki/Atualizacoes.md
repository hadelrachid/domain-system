# 🔄 Sistema de Atualizações com 1 Clique

O **Domain System** vem de fábrica com um sistema de atualizações seguro e automático. Você não precisa conectar via FTP ou mexer em código no servidor para manter o sistema e os plugins em dia.

---

## 🏛️ Duas Camadas de Atualização

### 1. Atualização do Núcleo do Sistema (Core)
* Onde fica: Acesse **Configurações → Atualizações** (`/admin/settings/updates`) ou clique no número da versão no rodapé do painel.
* Como funciona: O sistema consulta as versões oficiais no GitHub Releases.
* Segurança antes de tudo: Antes de tocar em qualquer arquivo, o sistema cria um **backup completo** dos seus arquivos e do banco de dados na pasta `storage/backups/`. Se faltar luz ou a conexão cair no meio do caminho, o sistema desfaz as alterações automaticamente (rollback).
* Proteção dos seus dados: Arquivos vitais como suas configurações (`.env`, `config/`), banco de dados (`database.sqlite`), fotos e mídias de clientes **nunca são substituídos**.

### 2. Atualização de Plugins com 1 Clique (Estilo WordPress)
* Onde fica: Acesse **Plugins** (`/admin/plugins`).
* Alertas automáticos: O sininho no topo do painel (`#os-notif-bell`) acende e avisa sempre que houver novidades no repositório oficial de extensões.
* Atualização seletiva: Cada plugin instalado exibe um box informando quando há uma versão superior disponível.
* Instalação segura: Ao clicar em **"Atualizar Agora"**, o sistema faz o download do pacote oficial (.zip) diretamente do GitHub Releases, confere a impressão digital SHA-256 para garantir que o arquivo não foi corrompido, e substitui a pasta do plugin sem afetar o resto do site.

---

## 🔐 Como Garantimos que Nada Vai Quebrar

1. **Conexões Apenas com Hosts Seguros:** O sistema só baixa arquivos de servidores oficiais da infraestrutura do GitHub com certificado SSL/TLS ativo.
2. **Dupla Conferência Criptográfica (SHA-256):** O pacote baixado só é descompactado se o código hash bater perfeitamente com o registro oficial publicado no manifesto `marketplace.json`.
3. **Disjuntor de Segurança (Circuit Breaker):** Se um plugin atualizado tiver qualquer erro inesperado em código de terceiros, o sistema desarma apenas aquele plugin e mantém o painel e as outras páginas funcionando normalmente.

---

## 💻 Atualizações pelo Terminal (Para Desenvolvedores e Servidores)

Se você preferir gerenciar atualizações via terminal no seu servidor, use o comando:

```bash
# Conferir se há nova versão disponível para o sistema
php os-cli.php system:check

# Ver o status atual
php os-cli.php system:status

# Aplicar a atualização com confirmação
php os-cli.php system:update --yes

# Desfazer a última atualização e restaurar o backup
php os-cli.php system:rollback
```
