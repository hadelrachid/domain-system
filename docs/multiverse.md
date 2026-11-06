# 🎨 Sistema de Múltiplos Temas e Contextos Visuais

No **Domain System**, a arquitetura visual foi projetada para suportar múltiplos contextos simultâneos sem conflitos.

Diferente de CMSs tradicionais onde apenas um único tema precisa controlar tanto o site público quanto áreas internas de forma engessada, nossa plataforma permite que cada área do sistema utilize o layout mais adequado para sua finalidade:

---

## 🌍 Como os Contextos Visuais Funcionam

Na mesma instalação do sistema, compartilhando a mesma base de dados e infraestrutura, diferentes temas operam de forma isolada:

1. **Site Público (Frontend):** O tema público ativo (criado e editável pelo Flex Builder) focado em velocidade, SEO e conversão para visitantes.
2. **Portal do Aluno (Academy):** Um layout exclusivo para a área logada do estudante (`/aluno/painel`), pensado para estudo, visualização de vídeos, leitura de PDFs e navegação entre aulas sem distrações externas.
3. **Painel Administrativo (`/admin`):** Um tema próprio com navegação lateral, métricas, atalhos e foco em produtividade para o administrador gerenciar cursos, pedidos e configurações.

---

## 💡 Vantagens dessa Abordagem

- **Zero Conflito de CSS e JS:** O CSS do painel administrativo ou do portal do aluno nunca vaza para o site público, e as estilizações do site institucional não afetam o leitor de aulas.
- **Liberdade de Customização:** Cada plugin pode fornecer templates dedicados para suas rotas ou herdar a estrutura padrão do tema ativo.
- **Facilidade de Manutenção:** Alterar o layout da página inicial ou trocar o tema do site não quebra a área do aluno nem as páginas administrativas.
