# Estou construindo minha própria plataforma PHP e tenho aprendido muito ao longo do caminho

> 🇧🇷 [Ler em Português](ABOUT.md) | 🇺🇸 [Read in English](ABOUT-en.md)

---

Sou um desenvolvedor autodidata e, há algum tempo, venho trabalhando em um projeto chamado **Domain System**.

Tudo começou com uma ideia simples: e se eu pudesse criar minha própria plataforma PHP, na qual o sistema principal e seus plugins tivessem responsabilidades bem definidas, sem depender excessivamente uns dos outros?

À medida que continuei trabalhando, o projeto cresceu. Precisei pensar em como organizar o código, como os plugins deveriam se comunicar com o núcleo (*core*), como tratar erros e como evitar que algo que já funcionava parasse de funcionar repentinamente.

Não vou fingir que tenho todas as respostas. Ainda estou aprendendo, cometendo erros e voltando atrás para aprimorar as coisas. Mas é justamente isso que torna este projeto fascinante para mim.

Você pode conferir o código-fonte aberto no GitHub aqui:  
👉 [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)

---

## Por que comecei a pensar de forma diferente sobre o PHP

Ao refletir sobre como organizar o projeto, comecei a me interessar pela maneira como os sistemas operacionais modernos separam seus componentes essenciais das aplicações que rodam sobre eles.

Pensei: será que posso aplicar um pouco desse conceito a uma plataforma web em PHP?

No **Domain System**, busco manter dois elementos fundamentais e distintos:

- **O núcleo (core):** A parte responsável pelos fundamentos necessários para o funcionamento do sistema, como roteamento, injeção de dependências, armazenamento e outros serviços compartilhados.
- **Os plugins:** Os componentes que adicionam funcionalidades de negócio à plataforma (como LMS de cursos, construtor visual de páginas, gestão de links e SEO).

A ideia é direta: não quero que o núcleo precise conhecer os detalhes específicos de cada plugin. Prefiro que a comunicação ocorra estritamente por meio de interfaces e contratos; assim, posso alterar uma parte sem precisar modificar todo o restante.

Na prática, acertar isso é bem mais difícil do que parece. É possível criar interfaces e, ainda assim, acabar com um código excessivamente dependente de classes concretas. Esse é um aspecto ao qual tenho prestado cada vez mais atenção conforme o projeto evolui.

---

## Algumas coisas nas quais tenho trabalhado

### Instalação de plugins de forma mais segura

Um aspecto que exigiu reflexão cuidadosa foi a instalação de plugins via painel.

Ao enviar um arquivo compactado (ZIP), não se pode simplesmente presumir que todos os arquivos contidos nele sejam seguros para serem extraídos em qualquer lugar do servidor.

Por isso, adicionei uma etapa em que os arquivos enviados são inspecionados antes de serem movidos para o destino final. O processo inclui verificações contra problemas clássicos como *path traversal* (travessia de diretórios) e *ZIP Slip*.

Não diria que isso garante a prevenção de todos os ataques possíveis. A segurança exige muito mais do que apenas algumas verificações, e sei que essa é uma área que requer estudo e atenção contínua.

### Facilitando a recuperação após problemas

Outro desafio constante é lidar com erros sem deixar o sistema em um estado instável ou comprometido.

O *Domain System* inclui persistência atômica em JSON (`AtomicJsonStore`), técnicas de bloqueio de arquivos (*file-locking* com `LOCK_EX`), ferramentas de verificação de esquema de banco de dados (`SchemaDoctorInterface`), além de isolamento de falhas em plugins (Circuit Breaker) e rotinas de *snapshot* e *rollback* automático antes de atualizações.

Essas medidas visam facilitar a identificação de problemas e a recuperação após a ocorrência deles. Sei que imprevistos ainda podem acontecer, mas prefiro criar mecanismos para lidar com falhas do que simplesmente presumir que elas nunca ocorrerão.

### Criando meu próprio editor visual de páginas

Também tenho trabalhado no **Flex Builder**, um editor visual para a criação de layouts de páginas, cabeçalhos e rodapés (Full Site Editing).

Um dos meus objetivos principais era que a pré-visualização no editor fosse o mais fiel possível ao site real publicado. Para garantir isso, ambos utilizam exatamente o mesmo processo de compilação por meio de um contrato compartilhado (`ThemePartCompilerInterface`).

Além disso, tenho trabalhado em layouts responsivos em CSS Grid de 12 colunas, de modo que, ao ocultar um elemento em uma tela menor (tablet ou mobile), as colunas restantes se reorganizem automaticamente sem deixar espaços vazios indesejados (*auto-reflow*).

Um editor como esse envolve centenas de detalhes, e tem sido uma excelente experiência de aprendizado.

### Usando IA sem perder o controle do código

Também utilizo ferramentas de IA voltadas para programação para me auxiliarem no desenvolvimento do projeto.

Elas podem ser incrivelmente úteis, especialmente ao lidar com uma base de código que já conta com dezenas de milhares de linhas. No entanto, aprendi na prática que obter um trecho de código que funciona isoladamente não é o mesmo que obter um código que se integre bem à arquitetura do projeto.

Às vezes, uma alteração gerada pode resolver um problema imediato, mas acabar criando outro oculto. Uma nova classe, por exemplo, pode funcionar corretamente no teste pontual, mas introduzir dependências que tornam futuras modificações muito mais difíceis.

Para ajudar a evitar isso, criei documentações de contratos ([`AI_CONTRACT_GUIDE.md`](AI_CONTRACT_GUIDE.md)) e testes de arquitetura automatizados que explicam como o projeto é organizado e como novos plugins devem ser desenvolvidos. Também existe uma ferramenta de linha de comando (`os-cli.php make:plugin`) para gerar a estrutura básica padronizada de um plugin.

Ainda estou buscando a melhor forma de utilizar a IA sem tornar o código difícil de manter. A documentação e os contratos ajudam, mas o desenvolvedor ainda precisa revisar cada alteração, rodar testes e entender profundamente o que o código faz.

---

## Um pouco sobre a tecnologia

O Domain System é construído em **PHP 8.2+**. Ele oferece suporte a MySQL/MariaDB e SQLite por meio de PDO, além de contar com uma opção própria de armazenamento estruturado em JSON com travas concorrentes.

Utilizo o **PHPUnit** para testes automatizados. O projeto conta atualmente com **438 testes unitários** e mais de **2.320 asserções** automatizadas cobrindo ciclo de vida, segurança, roteamento, contratos e compilação visual.

Esses números são úteis como métrica de estabilidade, mas não contam a história completa. É importante que os testes passem, mas também é fundamental verificar continuamente se o código está organizado de maneira compreensível e sustentável ao longo do tempo.

---

## Estou compartilhando isso porque gostaria de aprender com outras pessoas

Este projeto me ensinou que criar algo do zero é um desafio enorme; mantê-lo limpo, seguro e compreensível à medida que cresce é um desafio ainda maior.

Feedbacks sinceros, críticas construtivas e sugestões de arquitetura são sempre muito bem-vindos de qualquer colega desenvolvedor!
