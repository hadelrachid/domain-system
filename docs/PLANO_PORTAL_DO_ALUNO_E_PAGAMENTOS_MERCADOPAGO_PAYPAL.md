# 🎓 Plano de Arquitetura: Portal do Aluno (Cockpit EAD) & Gateway de Pagamentos (Mercado Pago / PayPal)

> **Status:** 🎯 Alta Prioridade — Planejamento da Versão 2.7.0  
> **Conceito:** CMS Educacional Leve e Auto-Hospedado (Alternativa independente ao WordPress + WooCommerce + LearnDash).  
> **Princípios Arquiteturais:** SOLID, Inversão de Dependência (DIP), Micro-Kernel, Ring 0 / Ring 3 e Arquitetura Orientada a Eventos.  

---

## 🧭 1. Visão Geral e Proposta de Valor

O objetivo deste projeto é consolidar o **Domain System** como uma plataforma completa de **Ensino a Distância (EAD) e Venda de Conteúdo**, permitindo que qualquer professor, mentor ou criador de conteúdo instale o sistema em sua hospedagem (Hostinger, cPanel ou VPS própria) e tenha:

1. **Sua Sala de Aula Virtual Independente:** Sem depender de taxas abusivas de plataformas terceiras nem do peso monumental do WordPress.
2. **Espaço Individual do Aluno (Cockpit do Aluno):** Cada aluno tem seu login, preferências, histórico de aulas concluídas e uma pasta de arquivos/anotações isolada (como uma conta de usuário do Windows).
3. **Liquidação Financeira Automática com Mercado Pago e PayPal:** O aluno compra o curso por Pix, Cartão ou Boleto, e no mesmo segundo a matrícula é ativada pelo Webhook sem nenhuma intervenção manual.
4. **Desacoplamento Total via Contratos (DIP):** O plugin **Academy** não conhece os detalhes técnicos dos bancos; ele apenas escuta os eventos disparados pelo motor financeiro.

---

## 🏛️ 2. Arquitetura em 3 Pilares

```
                                  ┌───────────────────────────────┐
                                  │      ALUNO / VISITANTE        │
                                  └───────────────┬───────────────┘
                                                  │
                         ┌────────────────────────┴────────────────────────┐
                         ▼                                                 ▼
        ┌─────────────────────────────────┐               ┌─────────────────────────────────┐
        │       COCKPIT DO ALUNO          │               │      CHECKOUT & PAGAMENTOS      │
        │  • Meus Cursos & Progresso      │               │  • Pix (QR Code / Copia e Cola) │
        │  • Pasta Pessoal de Anotações   │               │  • Cartão Parcelado (até 12x)   │
        │  • Certificados e Apostilas     │               │  • Boleto Bancário              │
        │  • Preferências de Perfil       │               │  • PayPal (Internacional)       │
        └────────────────┬────────────────┘               └────────────────┬────────────────┘
                         │                                                 │
                         │                                                 │ Webhook Confirmado
                         ▼                                                 ▼
        ┌───────────────────────────────────────────────────────────────────────────────────┐
        │                           KERNEL EVENT DISPATCHER                                 │
        │                      Evento: 'commerce.payment_approved'                          │
        └────────────────────────────────────────┬──────────────────────────────────────────┘
                                                 │
                                                 ▼
        ┌───────────────────────────────────────────────────────────────────────────────────┐
        │                              PLUGIN ACADEMY (LMS)                                 │
        │  • CourseEnrollmentService: Libera acesso imediato ao Curso ou Pacote             │
        │  • LessonBlockRenderer: Exibe vídeos, códigos, textos e PDFs sob demanda          │
        └───────────────────────────────────────────────────────────────────────────────────┘
```

---

## 👨‍🎓 Pilar 1: O Cockpit do Aluno (Student Workspace)

Assim como um sistema operacional isola os arquivos de cada usuário em pastas próprias, o Domain System oferecerá um **Workspace dedicado para o perfil `student`**:

### Funcionalidades do Espaço do Aluno:
1. **Autenticação & Cadastro (`/aluno/login` e `/aluno/cadastro`):**
   - Criação de conta simplificada durante o checkout ou acesso direto.
   - Proteção de sessão nativa via `SessionManagerInterface` com hash timing-safe.
2. **Dashboard do Aluno (`/aluno/painel`):**
   - Grade visual com os cursos matriculados.
   - Barra percentual de conclusão por curso (`X% concluído`).
   - Botão *"Continuar de onde parei"* que direciona para a última aula assistida.
3. **Pasta Pessoal e Preferências do Aluno:**
   - Armazenamento isolado (`storage/students/{aluno_id}/`): o aluno pode salvar resumos, anotações e arquivos de exercícios.
   - Histórico de emissão e download de certificados em PDF.
   - Preferências visuais (Dark/Light mode, volume do player, preferências de notificação).

---

## 🎓 Pilar 2: Motor de Matrículas e Restrição de Acesso (Academy Hub)

O plugin **Academy** mantém a responsabilidade única (**SRP**) sobre a estrutura pedagógica, delegando a cobrança para o módulo financeiro.

