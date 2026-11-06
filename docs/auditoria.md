# 🛡️ Arquitetura e Segurança do Domain System (v2.6)

Este documento descreve as decisões de arquitetura, padrões de código e práticas de segurança adotadas no **Domain System**.

---

## 🏛️ Princípios de Arquitetura

O sistema foi estruturado para ser modular, direto e de fácil manutenção, seguindo boas práticas de desenvolvimento em PHP moderno:

### 1. Separação Clara de Responsabilidades
- **Núcleo (`src/Core`)**: Contém apenas o que é estritamente essencial para o sistema funcionar (roteador, container de injeção de dependências, despachante de eventos e gerenciador de sessão). Ele não guarda regras de negócio específicas nem lógica de banco de dados engessada.
- **Módulos Base (`src/SystemApps`)**: Funcionalidades estruturais da plataforma (conexão com banco de dados, sistema de autenticação e permissões, painel administrativo e atualizador).
- **Plugins (`src/Plugins`)**: Módulos que adicionam recursos ao site (como o **Academy** para cursos online, o **DomainCommerce** para pagamentos e o **Flex Builder** para temas).

### 2. Injeção de Dependências e Contratos
- Em vez de instanciar classes concretas diretamente dentro dos controllers (`new Servico()`), os componentes declaram suas dependências por meio de **interfaces (contratos)** em seus construtores.
- O container do núcleo resolve e injeta automaticamente as dependências necessárias, facilitando a troca de implementações e a criação de testes automatizados.

### 3. Eliminação de Superglobais
- Os controllers não acessam variáveis superglobais diretamente (`$_POST`, `$_GET`, `$_SESSION`).
- Toda requisição HTTP é tratada como um objeto `Request` imutável, e o gerenciamento de sessões passa pelo `SessionManagerInterface`, garantindo validação de tipos e dados sanitizados.

---

## 🧩 Principais Recursos e Módulos

### 🎓 Academy (Plataforma de Cursos)
- Área do aluno com autenticação segura e painel do estudante.
- Aulas organizadas por módulos com suporte a vídeos, textos em blocos e **leitor de apostilas/PDF embutido**.
- Acompanhamento de progresso em tempo real e emissão de certificados automáticos.

### 💳 DomainCommerce (Pagamentos e Cobranças)
- **Mercado Pago Nativo:** Pix dinâmico gerado na hora na tela (com QR Code e Copia-e-Cola), verificação a cada 3,5 segundos com liberação instantânea de matrícula, além de cartão em até 12x e boleto bancário.
- **PayPal Oficial:** Checkout internacional via API REST v2 do PayPal com saldo e cartões internacionais.
- **Webhooks de Afiliados:** Integração com Hotmart, Eduzz, Monetizze e ClickBank para criação automática de alunos e matrículas na confirmação de venda.
- **Injeção Segura:** Apenas gateways configurados com credenciais válidas são exibidos no checkout.

### 🎨 Flex Builder (Temas e Layouts)
- Construtor visual de cabeçalhos, rodapés e seções em grid de 12 colunas.
- Controle de visibilidade independente por resolução (Desktop, Laptop, Tablet e Celular) com reajuste automático das colunas (*Auto-Reflow*).
- Menus com submenus em múltiplos níveis (arraste horizontal estilo WordPress) e gaveta mobile personalizável.

---

## 🔒 Segurança e Resiliência

1. **Prevenção de SQL Injection:** Todas as operações com banco de dados utilizam PDO com *prepared statements*.
2. **Proteção contra CSRF:** Verificação obrigatória de tokens anti-CSRF em todas as rotas de mutação (`POST`).
3. **Sanitização de HTML:** O sistema sanitiza conteúdos ricos e vetores SVG para prevenir ataques de Cross-Site Scripting (XSS).
4. **Isolamento de Falhas (Circuit Breaker):** Se um plugin externo apresentar erro fatal, o sistema captura a exceção e isola o plugin, mantendo o restante da aplicação funcionando sem derrubar o servidor.
5. **Atualizador Seguro:** Atualizações via GitHub Releases contam com verificação de integridade por hash SHA-256 e criação prévia de ponto de restauração (código e banco de dados).

---

## 🧪 Cobertura de Testes Automatizados

A estabilidade da plataforma é garantida por uma suíte completa de testes no **PHPUnit**:
- **536 testes automatizados** e **2.999 asserções** cobrindo segurança, checkout, gateways, matrículas, autenticação e contratos.
