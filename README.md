# Domain System (v2.7.0)

> **Um sistema web completo, ultraleve (~30 MB) e rápido feito em PHP 8.2 puro — sem frameworks pesados, sem dezenas de plugins lentos.**

Olá! Meu nome é Hadel e sou um desenvolvedor autodidata. Criei o **Domain System** com uma ideia simples na cabeça: provar que é possível ter um CMS completo com loja virtual de dropshipping/e-commerce e escola de cursos online rodando liso, consumindo apenas **~5 MB de memória RAM** e ocupando **~30 MB em disco**, funcionando perfeito em qualquer hospedagem barata ou VPS simples.

Sem o peso, a lentidão e a insegurança de depender de centenas de plugins de terceiros.

---

[🇺🇸 English version](README-en.md) | [🌱 Minha História e Sobre o Projeto](ABOUT.md) | [🤝 Como Ajudar / Comunidade](#-preciso-da-sua-ajuda-junte-se-ao-projeto) | [🗺️ O que vem por aí (Roadmap)](ROADMAP.md) | [👨‍💻 Guia do Desenvolvedor](DEVELOPER_GUIDE.md)

---

## 🤝 Preciso da sua ajuda: Junte-se ao projeto!

Como programo a maior parte do tempo sozinho, utilizo ferramentas de Inteligência Artificial como minhas **parceiras diárias de bancada e auditoria**. Coloco diferentes modelos para varrer o código linha por linha, procurando falhas de lógica, riscos de segurança, problemas de concorrência e redundâncias. Hoje o projeto já conta com uma suíte de **656 testes automatizados no PHPUnit e mais de 3.900 asserções**.

Mas convenhamos: **I.A. só vai até certo ponto. Nada substitui o olhar, a bagagem e o bom senso de desenvolvedores humanos reais.**

Estou abrindo as portas do projeto e buscando pessoas que queiram somar forças:
- **Auditores de Código e Segurança:** Venha tentar quebrar o sistema! Encontre falhas, aponte brechas e sugira correções.
- **Arquitetos e Entusiastas de PHP:** Me ajude a manter o código simples, limpo e desacoplado, sem cair no exagero de complicar o que deveria ser direto.
- **Testadores:** Suba o sistema na sua máquina ou servidor, teste o fluxo de compras, crie cursos, monte temas e me diga o que funcionou e o que quebrou.
- **Desenvolvedores de Plugins e Temas:** Crie novas extensões usando nossos contratos leves.

Se você gosta da ideia de softwares leves, diretos ao ponto e quer construir algo legal junto comigo, seu PR (Pull Request), Issue ou crítica sincera será recebido de braços abertos!

👉 [Veja como contribuir no Guia do Desenvolvedor](DEVELOPER_GUIDE.md)

---

## 💡 O que o sistema já faz hoje?

O Domain System já vem com as ferramentas essenciais prontas para rodar:

### 📦 1. Loja Virtual & Dropshipping Internacional (DomainDrop)
- **Integração com a API do AliExpress:** Conexão nativa e segura com a AliExpress Open Platform para consultar produtos, calcular frete, criar pedidos e rastrear encomendas em tempo real.
- **Curadoria e Ofertas 24 Horas:** Prioriza produtos que já estão em estoque no Brasil (entrega rápida), calcula conversão de moedas (Dólar para Real) com sua margem de lucro e atualiza as ofertas da vitrine em ciclos automáticos de 24 horas.
- **Espaço do Cliente Completo (`/minha-conta`):** Área limpa para o comprador acompanhar o rastreio da encomenda (Correios e internacional) e abrir chamados de suporte enviando fotos ou documentos.
- **Painel Administrativo com Tradução Automática:** Acompanhe os pedidos, veja o prazo de entrega e responda a chamados com um botão que já traduz as mensagens para fornecedores internacionais.

### 🎓 2. Escola de Cursos Online (Academy LMS)
- **Área do Aluno:** Login simples, painel intuitivo e controle de progresso das aulas com barra de porcentagem.
- **Aulas em Vídeo, Texto e Leitor de PDF:** Suporte a vídeos do YouTube, Vimeo ou arquivos próprios, além de um leitor embutido de apostilas em PDF que não pesa no navegador.
- **Certificados de Conclusão:** Emissão automática em PDF quando o aluno conclui todas as aulas.

### 💳 3. Pagamentos sem Complicação (DomainCommerce)
- **Mercado Pago Nativo:**
  - **Pix na Tela:** QR Code e código Copia-e-Cola gerados na hora, conferindo o pagamento a cada poucos segundos e liberando o pedido ou matrícula imediatamente.
  - **Cartão de Crédito:** Parcelamento em até 12x (com opção de usar até 2 cartões na mesma compra).
  - **Boleto Bancário.**
- **PayPal:** Para quem quiser vender para clientes de fora do Brasil em moeda estrangeira.
- **Integração com Plataformas de Afiliados:** Recebe vendas da Hotmart, Eduzz, Monetizze e ClickBank via webhook e matricula o aluno automaticamente.

### 🎨 4. Construtor de Páginas Visual (Flex Builder)
- Crie cabeçalhos, rodapés e seções arrastando blocos em uma grade responsiva de 12 colunas.
- Defina facilmente o que aparece no computador e o que aparece no celular.
- Se você criar um tema dedicado com visual próprio (como o tema `rachidd_store`), o sistema isola o cabeçalho e rodapé para que edições visuais não quebrem o design original.

### 🔄 5. Atualizador com 1 Clique & Segurança
- **Atualização Direta:** O sistema verifica novas versões no GitHub e aplica atualizações pelo painel, fazendo backup automático do banco e dos arquivos antes de mexer em qualquer coisa.
- **Proteção contra Falhas:** Se um plugin secundário der erro inesperado, o sistema desarma temporariamente aquele plugin e mantém o restante do site no ar, gravando o erro para você corrigir (sem dar aquela tela branca de erro 500 para os clientes).
- **Segurança Nativa:** Proteção automática contra ataques comuns da web (CSRF, injeção SQL com PDO e filtros contra XSS).

---

## 📊 Números e Tecnologias

- **Linguagem:** PHP 8.2 ou superior (código estritamente tipado).
- **Testes Automatizados:** **656 testes** rodando no PHPUnit com **3.922 asserções** cobrindo checkout, segurança, pedidos, gateways e rotas.
- **Banco de Dados:** Funciona tanto com **MySQL/MariaDB** quanto com **SQLite** (sem precisar mexer no código).
- **Zero Frameworks Pesados no Núcleo:** Não usamos Laravel, Symfony ou pacotes gigantescos no core. Apenas as extensões normais do PHP (`pdo`, `mbstring`, `zip`, `curl`, `openssl`).

---

## 🚀 Como testar na sua máquina

Você só precisa do PHP 8.2 instalado no seu computador:

```bash
# 1. Baixe o código
git clone https://github.com/hadelrachid/domain-system.git
cd domain-system

# 2. Inicie o servidor embutido do PHP
php -S localhost:8000 -t public
```

Agora abra no seu navegador:  
👉 `http://localhost:8000`

O instalador vai abrir na hora para você escolher o banco de dados e criar seu login de administrador.

Para rodar todos os testes automatizados:
```bash
php phpunit.phar
```

---

## 📁 Como o código está organizado

Se quiser abrir o projeto no seu editor de código, a estrutura é bem direta:

- `public/`: A porta de entrada do site (`index.php`) e os arquivos visuais (CSS, JS, imagens).
- `src/Core/`: O coração do sistema (o roteador de páginas, o container de dependências e eventos).
- `src/Plugins/`: Onde ficam os módulos (`domain_drop` para dropshipping, `academy` para cursos, `commerce` para pagamentos, `flextheme` para o editor visual).
- `src/SystemApps/`: Funções internas de apoio (login do admin, conexão de banco, atualizador).
- `themes/`: Os temas visuais e páginas do site.
- `tests/`: Todos os testes unitários e de integração do PHPUnit.

---

## 📄 Licença

O Domain System é software livre sob a licença [MIT](LICENSE). Você pode usar, estudar, modificar e colocar no seu próprio negócio à vontade.
