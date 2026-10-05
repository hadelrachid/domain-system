# Receita de Desenvolvimento Incremental & TDD

Este roteiro **DEVE** ser seguido rigorosamente pelo Antigravity (ou qualquer engenheiro) durante a evolução do Web OS. É a garantia de que não haverá quebras arquiteturais ou esquecimento de testes.

## Ciclo de Produção

### 1. 📋 Planejamento & Contrato (Design)
- Entender a necessidade de negócio.
- Se for criar uma classe nova, desenhar primeiro a Interface (Contrato).
- Identificar quais componentes (Ring 0 ou Ring 3) serão afetados.

### 2. 🔴 O Teste Vem Primeiro (Red)
- **Antes** (ou imediatamente junto) da codificação principal, escrever o teste unitário ou arquitetural (`tests/Unit/`, `tests/Architecture/`).
- O teste DEVE falhar primeiramente (vermelho). Isso prova que a proteção está ativa e que estamos cobrindo um buraco real.

### 3. 🟢 Implementação (Green)
- Codificar a refatoração ou a feature no core/plugin correspondente.
- Rodar o teste isolado que acabou de ser criado (`php phpunit.phar tests/.../NomeDoTeste.php`).
- Ajustar até a barra ficar **Verde**.

### 4. 🛠️ Limpeza & SOLID (Refactor)
- O código funciona? Ótimo. Agora limpe.
- Existem variáveis soltas? Acoplamento? Classes com dupla responsabilidade (SRP quebrado)? 
- Corrija e mantenha o teste passando.

### 5. 🛡️ Bateria de Regressão
- Rodar a bateria **INTEIRA** de testes (`php phpunit.phar`).
- Garantir que a refatoração atual não causou Efeito Dominó ou disparou o *No-Break Shield* acidentalmente em outros módulos.

### 6. ✍️ Checkpoint & Documentação
- Atualizar `.md` caso regras de negócio mudem.
- Só então pedir autorização para ir ao próximo passo.

> *"Devagar, sem pressa, desde que façamos os testes após a refatoração ou correção."* - Regra de Ouro.
