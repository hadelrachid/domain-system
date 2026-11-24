## 🚀 Domain-System OS v2.8.3 — Arquitetura dos Três Pilares, Atualizador em 1 Clique & Alinhamento Oficial AliExpress

A versão **v2.8.3** representa um marco importante na maturidade do Domain System: organizamos a casa em **três pilares independentes**, adicionamos um sistema de atualização de plugins no painel estilo WordPress e alinhamos a mineração do dropshipping com os manuais oficiais da AliExpress Open Platform.

---

### 🏛️ 1. Arquitetura em Três Pilares (Repositório do Core Ultra Leve)
- **Desacoplamento de Aplicações Pesadas:**  
  O repositório principal (`domain-system`) agora foca exclusivamente no que é vital: o Cockpit OS, a segurança do sistema e extensões leves de demonstração.
- **Repositório de Extensões Dedicado:**  
  Módulos como `DomainDrop`, `DomainCommerce` e `Academy LMS` foram migrados para o repositório oficial [domain-system-plugins](https://github.com/hadelrachid/domain-system-plugins), permitindo que cada um tenha suas próprias versões e evolua sem inchar o núcleo.
- **Repositório de Temas Oficial:**  
  Criação do repositório [domain-system-themes](https://github.com/hadelrachid/domain-system-themes), estreando o tema oficial de e-commerce **RachidD Store** na versão inicial `v1.0.0` com licença MIT.

---

### 🔄 2. Atualizador de Plugins com 1 Clique (Estilo WordPress)
- **Central de Notificações no Sininho:**  
  O ícone de sino no topo do painel avisa automaticamente quando houver novas versões de extensões disponíveis no GitHub.
- **Botão "Atualizar Agora":**  
  Dentro da tela de plugins, você atualiza qualquer extensão com um simples clique. O sistema baixa o arquivo ZIP assinado, confere a impressão digital SHA-256 e instala com segurança.
- **Verificação Manual:**  
  Botão "Verificar Atualizações" para checagem em tempo real das novidades no GitHub.

---

### 📦 3. Alinhamento Oficial do Dropshipping (DomainDrop)
- **Consultas Padronizadas pela API Oficial:**  
  Uso dos métodos recomendados pela AliExpress Open Platform (`dropshiper.pdf` e `categoria-e-atributos.pdf`), eliminando identificadores sintéticos e trabalhando com os IDs e URLs numéricos reais do catálogo.
- **Fim das Imagens Incompatíveis:**  
  Correção nas fotos da vitrine, garantindo que produtos importados exibam suas imagens corretas de alta definição.
- **Fim das Páginas 404:**  
  Garantia de persistência automática ao acessar produtos em vitrines temporárias e redirecionamento amigável para evitar que o visitante caia em links quebrados.

---

### 🧪 4. Suíte de Testes Automatizados
- **760 testes automatizados** e **4.593 asserções** validados no PHPUnit com 100% de sucesso.
- Blindagem com testes no GitHub Actions para garantir estabilidade em qualquer ambiente.

---

## 🚀 Domain-System OS v2.7.4 — Resiliência OAuth2 AliExpress & Sessão Persistente

Esta versão **v2.7.4** resolve a preservação de sessão e o fluxo de redirecionamento no retorno da autorização OAuth 2.0 do AliExpress em ambientes de produção (como Hostinger).

### 📦 Correções & Melhorias no DomainDrop & Core
- **Padronização de Cookie de Sessão com `SameSite=Lax`:**
  Permite que o navegador preserve a sessão do administrador ao ser redirecionado de volta da tela de autorização do AliExpress (`api-sg.aliexpress.com` → painel da loja).
- **Lista de Rotas Seguras nos Middlewares:**
  Inclusão das rotas `/admin/dropship/callback` e `/admin/dropship/oauth/callback` na lista segura, evitando deslogar o usuário durante o retorno do login internacional.
- **Contrato Oficial do Cliente AliExpress (`AliExpressApiClientInterface`):**
  Definição explícita de métodos para geração de link de autorização, criação e renovação de tokens de acesso.

---

## 🚀 Domain-System OS v2.7.3 — OAuth2 AliExpress, Estoque ao Vivo & Blindagem de Segurança

Esta versão **v2.7.3** consolida o fluxo oficial **OAuth 2.0 Server-side** com o AliExpress, verificação de disponibilidade em tempo real e melhorias de segurança nas extensões.

### 📦 Novidades no DomainDrop
- **Conexão Oficial com Conta AliExpress:**
  Botão no painel administrativo para autorizar o aplicativo oficial via OAuth 2.0 com renovação automática de token antes do vencimento.
- **Verificação de Estoque e Frete ao Vivo:**
  Conferência em tempo real dos preços e prazos de entrega antes da confirmação do pedido.
