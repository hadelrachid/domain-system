# Domain System (v2.8.9)

> **Um sistema web completo, ultraleve (~30 MB) e muito rápido feito em PHP 8.2 puro — sem frameworks pesados e sem dependências desnecessárias.**

Olá! Meu nome é Hadel e sou um desenvolvedor autodidata. Criei o **Domain System** com uma ideia bem clara na cabeça: provar na prática que é possível construir um sistema web completo, modular e seguro rodando liso com apenas **~5 MB de memória RAM** e ocupando míseros **~30 MB de espaço em disco**. Ele sobe redondo em qualquer hospedagem compartilhada barata ou VPS básica de 1 GB.

Aqui não tem inchaço. Nada de instalar dezenas de bibliotecas pesadas de terceiros para fazer coisas simples.

---

[🇺🇸 Read in English](README-en.md) | [🌱 Minha História](ABOUT.md) | [🔍 Por Dentro do Sistema](docs/documentacao-sistema/README.md) | [📚 Wiki Oficial](https://github.com/hadelrachid/domain-system/wiki) | [📖 Documentação](https://hadelrachid.github.io/domain-system/) | [🗺️ Roadmap](ROADMAP.md) | [👨‍💻 Guia do Desenvolvedor](DEVELOPER_GUIDE.md) | [🤝 Comunidade](#-preciso-da-sua-ajuda-junte-se-ao-projeto)

---

## 🏛️ A Filosofia dos Três Pilares

Para manter o sistema leve, modular e rápido de clonar no GitHub, organizamos o projeto em três pilares independentes e complementares:

1. **O Núcleo (Domain-System Cockpit OS — Este Repositório):**  
   É o alicerce do sistema. Contém o motor central (Kernel), o painel administrativo (CockPit), o sistema de usuários, segurança contra falhas (Disjuntor / Circuit Breaker) e extensões de amostragem (`clock_widget`, `radio_online` e o motor visual `flextheme`). É enxuto, rápido de clonar e resiliente.
2. **O Hub de Plugins ([domain-system-plugins](https://github.com/hadelrachid/domain-system-plugins)):**  
   O diretório central e intermediário de catálogo que direciona para os repositórios dedicados de cada módulo de negócio:
   * **[DomainDrop](https://github.com/hadelrachid/domain_drop):** Mineração de produtos no AliExpress, cálculo automático de margem de lucro e automação de pedidos.
   * **[DomainCommerce](https://github.com/hadelrachid/commerce):** Checkout desacoplado com Pix na tela, cartão em até 12x via Mercado Pago e PayPal, além de webhooks de afiliados (Hotmart, Eduzz, Monetizze).
   * **[Academy LMS](https://github.com/hadelrachid/academy):** Escola online completa com área de membros, progresso do aluno e emissão de certificados.
   * **Outros módulos dedicados:** [SEO & Engine](https://github.com/hadelrachid/seo), [Analytics Privado](https://github.com/hadelrachid/analytics), [FlexTheme](https://github.com/hadelrachid/flextheme), etc.
3. **O Hub de Temas ([domain-system-themes](https://github.com/hadelrachid/domain-system-themes)):**  
   O marketplace e catálogo aberto de vitrines visuais, permitindo que a comunidade e desenvolvedores terceiros hospedem temas em seus próprios repositórios no GitHub, como o tema oficial de dropshipping **[RachidD Store](https://github.com/hadelrachid/rachidd_store)**.

Você escolhe o que precisa e instala ou atualiza com 1 clique direto pelo painel de controle. Sem mexer em linha de comando se não quiser.

---

## 🤝 Preciso da sua ajuda: Junte-se ao projeto!

Como programo sozinho a maior parte do tempo, uso inteligências artificiais como assistentes de bancada para varrer o código atrás de detalhes que os olhos cansam de ver. Hoje o projeto já conta com uma suíte sólida de **767 testes automatizados no PHPUnit e mais de 4.600 asserções**.

Mas convenhamos: **código bom de verdade precisa de gente de verdade.** Nada substitui o olhar crítico, a malícia de segurança e a experiência de desenvolvedores humanos.

As portas estão abertas para quem quiser somar:
* **Auditores de Código e Segurança:** Venha testar nossos limites. Procure brechas, aponte inconsistências e nos ajude a deixar tudo à prova de falhas.
* **Desenvolvedores PHP:** Ajude a manter o código limpo, simples e gostoso de manter.
* **Testadores e Lojistas:** Suba o sistema, simule compras, cadastre produtos ou aulas e me conte o que achou da experiência.
* **Criadores de Temas e Plugins:** Crie temas no Flex Builder ou construa suas próprias extensões.

Se você gosta de software enxuto e quer construir algo bacana em conjunto, seu PR (Pull Request) ou Issue será muito bem-vindo!

👉 [Veja como contribuir no Guia do Desenvolvedor](DEVELOPER_GUIDE.md)  
🔍 [Por Dentro do Domain System — Guia Passo a Passo de Arquitetura e Start no Servidor](docs/documentacao-sistema/README.md)

---

## 💡 O que o Cockpit já entrega pronto

* **Painel Administrativo com Notificações:**  
  O sininho no canto superior avisa sempre que houver novidades ou novas versões de plugins e temas no GitHub.
* **Atualizador de Plugins e Temas em 1 Clique (Estilo WordPress):**  
  Acesse a aba de plugins ou temas e clique em "Atualizar". O sistema baixa o pacote oficial direto do repositório dedicado, confere a integridade criptográfica (SHA-256) e extrai com segurança, sem alertas feios de navegador.
* **Disjuntor de Segurança (Circuit Breaker):**  
  Se um plugin secundário causar um erro fatal, o sistema não deixa a tela branca de erro 500 derrubar o site. Ele desliga só o módulo problemático e mantém sua loja ou escola no ar.
* **Construtor Visual de Páginas (Flex Builder):**  
  Monte cabeçalhos, rodapés e seções arrastando blocos em uma grade de 12 colunas, escolhendo o que aparece no computador e o que aparece no celular.
* **Segurança Nativa:**  
  Proteção automática contra ataques de CSRF, injeção de SQL via PDO tipado e sanitização rigorosa contra XSS.

---

## 📊 Números e Requisitos

* **Linguagem:** PHP 8.2 ou superior.
* **Banco de Dados:** Funciona perfeitamente com **SQLite** (já vem pronto de fábrica, sem precisar instalar nada) ou **MySQL/MariaDB**.
* **Testes Automatizados:** **767 testes** cobrindo segurança, roteamento, pagamentos e lifecycle.
* **Zero Frameworks Pesados:** Apenas PHP puro e extensões padrão (`pdo`, `mbstring`, `zip`, `curl`, `openssl`).

---

## 🚀 Como testar na sua máquina em 2 minutos

Você só precisa ter o PHP 8.2 ou superior instalado:

```bash
# 1. Baixe o repositório
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Inicie o servidor embutido do PHP
php -S localhost:8000 -t public
```

Pronto! Abra seu navegador em:  
👉 `http://localhost:8000`

O instalador vai abrir na hora para você escolher o banco e criar sua senha de administrador.

Para rodar a suíte completa de testes:
```bash
php phpunit.phar
```

---

## 📁 Estrutura das Pastas

Para quem gosta de navegar pelo código:

* `public/`: A porta de entrada do site (`index.php`) e arquivos estáticos (CSS, JS, imagens).
* `src/Core/`: O motor principal (rotas, injeção de dependências, eventos e verificador de atualizações).
* `src/SystemApps/`: Módulos essenciais de administração (login, controle de acessos, instalador).
* `src/Plugins/`: Onde ficam os plugins instalados.
* `themes/`: Onde ficam os temas ativos do site.
* `tests/`: Bateria completa de testes automatizados no PHPUnit.

---

## 📄 Licença

O Domain System é software livre distribuído sob a licença [MIT](LICENSE). Você pode usar, estudar, modificar e aplicar no seu negócio sem pegadinhas.
