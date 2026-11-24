# 📚 Wiki Oficial do Domain System (v2.8.3)

> 🇧🇷 [Ler em Português](#-visão-geral-e-boas-vindas) | 🇺🇸 [Read in English](#-english-overview--welcome)  
> 🌐 **Documentação Online:** [https://hadelrachid.github.io/domain-system/](https://hadelrachid.github.io/domain-system/)  
> 💻 **Repositório GitHub:** [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

# 🇧🇷 Visão Geral e Boas-Vindas

Olá! Seja muito bem-vindo à documentação oficial do **Domain System**.

Eu sou o Hadel, desenvolvedor autodidata. Criei este projeto porque cansei de ver sistemas pesados que exigem servidores caros e dezenas de plugins de terceiros só para colocar uma loja ou uma escola online para rodar.

O Domain System foi feito em **PHP 8.2 puro**, ocupa míseros **~30 MB de espaço em disco** e consome cerca de **~5 MB de memória RAM** por requisição. Ele roda suave até na hospedagem compartilhada mais simples.

---

## 🏛️ Os Três Pilares da Plataforma

Para manter tudo rápido, fácil de atualizar e sem misturar as coisas, dividimos o projeto em 3 partes:

1. **O Núcleo (Domain-System Cockpit OS):**  
   O alicerce da casa. Contém o motor central (rotas, injeção de dependências, segurança e painel administrativo).
2. **Plugins Agregados ([domain-system-plugins](https://github.com/hadelrachid/domain-system-plugins)):**  
   Onde moram os módulos de aplicação como o **DomainDrop** (dropshipping AliExpress), o **DomainCommerce** (pagamentos Pix e cartões) e o **Academy LMS** (cursos e aulas). Você instala e atualiza com 1 clique direto pelo painel de controle.
3. **Temas ([domain-system-themes](https://github.com/hadelrachid/domain-system-themes)):**  
   Onde ficam os visuais prontos, como o tema oficial **RachidD Store** v1.0.0.

---

## 🤝 O Papel da I.A. e o Chamado à Comunidade

Como programo sozinho na maior parte do tempo, uso inteligências artificiais como assistentes de bancada para caçar falhas de lógica, riscos de concorrência e brechas de segurança. Hoje o sistema conta com mais de **760 testes automatizados no PHPUnit** e mais de **4.590 asserções**.

Ainda assim, **nada substitui o olhar atento, o bom senso e a bagagem de programadores humanos reais**.

Esta Wiki e o código estão de portas abertas para você:
- **Testar e auditar:** Tente encontrar brechas, aponte inconsistências e nos ajude a blindar o sistema.
- **Simplificar o código:** Ajude a manter tudo direto e gostoso de mexer.
- **Criar extensões:** Crie novos plugins e temas visuais leves.

---

## 🚀 Guias Passo a Passo

* 🧩 **[Como Criar um Plugin do Zero](Criando-Plugins)** — Crie sua primeira extensão em minutos com rotas, menu no admin e banco de dados.
* 🎨 **[Como Criar um Tema Visual](Criando-Temas)** — Construa layouts rápidos e responsivos usando PHP puro e HTML.
* 🔄 **[Atualizações com 1 Clique](Atualizacoes)** — Como funciona a atualização do Core e o atualizador de plugins estilo WordPress.
* 📐 **[Política de Versionamento SemVer](Versionamento)** — Como funciona a numeração de versões (MAJOR.MINOR.PATCH) nos 3 pilares.
* ⚡ **[Disjuntor de Segurança (Circuit Breaker)](Disjuntor-Circuit-Breaker)** — Como o sistema desliga plugins com erro para nunca derrubar seu site em produção.
* 📦 **[DomainDrop (Dropshipping)](DomainDrop)** — Entenda o motor de importação, mineração de ofertas 24h e rastreio.
* 🏗️ **[Flex Builder (Construtor Visual)](Flex-Builder)** — Como montar páginas arrastando blocos em uma grade de 12 colunas.
* 🛡️ **[Controle de Acessos e Permissões](Global-Gatekeeper)** — Segurança por cargos, permissões e 2FA.
* 🚨 **[Modo de Emergência](Emergency-Hatch)** — Procedimento seguro para recuperar acesso ao painel caso perca a senha.

---

## 📖 Documentação Interativa & Arquitetura Oficial

Além dos guias desta Wiki, você pode explorar a documentação técnica interativa oficial:

* 🚀 **[Guia Interativo de Extensões (Temas e Plugins)](https://hadelrachid.github.io/domain-system/tutorial-dev.html)** — Guia visual com injeção de dependências e exemplos práticos.
* 📜 **[Manifesto do Contrato do Sistema](https://hadelrachid.github.io/domain-system/01-o-contrato-do-sistema.html)** — A arquitetura de desacoplamento e governança de contratos.
* 🤖 **[O Ciclo de Confiança Verificável (IA)](https://hadelrachid.github.io/domain-system/architecture/01-ciclo-de-confianca-verificavel.html)** — Como a arquitetura foi desenhada para validação contínua e testes automatizados.

---

<br>

# 🇺🇸 English Overview & Welcome

Welcome to the official documentation for **Domain System**.

Domain System is a lightweight, high-performance web operating platform written in modern, strictly typed **PHP 8.2+**. It is designed from the ground up to solve common web hurdles: zero bloated vendor dependencies, fast database access (SQLite or MySQL), and real-time fault isolation.

### 🏛️ The Three Pillars
1. **Core Operating System (`domain-system`):** The rock-solid, bloat-free foundation.
2. **Decoupled Business Plugins (`domain-system-plugins`):** Autonomous e-commerce, dropshipping, and course apps.
3. **Decoupled Storefront Themes (`domain-system-themes`):** Fast visual themes like the official `rachidd_store`.

Explore the guides above or clone the repository to run it locally in under two minutes!
