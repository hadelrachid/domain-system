# Plugin: Rádio Web Station (radio_online)

Este plugin adiciona um Widget interativo ao Dashboard (OS Workspace) que permite aos administradores sintonizarem rádios online enquanto trabalham no sistema.

## 📻 Funcionalidades
- **Estações Pré-definidas:** Traz rádios mundiais de alta qualidade embutidas.
- **Rádios Personalizadas:** O usuário pode adicionar o link da sua rádio favorita.
- **Memória Local:** Rádios adicionadas são salvas automaticamente no navegador do usuário (`localStorage`).
- **Exclusão:** Possui um botão inteligente que permite remover as rádios salvas da memória.
- **Display Interativo:** Simula um painel de rádio real informando o status da conexão.

## 🔗 Como adicionar uma Rádio Personalizada
No widget do painel, selecione a opção **"-- Digitar URL Customizada --"** na lista suspensa e cole o link direto de transmissão da rádio no campo que vai aparecer. Em seguida, clique no botão azul **(+)** para salvar.

### Quais tipos de links funcionam?
O player utiliza a API nativa de áudio HTML5 do navegador. Ele **não** toca links de sites normais (ex: link para a página do YouTube ou página do site da rádio). Ele precisa do **link direto do servidor de streaming**.

**Tipos de streams suportados:**
- Links de servidores **Icecast** ou **Shoutcast**.
- Links diretos terminados em `.mp3`, `.aac`, `.ogg`.
- Fluxos HLS terminados em `.m3u8` (em navegadores compatíveis).

### ⚠️ Regra de Ouro (CORS e HTTPS)
1. **HTTPS é obrigatório:** Se o seu sistema OS está rodando em HTTPS (seguro), o navegador **bloqueará** rádios que usem links HTTP (inseguros). O link da rádio *precisa* começar com `https://`.
2. **CORS (Cross-Origin):** Alguns servidores de rádio bloqueiam a reprodução quando embutidos em outros sites. Se o link for válido mas aparecer "ERRO DE SINAL (VERIFIQUE CORS OU URL)", significa que o dono da rádio configurou o servidor para não permitir que players externos toquem o áudio deles.

## 🛠️ Exemplo de Links Válidos
- `https://stream.live.vc.bbcmedia.co.uk/bbc_world_service` (Stream direto sem extensão)
- `https://icecast.vrtcdn.be/stubru-high.mp3` (Icecast terminando em .mp3)
- `https://jazz.streamr.ru/jazz-128.mp3` (Shoutcast MP3)

## 🔐 Permissões (ACL)
Para visualizar e usar o widget, o usuário precisa ter a permissão (capability):
`radio.listen`

Esta permissão é registrada automaticamente ao ativar o plugin.
