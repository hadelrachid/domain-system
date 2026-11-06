# 💬 Especificação Arquitetural: Central de Ajuda & Suporte ao Aluno (Helpdesk Integrado)
**Domain-System OS &bull; Academy LMS &bull; v2.6+**

---

## 1. 🎯 Objetivo & Visão Geral

A **Central de Ajuda & Suporte ao Aluno** tem como objetivo fornecer um canal oficial, ágil e estruturado para comunicação direta entre o aluno (ou comprador) e o administrador/dono do curso.

### Principais Problemas Solucionados:
1. **Atritos de Pagamento:** Aluno pagou via PIX ou cartão, mas por oscilação temporária de webhook a matrícula não liberou de imediato.
2. **Problemas de Login e Acesso:** Alunos que esqueceram credenciais ou não conseguem entrar.
3. **Dúvidas de Conteúdo:** Esclarecimento de dúvidas sobre apostilas, módulos e exercícios.
4. **Falhas Técnicas:** Relato de links, vídeos ou PDFs com problema de carregamento.
5. **Reclamações & Atendimento Geral:** Canal formal de ouvidoria para evitar disputas, chargebacks e reclamações em redes sociais ou Reclame Aqui.

---

## 2. 🖥️ Pontos de Entrada do Aluno (UX / Frontend)

```
┌────────────────────────────────────────────────────────────────────────┐
│                        PONTOS DE ACESSO DO ALUNO                      │
├───────────────────────┬────────────────────────┬───────────────────────┤
│ 1. Cockpit do Aluno   │ 2. Telas de Login/Reg  │ 3. Vitrine de Cursos  │
│    (/aluno/painel)    │ (/aluno/login)         │    (/cursos)          │
│                       │                        │                       │
│ Botão "💬 Suporte"   │ Link de contingência:  │ Seção de contato com  │
│ Dados do aluno já     │ "Teve problemas com    │ o instrutor para      │
│ vêm preenchidos       │ seu pagamento/acesso?" │ dúvidas pré-compra    │
└───────────────────────┴────────────────────────┴───────────────────────┘
```

### 2.1. No Cockpit do Aluno (`/aluno/painel`)
- Adição do botão **`💬 Suporte & Ajuda`** no cabeçalho.
- O aluno visualiza seus chamados anteriores e o status de cada um (Pendente, Respondido, Resolvido).
- Formulário rápido de novo ticket com Nome, E-mail e ID já preenchidos da sessão `StudentAuthService`.

### 2.2. Nas Telas Públicas de Login e Cadastro (`/aluno/login` e `/aluno/cadastro`)
- Link de atendimento de emergência no rodapé:
  > *"Problemas com seu pagamento ou com seu acesso? [Fale com nosso Suporte &rarr;]"*
- Permite que um aluno que comprou mas ainda não consegue logar envie o e-mail da compra e o comprovante.

---

## 3. 🏷️ Categorias Inteligentes de Triagem

Ao abrir um chamado, o aluno seleciona a categoria, ativando campos específicos para agilizar a resolução:

| Categoria | Descrição | Campos Específicos Exibidos | Prioridade |
| :--- | :--- | :--- | :---: |
| 💳 **Pagamento Realizado sem Liberação** | Aluno pagou, mas o curso continua bloqueado. | • Código da Transação / Pedido<br>• Gateway utilizado (Pix, Mercado Pago, Hotmart, etc.)<br>• Upload de comprovante | **Alta (Crítica)** |
| 🔑 **Dificuldade de Login / Acesso** | Senha inválida, e-mail não reconhecido. | • E-mail utilizado na compra | **Alta** |
| 🎥 **Problema Técnico na Aula** | Vídeo quebrado, PDF que não baixa. | • Nome do Curso e da Aula | **Média** |
| 📚 **Dúvida sobre o Conteúdo** | Pergunta pedagógica sobre as lições. | • Curso e Módulo | **Normal** |
| 📢 **Reclamação ou Cancelamento** | Pedido de cancelamento, reembolso ou feedback. | • Motivo detalhado | **Alta** |

---

## 4. 🛠️ Painel do Administrador (`/admin/academy/suporte`)

