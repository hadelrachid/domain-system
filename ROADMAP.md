# Roteiro de Desenvolvimento — Domain System (v2.8.3)

Este documento acompanha passo a passo o crescimento do **Domain System**. Nosso objetivo é simples: manter o sistema leve, modular, fácil de entender e coberto por testes automatizados em PHP 8.2+.

---

## 🟢 Etapa 1: Estrutura Base e Segurança (Concluída)

* [x] **Tratamento Seguro de Falhas:** Captura centralizada de erros para que falhas em pequenos módulos nunca derrubem o site inteiro.
* [x] **Gravação Segura de Configurações (`AtomicJsonStore`):** Gravação com bloqueio de arquivo (`LOCK_EX`) para evitar que dados se corrompam em acessos simultâneos.
* [x] **Doutor do Banco de Dados (`SchemaDoctorInterface`):** Verificação e autocura de tabelas tanto em SQLite quanto em MySQL.
* [x] **Injeção de Dependências Descomplicada:** Código limpo e desacoplado, sem variáveis globais escondidas.
* [x] **Controle de Acessos:** Perfis e permissões ajustáveis pelo painel, com proteção contra CSRF e suporte a dois fatores (2FA).
* [x] **Instalador Visual:** Assistente passo a passo para criar o primeiro usuário e configurar o banco de dados em segundos.

---

## 🟢 Etapa 2: Construtor Visual e Atualizador do Sistema (Concluída)

### 2.1. Flex Builder (Editor Visual de Páginas)
- [x] **Montagem por Blocos:** Cabeçalhos, rodapés e seções montados arrastando blocos visuais na tela.
- [x] **Visualização em Tempo Real:** O que você vê no editor é exatamente o que o visitante vê no navegador.
- [x] **Design Adaptável (Desktop, Notebook, Tablet e Celular):** Ajuste fino de quais blocos aparecem em cada tamanho de tela.
- [x] **Seções Vinculadas a Páginas:** Conexão simples entre seções e modelos de página (`home.php`, `sobre.php`).

### 2.2. Sistema de Atualização do Núcleo (`SystemUpdater`)
- [x] **Atualizações Seguras pelo GitHub:** O sistema localiza novas versões, confere a impressão digital do arquivo (SHA-256) e instala sem perder suas configurações.
- [x] **Cópia de Segurança Prévia:** Backup automático antes de qualquer atualização.

---

## 🟢 Etapa 3: Escola Online e Pagamentos (Concluída)

### 3.1. Portal do Aluno (Academy LMS)
- [x] **Área de Membros Amigável:** Cadastro, login fácil, painel com aulas assistidas e barra de progresso.
- [x] **Vídeos, Textos e Apostilas em PDF:** Leitor de PDF leve integrado na própria aula, sem travar o celular do aluno.
- [x] **Certificados Automáticos:** Geração de certificado em PDF quando o aluno conclui o curso.

### 3.2. Pagamentos sem Complicação (DomainCommerce)
- [x] **Mercado Pago com Pix na Tela:** QR Code instantâneo com conferência em segundo plano e liberação imediata.
- [x] **Cartão em até 12x:** Suporte a parcelamento e opção de dividir o valor em 2 cartões diferentes.
- [x] **PayPal:** Para vender cursos ou produtos para quem mora fora do Brasil.
- [x] **Integração com Plataformas de Afiliados:** Alunos matriculados na hora via webhooks da Hotmart, Eduzz, Monetizze e ClickBank.

---

## 🟢 Etapa 4: Dropshipping Internacional com AliExpress (Concluída)

- [x] **Conexão Oficial com o AliExpress:** Conexão nativa para consultar produtos, calcular frete internacional e criar pedidos.
- [x] **Vitrine com Ofertas 24 Horas:** Seleção diária de itens em estoque nacional com frete rápido e conversão automática de dólar para real com sua margem de lucro.
- [x] **Área do Cliente (`/minha-conta`):** Rastreio de encomendas em 5 etapas e abertura de chamados com envio de fotos.
- [x] **Painel de Atendimento:** Central com botão de tradução rápida de mensagens para fornecedores internacionais.

---

## 🟢 Etapa 5: Alinhamento Oficial AliExpress & Resiliência OAuth (Concluída na v2.8.3)

- [x] **Alinhamento com Manuais Oficiais do AliExpress:** Consultas padronizadas seguindo as especificações da plataforma (`dropshiper.pdf` e `categoria-e-atributos.pdf`).
- [x] **Fotos Corretas e Sem Erros 404:** Fim de imagens trocadas na vitrine e garantia de links sempre válidos.
- [x] **OAuth 2.0 Estável:** Conexão persistente no painel, inclusive em servidores compartilhados com proxies HTTPS.
- [x] **Suíte com 760 Testes Automatizados:** Mais de 4.590 asserções validadas pelo PHPUnit no GitHub Actions.

---

## 🟢 Etapa 6: A Arquitetura dos Três Pilares & Atualizador em 1 Clique (Concluída na v2.8.3)

- [x] **Núcleo Super Leve:** O repositório central (`domain-system`) focado apenas no Cockpit OS e infraestrutura vital.
- [x] **Repositório de Extensões ([domain-system-plugins](https://github.com/hadelrachid/domain-system-plugins)):** Módulos de negócio (`domain_drop`, `commerce`, `academy`) com versionamento próprio.
- [x] **Repositório de Temas ([domain-system-themes](https://github.com/hadelrachid/domain-system-themes)):** Temas desacoplados, começando pelo `rachidd_store` v1.0.0.
- [x] **Atualizador Estilo WordPress:** Notificações no sininho e botão "Atualizar Agora" para cada extensão direto pelo painel de controle.

---

## 🔵 Etapa 7: Próximos Passos (Planejamento Futuro)

- [ ] **Filtros Avançados de Atributos no Dropship:** Refinamento de cores, tamanhos e modelos de produtos importados.
- [ ] **Estoque Próprio Nacional:** Possibilidade de mesclar produtos de estoque físico próprio com cálculo de frete dos Correios e Melhor Envio.
- [ ] **Espaço de Dúvidas nas Aulas:** Fórum leve por aula para alunos tirarem dúvidas entre si e com o professor.
- [ ] **Caderno de Anotações do Aluno:** Espaço para o estudante fazer anotações pessoais durante os vídeos.

---

## 🤝 Quer trocar uma ideia sobre o projeto?

O roadmap é aberto e feito para resolver necessidades da vida real. Se você tem sugestões, encontrou algo para melhorar ou quer contribuir com código:
1. Abra uma **Issue** ou discussão no GitHub: [https://github.com/hadelrachid/domain-system](https://github.com/hadelrachid/domain-system)
2. Mande um e-mail para mim: `hadelrachid@gmail.com`
3. Fique à vontade para mandar um Pull Request!