### Contratos a serem criados em `src/Plugins/academy/Contracts/`:
- `StudentRepositoryInterface`: Gerencia a persistência dos dados e progresso do aluno.
- `CourseEnrollmentInterface`: Registra, valida e cancela matrículas de cursos.

### Regra de Proteção de Aulas:
```php
interface CourseEnrollmentInterface
{
    /**
     * Verifica se o aluno possui acesso ativo ao curso especificado.
     */
    public function hasActiveEnrollment(int $studentId, int $courseId): bool;

    /**
     * Efetiva a matrícula do aluno vinculada a um pedido financeiro.
     */
    public function enroll(int $studentId, int $courseId, ?DateTimeImmutable $expiresAt = null): void;

    /**
     * Suspende ou cancela a matrícula (ex: estorno ou cancelamento de assinatura).
     */
    public function revoke(int $studentId, int $courseId): void;
}
```

- **Aulas de Degustação (Públicas):** Acessíveis livremente para atrair novos alunos (amostra grátis).
- **Aulas Fechadas (Matriculados):** O leitor de blocos só carrega os vídeos e o leitor de PDF se `hasActiveEnrollment()` retornar `true`.

---

## 💳 Pilar 3: Camada de Pagamentos Desacoplada (Mercado Pago & PayPal)

Para garantir o princípio de Inversão de Dependência (**DIP**), o sistema adota um contrato universal de gateway:

```php
namespace DomainSystem\Plugins\commerce\Contracts;

interface PaymentGatewayInterface
{
    public function getId(): string; // 'mercadopago' | 'paypal'
    public function getTitle(): string;
    
    /**
     * Cria a cobrança (Pix, Cartão ou Boleto) e retorna os dados de liquidação.
     */
    public function createPayment(OrderInterface $order, array $paymentData): PaymentResult;

    /**
     * Processa a notificação assíncrona (Webhook) enviada pelo banco.
     */
    public function handleWebhook(RequestInterface $request): WebhookResult;
}
```

### 1. Integração Mercado Pago (Mercado Livre) 🇧🇷
O gateway principal para o público brasileiro:
- **Pix Instantâneo:**
  - Gera o QR Code visual (imagem) e o código *Pix Copia-e-Cola*.
  - O webhook do Mercado Pago notifica o servidor no momento do pagamento e libera a aula em menos de 5 segundos.
- **Cartão de Crédito:**
  - Suporte a parcelamento em até 12x.
  - Tokenização de cartão no front-end (PCI Compliance): nenhum número de cartão trafega de forma insegura no servidor do professor.
- **Boleto Bancário:**
  - Geração automática com código de barras digitável e botão para download do PDF.
- **Configuração no Painel:** Apenas duas chaves simples:
  - `MERCADOPAGO_PUBLIC_KEY`
  - `MERCADOPAGO_ACCESS_TOKEN`

### 2. Integração PayPal 🌎
O gateway internacional para vendas no exterior:
- Processa pagamentos em Dólares (USD), Euros (EUR) e Reais (BRL).
- Aceita Cartões de Crédito internacionais e Saldo da Conta PayPal.
- **Configuração no Painel:**
  - `PAYPAL_CLIENT_ID`
  - `PAYPAL_CLIENT_SECRET`

---

## 📦 4. Modelos de Monetização Suportados

1. **Pagamento Único (Compra Avulsa por Curso):**
   - O aluno compra o "Curso de Python" e a matrícula é gerada sem data de expiração (`expires_at = null`).
2. **Pacote de Cursos (Bundles):**
   - O professor vende um pacote promocional (ex: *Formação Full-Stack* com 3 cursos inclusos). O evento ativa a matrícula nos 3 cursos de uma única vez.
3. **Assinatura Recorrente (Clube de Membros / Mensalidade):**
   - O aluno paga uma mensalidade (ex: R$ 49,90/mês).
   - O webhook estende a data de validade da matrícula a cada mês. Caso ocorra cancelamento ou inadimplência, o sistema desativa o acesso mantendo o progresso e anotações do aluno salvos para quando ele reativar.

---

## 🗓️ 5. Fases de Execução

| Fase | Foco Principal | Entregáveis |
| :--- | :--- | :--- |
| **Fase 1** | **Base de Usuários & Cockpit do Aluno** | Perfil `student`, tela de login/cadastro do aluno, painel do aluno e pasta pessoal de anotações. |
| **Fase 2** | **Contratos de Matrícula (Academy)** | `CourseEnrollmentInterface`, tabelas de matrículas (`academy_enrollments`), verificação de acesso nas aulas. |
| **Fase 3** | **Plugin Commerce & Gateway Mercado Pago** | Contrato `PaymentGatewayInterface`, checkout com Pix (QR Code/Copia-e-Cola), Cartão até 12x e Webhooks. |
| **Fase 4** | **Gateway PayPal & Vendas Internacionais** | Driver do PayPal para pagamentos em moeda estrangeira e cartão internacional. |
| **Fase 5** | **Blocos Visuais no Flex Builder FSE** | Blocos de "Comprar Curso", "Botão de Matrícula", "Preço/Parcelamento" arrastáveis no tema. |
