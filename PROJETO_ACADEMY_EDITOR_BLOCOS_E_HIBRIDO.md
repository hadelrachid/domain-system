# 🎓 PROJETO ACADEMY: EDITOR EM BLOCOS & ARQUITETURA HÍBRIDA DE AULAS

> **Status:** 🔴 **ALTA PRIORIDADE DE CRIAÇÃO**  
> **Data Agendada para Execução:** Domingo  
> **Versão:** 1.0.0  
> **Arquitetura Base:** SOLID / Micro-Kernel Domain-System  
> **Branch / Ambiente:** `d:\xampp\htdocs\domain-system`  

---

## 1. Visão Geral e Objetivo do Projeto

O objetivo deste projeto é equipar o plugin **Academy (Cursos)** com um ecossistema completo e moderno de autoria e gerenciamento de aulas, combinando:

1. **Editor em Blocos (Estilo Gutenberg / Notion / Editor.js)**:
   - Experiência visual rica no painel admin para criação de aulas com vídeos embutidos, blocos de código com destaque de sintaxe, avisos didáticos e formatação semântica.
   - Dados salvos em **JSON puro e estruturado** na coluna `academy_lessons.content`.
   - Garantia de que vídeos nunca vazem (16:9 responsivo automático) e proteção nativa contra quebras de layout ou injeção XSS.

2. **Modelo Híbrido Flexível**:
   - **Modo Editor de Blocos:** Conteúdo dinâmico em banco (`source_type = 'content'`).
   - **Modo Arquivo de Tema:** Inclusão direta de arquivos `.php` do tema (`source_type = 'theme_file'`), como os já existentes em `themes/rachidd/pages/tutoriais/`.
   - **Auto-Importação & Sincronização por Pasta:** Um botão no Módulo (`[ 📂 Importar Pasta do Tema ]`) que escaneia uma subpasta (ex: `pages/tutoriais/`) e cria automaticamente todas as aulas ordenadas pelo prefixo numérico (`01-instalacao.php` $\rightarrow$ "Instalação").

---

## 2. Especificação Técnica dos Blocos

Cada aula no Modo Blocos é serializada como um documento JSON contendo uma lista ordenada de blocos:

```json
{
  "time": 1729100000,
  "blocks": [
    {
      "id": "b_1",
      "type": "header",
      "data": { "level": 2, "text": "Introdução ao Kernel" }
    },
    {
      "id": "b_2",
      "type": "video",
      "data": { "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ", "caption": "Visão geral da arquitetura" }
    },
    {
      "id": "b_3",
      "type": "paragraph",
      "data": { "text": "Nesta aula vamos compreender o ciclo de vida..." }
    },
    {
      "id": "b_4",
      "type": "code",
      "data": { "language": "php", "code": "<?php\n\n$container = new Container();" }
    },
    {
      "id": "b_5",
      "type": "callout",
      "data": { "type": "success", "text": "Princípio da Responsabilidade Única (SRP) aplicado com sucesso." }
    }
  ]
}
```

### Catálogo de Blocos Nativos:

| Bloco | Tipo | Propriedades Principais | Renderização no Front-End |
| :--- | :--- | :--- | :--- |
| **📌 Título** | `header` | `level` (1, 2, 3), `text` | `<h2 class="ds-block-heading">...</h2>` com visual Cyberpunk. |
| **📝 Parágrafo** | `paragraph` | `text` (permite formatação inline) | `<p class="ds-block-paragraph">...` higienizado (anti-XSS). |
| **🎬 Vídeo 16:9** | `video` | `url`, `caption` | Container responsivo 16:9 para YouTube, Vimeo ou tag `<video>` MP4. |
| **💻 Código** | `code` | `language` (php, js, sql, html, css, json, bash), `code` | Caixa dark com topo identificador e botão interativo **"Copiar"**. |
| **💡 Dica / Alerta** | `callout` | `type` (info, success, warning, danger), `text` | Card com borda lateral neon temática e ícone proporcional. |
| **📋 Lista** | `list` | `style` (unordered, ordered), `items` | `<ul>` ou `<ol>` com espaçamento e tipografia legível. |
| **🖼️ Imagem** | `image` | `url`, `caption` | `<figure>` centralizado com sombra suave e legenda. |
| **➖ Divisor** | `delimiter` | *(vazio)* | Linha sutil com gradiente neon separando tópicos. |

---

## 3. Arquitetura de Classes e Componentes (SOLID)

```mermaid
flowchart TD
    AdminUser["Admin / Instrutor"] -->|Clica em Editor de Blocos| LessonAdminController["LessonAdminController\n(GET /admin/academy/lessons/{id}/editor)"]
    AdminUser -->|Importar Pasta| CourseAdminController["CourseAdminController\n(Importar pages/tutoriais)"]
    
    LessonAdminController -->|Salva JSON Blocos| StructureRepo["CourseStructureRepositoryInterface\n(academy_lessons.content)"]
    CourseAdminController -->|Cria Aulas em Lote| StructureRepo
    
    StudentUser["Aluno / Visitante"] -->|Acessa /cursos/{c}/{l}| CourseFrontController["CourseFrontController\n(showLesson)"]
    
    CourseFrontController -->|Se source_type = content| LessonBlockRenderer["LessonBlockRenderer\n(render JSON -> HTML)"]
    CourseFrontController -->|Se source_type = theme_file| SafeInclude["renderThemeFile()\n(include pages/tutoriais/aula.php)"]
```

