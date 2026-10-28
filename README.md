# Domain System (v2.5)

Plataforma modular em PHP 8.2+ para gerenciamento de páginas, serviços e temas, projetada com base em contratos de interfaces e injeção de dependências.

O objetivo do projeto é oferecer uma alternativa enxuta e desacoplada aos CMSs tradicionais, mantendo consumo reduzido de memória (~5 MB de RAM no motor Zend em requisições típicas) e um espaço em disco de aproximadamente 30 MB descompactado.

---

[🇺🇸 English version](README-en.md) | [📖 Documentação](https://hadelrachid.github.io/domain-system/) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Guia do Desenvolvedor](DEVELOPER_GUIDE.md)

---

## 🎯 Proposta e Arquitetura

O sistema adota princípios de **Clean Architecture** e **Inversão de Dependência (DIP)**:

1. **Núcleo Agnóstico (`src/Core`)**:
   - Fornece apenas serviços de infraestrutura básica: container de injeção de dependências, despachante de eventos (hooks), roteador agnóstico de ambiente (raiz ou subpasta), armazenamento atômico de arquivos de configuração e verificadores de esquema de banco de dados.
   - O núcleo não possui acoplamento com modelos de negócio ou regras específicas de banco de dados.

2. **Separação em Camadas (`SystemApps` e `Plugins`)**:
   - **`src/SystemApps` (Módulos Base)**: Componentes estruturais essenciais (banco de dados, autenticação/RBAC, painel administrativo, monitoramento e atualizador). Possuem prioridade no processo de inicialização e não são desativados automaticamente em caso de exceções transitórias.
   - **`src/Plugins` (Extensões de Domínio)**: Módulos de aplicação (como construtor de temas, LMS, páginas e SEO). Conectam-se ao núcleo por meio do mediador `OsRuntime`, que verifica os contratos e serviços declarados durante o registro de cada módulo.

3. **Tolerância a Falhas e Circuit Breaker**:
   - Tratamento de exceções no ciclo de vida dos módulos: se uma extensão de domínio lançar um erro fatal durante a inicialização ou renderização de rotas, o sistema isola o módulo com falha e preserva o funcionamento do restante da aplicação.

4. **Flex Builder (FSE - Full Site Editing)**:
   - Motor visual para montagem de cabeçalhos, rodapés e seções centrais em CSS Grid de 12 colunas.
   - Suporte a visibilidade responsiva independente por elemento em 4 resoluções (Desktop, Laptop 1024px, Tablet 768px e Mobile 375px) com reajuste automático das colunas (*Auto-Reflow*).
   - Validação estrutural de payloads antes da persistência e sanitização estrita de HTML e vetores SVG contra XSS.

5. **Atualizador Integrado (`SystemUpdater`)**:
   - Verificação e aplicação de versões publicadas no GitHub Releases via empacotamento assinado por SHA-256.
   - Processo de substituição atômica de arquivos com pré-checagem de ambiente (*Preflight*) e preservação de arquivos de configuração (`.env`, `config/`, banco de dados e mídias).

---

## 🔍 Auditoria, Transparência e IA: `project.txt`

Para facilitar a colaboração, análise estática e revisão de código por desenvolvedores humanos ou ferramentas de inteligência artificial (LLMs), este repositório disponibiliza na raiz o arquivo:

📄 **[`project.txt`](project.txt)**

Esse arquivo reúne a estrutura completa do sistema, namespaces, interfaces e código-fonte em um único documento padronizado. 

> **Dica para revisão e auditoria:** Caso queira auditar o código ou entender como os contratos se comunicam, você pode carregar o `project.txt` diretamente no seu modelo de IA de preferência (Claude, ChatGPT, Gemini, etc.) ou em ferramentas de análise estática. Isso permite que a IA tenha contexto global do projeto, sem depender de análises parciais ou suposições.

Críticas técnicas e sugestões sobre a arquitetura e segurança são bem-vindas nas *Issues* e *Discussions*.

---

## 📊 Métricas e Testes

- **Linguagem:** PHP 8.2+ (com tipagem estrita `declare(strict_types=1)`).
- **Testes Automatizados:** 438 testes unitários e de integração no PHPUnit (mais de 2.350 asserções cobrindo contratos, sanitização, migrações e atualizador).
- **Consumo de Memória (Telemetria):** ~4.94 MB de uso ativo no motor Zend em requisições de página (~12 MB de pico de alocação do sistema operacional).
- **Dependências Externas em Produção:** Zero dependências de terceiros no núcleo (apenas extensões padrão do PHP: PDO, Zip, Mbstring, OpenSSL e cURL).

---

## 🚀 Instalação e Execução

### Requisitos
- PHP 8.2 ou superior com as extensões `pdo`, `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `zip`, `curl` e `openssl`.
- Servidor Web (Apache, Nginx ou o servidor embutido do PHP).

### Execução Local Rápida
```bash
# 1. Clonar o repositório
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Iniciar servidor embutido do PHP para testes
php -S localhost:8000 -t public
```

Acesse `http://localhost:8000` no navegador para executar o assistente de configuração inicial.

---

## 🧪 Executando os Testes

Para executar a suíte completa de testes automatizados com o PHPUnit:

```bash
php phpunit.phar
```

---

## 📄 Licença

Este projeto é distribuído sob a licença [MIT](LICENSE).
