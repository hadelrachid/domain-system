# 📘 Manual Operacional: Plataforma do Aluno e Pagamentos (DomainCommerce & Academy)

> **Versão do Sistema:** Domain-System v2.6.0  
> **Módulos:** Academy EAD & DomainCommerce  
> **Status:** Ativo e Validado com Bateria de Testes  

---

## 🎯 Visão Geral da Arquitetura

A plataforma de cursos (**Academy**) e o motor de pagamentos (**DomainCommerce**) funcionam de forma independente e desacoplada, utilizando contratos PHP (interfaces) e eventos reativos do Kernel do sistema.

- **Zero dependência direta de banco de dados entre módulos:** O DomainCommerce não sabe nada sobre tabelas de cursos; ele se comunica via contrato `PurchasableItemInterface`.
- **Ativação Instantânea:** Quando o pagamento é liquidado (seja via Pix em segundos, cartão de crédito ou webhook), o evento `payment.approved` ativa a matrícula do aluno automaticamente.
- **Privacidade e LGPD:** Cada aluno possui um diretório físico isolado (`storage/students/{id}/`), impedindo qualquer vazamento de anotações ou dados sensíveis.

---

## 🛍️ 1. Como Funcionam os Pagamentos (DomainCommerce)

### A. Gateways e Meios de Pagamento Disponíveis

| Gateway | Meios Suportados | Prazo de Liberação |
| :--- | :--- | :--- |
| **🇧🇷 Mercado Pago** | **Pix** (QR Code Dinâmico e Copia-e-Cola)<br>**Cartão de Crédito** (em até 12x)<br>**Boleto Bancário** | • Pix: Instantâneo (segundos)<br>• Cartão: Instantâneo<br>• Boleto: 1 a 2 dias úteis |
| **🌎 PayPal** | Cartão Internacional, Saldo PayPal e moedas estrangeiras (USD, EUR, GBP) | Instantâneo |
| **💳 Stripe** | Cartão de Crédito Global (mais de 135 países) | Instantâneo |
| **🚀 Hotmart, Eduzz, Monetizze & ClickBank** | Plataformas de afiliados e checkouts externos com auto-provisionamento via Webhook | Instantâneo via Webhook |
| **🔌 Outros (Hooks)** | Asaas, PagSeguro, Efí, etc. (extensíveis via código) | Configurável |

---

### B. O Fluxo de Compra Passo a Passo

```
1. Visitante acessa o curso
   └─► Aula paga exibe: "🔒 Conteúdo Exclusivo para Alunos Matriculados"
   └─► Clica em: "🛒 Matricular-se por R$ 197,00"

2. Tela de Checkout (/checkout/curso/{id})
   └─► Aluno escolhe o Gateway (Mercado Pago, PayPal ou Stripe)
   └─► Aluno escolhe o Método (Pix, Cartão até 12x ou Boleto)
   └─► Sistema gera um código de pedido único: ORD_20261104_ABC123

3. Liquidação do Pagamento
   ├─► Pix: Aluno escaneia o QR Code no app do banco ou usa o Copia-e-Cola
   ├─► Cartão: Processamento e tokenização segura direto na API do banco
   └─► Webhook bancário recebe a notificação em tempo real

4. Desbloqueio Imediato
   └─► Kernel dispara o evento: 'payment.approved'
   └─► Aluno é redirecionado para o seu Cockpit (/aluno/painel) com o curso 100% liberado!
```

---

## 👤 2. Autenticação e Área de Login no Header

### A. Botões de Login e Cadastro no Header
O tema principal e o **Header & Footer Builder** agora contam com o bloco nativo **`UserAuthBlock`** (`user_auth`):
- **Visitante Deslogado:**
  - Botão **"Entrar"** com link para `/aluno/login`.
  - Botão de destaque **"Cadastrar"** com link para `/aluno/cadastro`.
- **Aluno Autenticado:**
  - Atalho com badge **"🎓 Meu Painel (Nome do Aluno)"** com link para `/aluno/painel`.
  - Botão discreto **"Sair"** (`/aluno/logout`).

### B. Páginas Dedicadas de Acesso do Aluno
- **Página de Login (`/aluno/login`):** Tela moderna com visual dark/glass, proteção CSRF, validação de e-mail e senha, e redirecionamento automático pós-login para o painel de aulas.
- **Página de Cadastro (`/aluno/cadastro`):** Criação imediata de conta de aluno com confirmação de senha e auto-login direto para a sala de aula.

---

## 🎓 3. Como Funciona a Plataforma do Aluno (Academy)

### A. O Cockpit do Aluno (`/aluno/painel`) e Compra de Cursos

Ao acessar a área do aluno:
1. **📚 Meus Cursos Matriculados:** Aulas em andamento com barra de progresso percentual e botão "Continuar Aula".
2. **🛒 Cursos Disponíveis para Compra & Novas Formações:**
   - Vitrine de novos cursos com imagem de capa 16:9, título, descrição e preço.
   - **Parcelamento Automático:** Exibe o valor total e o parcelamento (ex: `R$ 197,00 em até 12x de R$ 16,42`).
   - **Botão Inteligente de Compra:**
     - Se o curso for vendido pelo **DomainCommerce interno**: botão abre `/checkout/curso/{id}` para pagar via **Pix**, **Cartão até 12x** ou **PayPal**.
     - Se for vendido por **Plataforma de Afiliados** (Hotmart, Eduzz, Monetizze ou ClickBank): botão abre diretamente o link seguro de checkout da plataforma externa (ex: `https://pay.hotmart.com/...`).
     - Se for gratuito: libera o acesso imediato.
3. **📁 Minha Pasta Privada de Estudos:** Repositório isolado de anotações e arquivos do aluno (LGPD).