### Componentes Desenvolvidos:

1. **[`LessonBlockRenderer.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Services/LessonBlockRenderer.php)** *(Pronto)*:
   - Serviço sem estado (Single Responsibility Principle) responsável por converter arrays/JSON de blocos em HTML responsivo e semântico.
   - Suporta conversão inteligente de URLs do YouTube/Vimeo em embeds 16:9.
   - Higieniza tags permitidas via `sanitizeInlineHtml`.

2. **[`LessonAdminController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/LessonAdminController.php)** *(Pronto)*:
   - Controla a tela do editor de blocos com barra superior de ações (Salvar, Ver no Site, Status, Título).
   - Suporta atalho `Ctrl+S` / `Cmd+S` e salvamento assíncrono via `fetch` com notificações toast nativas.

3. **[`CourseAdminController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseAdminController.php)** *(Concluído)*:
   - No editor de estrutura:
     - Adicionado o botão `[ ⚡ Editor de Blocos ]` em cada aula e seletor híbrido (`content` vs `theme_file`).
     - Adicionado o botão `[ 📂 Importar Pasta do Tema ]` no cabeçalho de cada módulo com prévia interativa.

4. **[`CourseFrontController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseFrontController.php)** *(Concluído)*:
   - Injetado `LessonBlockRenderer` no método `showLesson` e extração de texto puro para SEO.
   - Se a aula tiver conteúdo em blocos, renderiza via `LessonBlockRenderer`.

5. **[`Plugin.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Plugin.php)** *(Concluído)*:
   - Registradas as novas rotas administrativas:
     - `GET /admin/academy/lessons/{id}/editor`
     - `POST /admin/academy/lessons/{id}/update-content`

---

## 4. Roteiro de Execução (100% Concluído)

### 📌 Etapa 1: Registro das Rotas e Injeção de Dependências
- [x] No arquivo [`Plugin.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Plugin.php):
  - Adicionado singleton para `LessonBlockRenderer`.
  - Registradas rotas `/admin/academy/lessons/{id}/editor` e `/admin/academy/lessons/{id}/update-content` apontando para `LessonAdminController`.

### 📌 Etapa 2: Atualização do Gerenciador de Estrutura do Curso
- [x] Em [`CourseAdminController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseAdminController.php):
  - Inserido botão de acesso rápido `[ ⚡ Editor de Blocos ]` e seletor de Modo/Status na listagem de aulas de cada módulo.
  - Implementado modal `[ 📂 Importar Pasta do Tema ]` que detecta automaticamente as subpastas existentes em `pages/` (ex: `pages/tutoriais/`, `pages/architecture/`).
  - Implementado script para ler os arquivos da pasta selecionada e popular as aulas automaticamente no módulo com títulos e slugs formatados.

### 📌 Etapa 3: Integração do Front-End (Aluno)
- [x] Em [`CourseFrontController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseFrontController.php):
  - Injetado `LessonBlockRenderer` no construtor com default `null` (mantendo compatibilidade total com testes legados).
  - Em `showLesson()`, renderização automática de aulas em blocos via `LessonBlockRenderer` e extração limpa de texto para as meta tags de SEO.

### 📌 Etapa 4: Suíte de Testes Automatizados
- [x] Criado [`tests/Unit/AcademyBlockEditorTest.php`](file:///d:/xampp/htdocs/domain-system/tests/Unit/AcademyBlockEditorTest.php):
  - Testada a renderização de todos os 8 blocos (`header`, `paragraph`, `video`, `code`, `callout`, `list`, `image`, `delimiter`).
  - Testada a proteção anti-XSS contra injeção de `<script>`, `on*=` e `javascript:`.
  - Testada a conversão de URLs do YouTube/Vimeo para embed 16:9 e vídeo MP4 direto.
  - Testado o salvamento e atualização via `LessonAdminController`.
  - Testado o comportamento híbrido no front-end e no painel admin.

### 📌 Etapa 5: Validação e Empacotamento
- [x] Suíte completa executada e aprovada (`179 tests, 704 assertions — OK`):
  ```bash
  php -dextension=zip phpunit.phar
  ```

---

## 5. Arquivos de Referência e Código Pronto

- **Serviço de Renderização de Blocos:** [`LessonBlockRenderer.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Services/LessonBlockRenderer.php)
- **Controller do Editor Visual:** [`LessonAdminController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/LessonAdminController.php)
- **Repositório de Estrutura:** [`PdoCourseStructureRepository.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Repositories/PdoCourseStructureRepository.php)
- **Controller Admin Atual:** [`CourseAdminController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseAdminController.php)
- **Controller Público do Curso:** [`CourseFrontController.php`](file:///d:/xampp/htdocs/domain-system/src/Plugins/academy/Controllers/CourseFrontController.php)
- **Exemplo de Aulas em Arquivos Prontos:** [`themes/rachidd/pages/tutoriais/`](file:///d:/xampp/htdocs/domain-system/src/Plugins/flextheme/themes/rachidd/pages/tutoriais/)
