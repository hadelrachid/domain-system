# Construindo um sistema web do zero: Por que escolhi fazer isso e por que preciso de você

> 🇧🇷 [Ler em Português](ABOUT.md) | 🇺🇸 [Read in English](ABOUT-en.md)

---

Olá! Meu nome é Hadel e sou um desenvolvedor autodidata.

Há algum tempo, decidi embarcar em um desafio grande: criar o meu próprio ambiente operacional e plataforma web em PHP puro, batizado de **Domain System**.

Tudo começou com uma indignação e uma pergunta: por que hoje em dia precisamos de frameworks gigantescos com centenas de pacotes de terceiros, ou de CMSs tradicionais que consomem centenas de megabytes de RAM e dependem de dezenas de plugins instáveis apenas para colocar uma loja ou uma escola de cursos no ar?

Eu queria o oposto: um sistema simples, leve (que coubesse em **~30 MB de disco** e gastasse **~5 MB de memória RAM**), rápido, seguro e que funcionasse até na hospedagem compartilhada mais barata do mercado.

O código está aberto para todo mundo ver e testar no GitHub:  
👉 [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

## Sem "fantasiar o pavão": Sou autodidata e aprendo na prática

Eu não tenho diploma de ciência da computação nem trabalho em uma big tech. Tudo o que sei aprendi lendo documentação, errando, quebrando coisas no servidor, passando horas procurando bugs e refazendo o código até ele ficar bom.

Por não ter uma equipe de engenheiros ao meu lado, eu uso ferramentas de **Inteligência Artificial (como Claude, ChatGPT, Nemotron e DeepSeek) como minhas parceiras de bancada e auditoria**.

Minha rotina é direta:
1. Eu penso na solução, crio a lógica e estruturo o código.
2. Em seguida, coloco as I.A.s para **auditar o código linha por linha de forma implacável**.
3. Peço para elas agirem como revisores críticos: caçando falhas de concorrência, brechas de injeção SQL, problemas de CSRF, regras de negócio mal tratadas, redundâncias e código morto.
4. Para cada problema encontrado, eu escrevo testes de regressão no PHPUnit para garantir que aquele erro nunca mais volte a acontecer.

Hoje, essa disciplina diária resultou em uma suíte robusta de **656 testes automatizados e 3.922 asserções** cobrindo checkout, controle de estoque, criptografia, webhooks de pagamento e segurança.

Mas aqui está a verdade mais importante: **I.A. tem limite. Ela ajuda a caçar padrões, mas não tem a vivência, a intuição e o bom senso de um programador humano experiente.**

---

## Por que preciso de uma comunidade de programadores?

Nenhum desenvolvedor sozinho, por mais dedicado que seja, consegue antecipar todos os cenários de uso de um sistema em escala real. 

É por isso que estou sendo transparente e fazendo este **chamado aberto**:

Preciso de pessoas que queiram somar forças para fazer o Domain System ser cada vez mais estável, seguro, leve e rápido.

### O que você pode fazer para ajudar?
- **Auditar o código:** Olhe o repositório com olhar crítico. Se encontrar uma linha que parece frágil ou uma lógica mal construída, abra uma *Issue* ou mande um *Pull Request*. Críticas sinceras são muito bem-vindas!
- **Revisar a arquitetura:** O sistema usa contratos e injeção de dependências, mas às vezes eu mesmo fico na dúvida se uma parte não poderia ser mais desacoplada ou se não estamos complicando o que deveria ser simples. Sua opinião técnica é ouro para mim.
- **Testar em ambientes reais:** Suba o sistema em servidores diferentes (Nginx, Apache, Docker, VPS, hospedagem compartilhada) e tente usá-lo de verdade. Encontrou um aviso ou algo quebrou? Me avise.
- **Contribuir com código:** Se quiser criar um plugin novo, melhorar o motor de temas ou refinar os gateways de pagamento, o projeto é totalmente livre (licença MIT).

---

## O que o sistema tem hoje (Sem enrolação)

- **Núcleo sem dependências externas:** Roteamento rápido, injeção de dependências e eventos sem precisar instalar toneladas de pacotes pelo Composer.
- **Motor de Dropshipping com API do AliExpress (DomainDrop):** Cálculo de frete real, criação de ordens, rotação diária de ofertas e área do cliente com rastreio de encomendas.
- **Escola de Cursos (Academy LMS):** Aulas em vídeo, leitor leve de PDF embutido e emissão de certificados.
- **Checkout e Pagamentos (DomainCommerce):** Pix instantâneo, cartão de crédito em até 12x via Mercado Pago, PayPal e webhooks de afiliados (Hotmart, Eduzz, etc.).
- **Construtor Visual (Flex Builder):** Criação de cabeçalhos, rodapés e seções em CSS Grid de 12 colunas adaptável para celular e computador.
- **Tolerância a Falhas (Circuit Breaker):** Se um plugin der erro inesperado em produção, ele não derruba o site inteiro com tela branca. Ele desarma apenas o plugin defeituoso e mantém o restante da loja ou escola no ar.

---

## Vamos conversar?

Se você gosta da ideia de resgatar o que o PHP tem de melhor — ser simples, direto, rápido e rodar em qualquer lugar sem frescura —, venha fazer parte.

- Repositório no GitHub: [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)
- E-mail pessoal: `hadelrachid@gmail.com`

Toda contribuição, sugestão ou crítica sincera é muito bem-vinda. Obrigado por ler até aqui!