Uma interface completa de gerenciamento dentro do painel do Academy:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 🎓 Academy - Gestão de Suporte & Chamados dos Alunos                   │
├────────────────────────────────────────────────────────────────────────┤
│ Filtros: [ Todos ] [ 🟡 Pendentes (3) ] [ 🔵 Respondidos ] [ 🟢 Resolvidos ]
├──────────┬──────────────────────┬────────────────────────┬─────────────┤
│ Protocolo│ Aluno / E-mail       │ Categoria / Assunto    │ Ação Rápida │
├──────────┼──────────────────────┼────────────────────────┼─────────────┤
│ #TK-8491 │ Maria Oliveira       │ 💳 Pagamento não liberou│ [⚡ Liberar] │
│          │ maria@gmail.com      │ Pix Mercado Pago       │ [Ver Ticket]│
├──────────┼──────────────────────┼────────────────────────┼─────────────┤
│ #TK-8488 │ Carlos Dev           │ 🎥 Erro no vídeo Mód. 2│ [Responder] │
│          │ carlos@dev.com       │ Aula 3 não carrega     │             │
└──────────┴──────────────────────┴────────────────────────┴─────────────┘
```

### Funcionalidade: Liberação com 1 Clique (`⚡ Liberar Matrícula Imediata`)
Quando o chamado for de categoria financeira:
- O painel exibe o e-mail informado e o código do pedido.
- Com um clique no botão **`⚡ Liberar Matrícula Imediata`**, o sistema chama internamente `CourseEnrollmentInterface::enroll()` e libera o curso para o aluno na hora, respondendo o ticket com a confirmação.

---

## 5. 🧱 Arquitetura Baseada em Contratos (SOLID / DIP)

### 5.1. Contratos no Núcleo do Plugin Academy

```php
namespace DomainSystem\Plugins\academy\Contracts;

interface SupportTicketRepositoryInterface
{
    public function createTicket(array $data): int;
    public function findById(int $ticketId): ?array;
    public function findByProtocol(string $protocol): ?array;
    public function listByStudent(int $studentId): array;
    public function listAll(array $filters = []): array;
    public function updateStatus(int $ticketId, string $status): bool;
    public function addReply(int $ticketId, int $authorId, string $authorRole, string $message): int;
    public function getReplies(int $ticketId): array;
}
```

```php
namespace DomainSystem\Plugins\academy\Contracts;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Http\Request;

interface SupportTicketServiceInterface
{
    public function openTicket(Request $request): array;
    public function replyTicket(int $ticketId, Request $request, string $authorRole): bool;
    public function resolveAndEnroll(int $ticketId, int $courseId, int $studentId): bool;
}
```

---

## 6. 🗄️ Modelo de Dados (Tabelas SQL)

### Tabela 1: `academy_support_tickets`
```sql
CREATE TABLE IF NOT EXISTS academy_support_tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    protocol VARCHAR(30) NOT NULL UNIQUE,
    user_id INTEGER NULL,
    student_name VARCHAR(150) NOT NULL,
    student_email VARCHAR(190) NOT NULL,
    category VARCHAR(50) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    course_id INTEGER NULL,
    order_ref VARCHAR(100) NULL,
    attachment_path VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pending', -- pending, in_progress, replied, resolved, closed
    priority VARCHAR(20) NOT NULL DEFAULT 'normal', -- normal, high, urgent
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tabela 2: `academy_support_replies`
```sql
CREATE TABLE IF NOT EXISTS academy_support_replies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    author_id INTEGER NULL,
    author_role VARCHAR(50) NOT NULL, -- admin, manager, student
    author_name VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES academy_support_tickets(id) ON DELETE CASCADE
);
```

---

## 7. 🔒 Requisitos Não-Funcionais & Segurança

1. **Proteção CSRF:** Todo envio de chamado ou resposta exige validação de token CSRF.
2. **Anti-Spam / Rate Limiting:** Limite de abertura de chamados por IP/Sessão (máximo 5 chamados por hora).
3. **Higienização XSS:** Mensagens de chamados e respostas são sanitizadas com `htmlspecialchars` antes de renderizadas em tela.
4. **Isolamento de Visibilidade:** Alunos só podem visualizar tickets emitidos por eles próprios (`user_id` ou e-mail correspondente).
5. **Zero Superglobais:** Controladores utilizam estritamente o objeto `Request`.

---

## 8. 📅 Próximos Passos na Esteira de Implementação

1. [ ] Concluir auditoria e testes do fluxo de pagamento (v2.6.0).
2. [ ] Criar migração das tabelas de suporte no `Plugin::activate()`.
3. [ ] Implementar `SupportTicketRepository` e registrar no container via DIP.
4. [ ] Desenvolver as telas de atendimento no Cockpit e no Painel Admin.
5. [ ] Escrever suíte de testes unitários (`AcademySupportTicketTest.php`).
