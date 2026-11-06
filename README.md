# Domain System (v2.6.0)

O **Domain System** é um CMS e plataforma de cursos online (LMS) feito em **PHP 8.2+**. Ele foi criado com um objetivo simples: ser muito leve, rápido e fácil de manter, sem o peso, a lentidão e a montanha de plugins de CMSs como o WordPress.

O sistema consome cerca de **5 MB de RAM** por requisição e ocupa apenas **~30 MB de espaço em disco**, rodando liso em qualquer VPS barata ou até em hospedagem compartilhada simples com PHP e MySQL/SQLite.

---

[🇺🇸 English version](README-en.md) | [📖 Documentação](https://hadelrachid.github.io/domain-system/) | [🌱 Sobre o Projeto](ABOUT.md) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Guia do Desenvolvedor](DEVELOPER_GUIDE.md)

---

## 💡 O que o Domain System traz pronto?

O projeto já vem pronto para colocar sites, lojas e escolas de cursos no ar:

### 🎓 1. Plataforma de Cursos Completa (Academy)
- **Área do Aluno Segura:** Tela de login e cadastro com recuperação de acesso e painel intuitivo.
- **Estrutura de Aulas:** Cursos organizados por módulos e aulas sequenciais com controle de progresso (barra de porcentagem e marcação de aula concluída).
- **Formatos de Aulas Flexíveis:** Vídeos (YouTube, Vimeo ou links diretos), textos formatados em blocos e **leitor de apostilas/PDF embutido** (com modos de visualização em canvas leve ou embutido).
- **Certificados Automáticos:** Emissão de certificado de conclusão quando o aluno atinge 100% das aulas.

### 💳 2. Pagamentos e Cobrança Direta (DomainCommerce)
- **Mercado Pago Oficial (Brasil):**
  - **Pix Instantâneo na Tela:** Gera o QR Code dinâmico e o código Copia-e-Cola direto na página, com checagem automática de recebimento a cada 3 segundos e liberação imediata da matrícula.
  - **Cartão de Crédito:** Parcelamento em até 12x (com opção de usar 2 cartões de crédito na mesma compra).
  - **Boleto Bancário** e Checkout Pro oficial.
- **PayPal Oficial (Vendas Internacionais):** Cobrança em moeda estrangeira ou saldo PayPal com proteção ao comprador.
- **Webhooks de Afiliados:** Recebimento automático de notificações de venda da Hotmart, Eduzz, Monetizze e ClickBank, criando o usuário e matriculando o aluno na hora.

### 🎨 3. Construtor Visual de Layouts (Flex Builder)
- Criação e personalização de cabeçalhos, rodapés e seções centrais em CSS Grid de 12 colunas.
- **Menus Responsivos:** Criação visual de menus com submenus (arraste horizontal e indentação fácil).
- **Gaveta Mobile:** Menu móvel personalizável com estilos variados de botões hambúrguer e visual moderno.
- **Visibilidade Responsiva:** Defina quais blocos aparecem ou somem em Desktop, Laptop, Tablet ou Celular.

### 🔄 4. Atualizador Integrado (1 Clique)
- Atualização direta pelo painel administrativo conectando aos pacotes de versão do GitHub Releases.
- Verificação de integridade com SHA-256 e backup automático do banco e arquivos antes de aplicar qualquer atualização.

### 🛡️ 5. Resiliência e o Circuit Breaker (Por que ele existe?)
- **Sobre as críticas ao Circuit Breaker em PHP:** Alguns desenvolvedores com razão perguntam: *"Por que usar um Circuit Breaker em plugins PHP monolíticos se a boa prática comum é deixar a exceção estourar?"*. Essa é uma dúvida muito justa.
- **O motivo prático:** Em um ambiente de produção real (como uma escola online com alunos assistindo aulas ou clientes comprando no checkout), se um plugin secundário — como um contador de visitas ou um widget acessório — quebrar por qualquer motivo inesperado, **o site inteiro não pode sair do ar com um erro fatal 500**.
- **O equilíbrio que adotamos:**
  - O Circuit Breaker **não esconde erros**: ele apenas desarma temporariamente o plugin defeituoso e grava o relatório completo da falha em log e na tela de **Supervisão do Sistema** (`/admin/supervision`) para o administrador corrigir.
  - Em modo de **Desenvolvimento (`dev`)**, os erros continuam estourando na tela com stack trace completo para facilitar o debug.
  - Em modo de **Produção**, a prioridade é a experiência do aluno e a disponibilidade das vendas.
- **Segurança Nativa:** Proteção automática contra ataques de CSRF, injeção SQL via PDO com prepared statements e sanitização estrita de HTML contra XSS.

---

## 📊 Estatísticas e Testes

- **Linguagem:** PHP 8.2 ou superior (código moderno e tipado com `strict_types=1`).
- **Suíte de Testes Automatizados:** **536 testes unitários e de integração** no PHPUnit com **2.999 asserções** cobrindo segurança, checkout, gateways, matrículas e rotas.
- **Banco de Dados:** Suporte nativo e transparente para **MySQL** e **SQLite**.
- **Dependências Externas no Núcleo:** Zero. Não depende de frameworks gigantescos; utiliza apenas as extensões oficiais do PHP (`pdo`, `mbstring`, `zip`, `curl` e `openssl`).

---

## 🚀 Como Rodar Localmente

### Requisitos Mínimos
- PHP 8.2 ou superior com as extensões comuns ativadas: `pdo`, `mbstring`, `zip`, `curl`, `openssl` (e `pdo_mysql` ou `pdo_sqlite`).
- Servidor web (Apache, Nginx ou o próprio servidor embutido do PHP).

### Passo a Passo

```bash
# 1. Clone o repositório
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Inicie o servidor interno do PHP para testar
php -S localhost:8000 -t public
```

Abra o seu navegador e acesse:
👉 `http://localhost:8000`

O instalador inicial do sistema será exibido para você configurar o banco de dados e criar seu primeiro usuário administrador.

---

## 🧪 Executando os Testes Automatizados

Para rodar todos os testes do sistema com o PHPUnit:

```bash
php phpunit.phar
```

---

## 📁 Estrutura das Pastas

Para quem for explorar o código-fonte, a estrutura é bem organizada e intuitiva:

- `public/`: Ponto de entrada web (`index.php`) e arquivos estáticos (CSS, JS, imagens).
- `src/Core/`: Núcleo da aplicação (roteador, container de injeção de dependências e eventos).
- `src/SystemApps/`: Módulos de infraestrutura interna (autenticação, banco de dados, painel admin e atualizador).
- `src/Plugins/`: Módulos e extensões (LMS Academy, DomainCommerce, temas, construtor de páginas e SEO).
- `themes/`: Temas e layouts visuais do site.
- `tests/`: Bateria completa de testes automatizados com PHPUnit.

---

## 📄 Licença

Este projeto é software livre sob a licença [MIT](LICENSE).