1. **Visão Geral dos Cursos:** Cartões responsivos com imagem de capa, título e carga horária de cada curso matriculado.
2. **Barra de Progresso Percentual:** Mede o avanço exato do aluno (ex: `75% concluído`).
3. **Botão "Continuar Assistindo":** Identifica automaticamente qual é a próxima aula pendente e leva o aluno direto para ela em 1 clique.
4. **Histórico de Pedidos:** Acesso aos recibos e status de cobrança de suas matrículas.

---

### B. A Sala de Aula do Aluno (Player de Aulas)

Ao assistir a qualquer aula (`/cursos/{slug-do-curso}/{slug-da-aula}`):

1. **Vídeos e Apostilas Multi-Cloud:**
   - Vídeos em proporção **16:9** (YouTube, Vimeo, MP4 próprio).
   - Apostilas e PDFs embutidos de forma híbrida: **Google Drive** (`/preview`), **OneDrive/SharePoint** (`action=embedview`), **Dropbox** ou arquivos locais de até **128 MB** protegidos contra download.
2. **Botão `[✓ Marcar como Concluída]`:**
   - O aluno clica e a aula é marcada com um visto verde (`✓ Aula Concluída`).
   - O progresso no painel do aluno é recalculado em tempo real via AJAX.
3. **Gaveta `[📝 Minhas Anotações]`:**
   - O aluno clica e uma gaveta lateral deslizante se abre sem pausar nem atrapalhar o vídeo.
   - O aluno digita suas observações, dúvidas ou trechos de código da aula.
   - Ao clicar em **"Salvar Anotação"**, o texto é salvo de forma criptografada/isolada em `storage/students/{id}/notes/`. Nem outros alunos nem visitantes têm acesso a esse arquivo.

---

## ⚙️ 3. Guia de Administração (Para o Dono da Plataforma)

### A. Gerenciando Vendas e Pedidos (`/admin/commerce`)

No menu lateral do painel de controle, clique em **🛒 DomainCommerce**:
- **Métricas:** Total Faturado (R$), Quantidade de Pedidos Pagos, Pix Aguardando Pagamento e Conectores Ativos.
- **Tabela de Pedidos:** Lista de todas as tentativas e compras realizadas, exibindo nome do aluno, valor, gateway utilizado e data.
- **Aprovação Manual:** Se um aluno efetuar uma transferência manual ou Pix direto e você quiser liberá-lo na hora, basta clicar em **"✓ Aprovar"** na tabela. O sistema aprovará o pedido e liberará o curso para o aluno no mesmo segundo.

---

### B. Configurando Chaves e Credenciais (`/admin/commerce/settings`)

No botão **"⚙️ Configurar Gateways"**:
1. **Moeda:** Escolha `BRL` (Real Brasileiro), `USD` (Dólar) ou `EUR` (Euro).
2. **Mercado Pago:**
   - Cole seu **Access Token** de produção ou teste (`APP_USR-...`).
   - Cole sua **Public Key**.
   - A URL de Webhook para configurar no painel do Mercado Pago é:  
     `https://seusite.com/webhook/payment/mercadopago`
3. **PayPal:**
   - Cole seu **Client ID** e **Client Secret**.
   - URL de Webhook: `https://seusite.com/webhook/payment/paypal`
4. **Stripe:**
   - Cole sua **Secret Key** (`sk_live_...` ou `sk_test_...`) e **Publishable Key**.
   - URL de Webhook: `https://seusite.com/webhook/payment/stripe`
5. **Plataformas de Afiliados (Hotmart, Eduzz, Monetizze & ClickBank):**
   - **Hotmart:** Cole seu **Hottok**. Webhook: `https://seusite.com/webhook/payment/hotmart`
   - **Eduzz:** Cole sua **Public Key / Token**. Webhook: `https://seusite.com/webhook/payment/eduzz`
   - **Monetizze:** Cole sua **Chave Única**. Webhook: `https://seusite.com/webhook/payment/monetizze`
   - **ClickBank:** Cole sua **Secret Key (INS)**. Webhook: `https://seusite.com/webhook/payment/clickbank`
   - **Auto-Provisionamento:** Ao receber a venda aprovada de qualquer uma dessas plataformas, o DomainCommerce cria automaticamente a conta do aluno na tabela `users` e o Academy ativa a matrícula no mesmo segundo!
6. Clique em **"💾 Salvar Configurações"**. As credenciais são armazenadas em formato atômico e seguro em `storage/commerce/settings.json`.

---

## 📱 5. Perfis de Experiência Mobile do Aluno

O player foi otimizado para celulares e tablets com 3 perfis configuráveis por aula:

1. **📱 Gaveta (drawer):** Menu de aulas recolhido que desliza suavemente sobre a tela ao toque do dedo.
2. **📲 Barra Fixa no Rodapé (bottom_bar):** Barra estilo app nativo no rodapé do celular com botões *Anterior*, *Aulas* e *Próxima*.
3. **🎬 Modo Cinema 16:9 (cinema):** O vídeo fica fixado no topo da tela do celular enquanto o aluno rola o conteúdo, apostila e anotações abaixo sem perder o vídeo de vista.

---

## 🛡️ 6. Resumo da Segurança e Testabilidade

- **Zero Superglobais nos Controllers:** Toda requisição passa pelo objeto seguro `Request` e `SessionManagerInterface`.
- **Proteção CSRF Estrita:** Toda alteração de anotação, conclusão de aula ou pagamento exige token CSRF válido (`X-CSRF-Token`).
- **479 Testes Automatizados (100% Verificados):** Todo o ciclo de vida de matrículas, pagamentos, webhooks de afiliados, blocos visuais e armazenamento isolado possui testes unitários contínuos em `tests/Unit/`.
