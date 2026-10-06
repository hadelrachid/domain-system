<?php

namespace DomainSystem\Core\Security;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: PasswordAnalyzer (O Analisador de Força de Senhas)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * Esta classe faz parte do subsistema de Segurança (Security) do DomainSystem
 * e é responsável por avaliar a robustez de senhas fornecidas pelos usuários.
 *
 * No projeto, ela é consumida principalmente por:
 *   - `PasswordPolicy` (que implementa `PasswordPolicyInterface`).
 *   - `UserController` (em `SystemApps/auth`), ao criar ou redefinir senhas.
 *   - O `password-meter.js` no frontend, que espelha a mesma lógica.
 *
 * PADRÃO DE PROJETO: Utility Class / Stateless Service
 * PRINCÍPIO SOLID:   SRP (Single Responsibility) — apenas analisa senhas.
 *
 * NOTA IMPORTANTE:
 * Todos os métodos são estáticos, pois a classe não mantém estado. Ela apenas
 * aplica regras de validação e heurísticas, o que é uma decisão de design
 * consciente para simplificar o uso em qualquer ponto do sistema.
 */
class PasswordAnalyzer
{
    /**
     * Avalia a força de uma senha e retorna uma pontuação de 0 a 100.
     *
     * A pontuação é dividida em 4 critérios, cada um valendo 25 pontos:
     *   1. Comprimento mínimo de 8 caracteres.
     *   2. Presença de letras maiúsculas (A-Z).
     *   3. Presença de letras minúsculas (a-z).
     *   4. Presença de números E caracteres especiais (o último é um "bônus").
     *
     * Isso permite uma escala clara:
     *   - 0     → Muito fraca (não atende nenhum critério).
     *   - 25    → Fraca.
     *   - 50    → Aceitável (limiar mínimo de `isAcceptable`).
     *   - 75    → Boa.
     *   - 100   → Excelente (atende todos os critérios).
     *
     * @param string $password A senha a ser analisada.
     * @return int A pontuação de força (0 a 100).
     */
    public static function getStrengthScore(string $password): int
    {
        // Inicia a pontuação em 0.
        $score = 0;

        // Critério 1: Comprimento mínimo de 8 caracteres (+25).
        if (strlen($password) >= 8) $score += 25;

        // Critério 2: Deve conter pelo menos uma letra maiúscula (+25).
        if (preg_match('/[A-Z]/', $password)) $score += 25;

        // Critério 3: Deve conter pelo menos uma letra minúscula (+25).
        if (preg_match('/[a-z]/', $password)) $score += 25;

        // Critério 4: Deve conter pelo menos um número E um caractere especial (+25).
        // Este é o critério mais "rígido", pois exige a combinação dos dois.
        if (preg_match('/[0-9]/', $password) && preg_match('/[^a-zA-Z0-9]/', $password)) $score += 25;

        return $score;
    }

    /**
     * Retorna uma lista de requisitos que a senha ainda não cumpre.
     *
     * Diferente de `getStrengthScore()`, que retorna um número, este método
     * retorna uma lista textual de "o que está faltando". Isso é ideal para
     * fornecer feedback direto ao usuário (ex: "Falta: Letra maiúscula, Número").
     *
     * A lógica de validação é idêntica à do `getStrengthScore()`, mas invertida:
     * cada `if` que avalia um critério que falhou adiciona uma mensagem ao array.
     *
     * @param string $password A senha a ser verificada.
     * @return array Lista de strings descrevendo os requisitos faltantes.
     */
    public static function getMissingRequirements(string $password): array
    {
        // Array que acumulará as mensagens de requisitos faltantes.
        $missing = [];

        // Verifica o comprimento mínimo (8 caracteres).
        if (strlen($password) < 8) {
            $missing[] = 'A senha deve ter no mínimo 8 caracteres.';
        }

        // Verifica a presença de letras maiúsculas.
        if (!preg_match('/[A-Z]/', $password)) {
            $missing[] = 'A senha deve conter letras maiúsculas.';
        }

        // Verifica a presença de letras minúsculas.
        if (!preg_match('/[a-z]/', $password)) {
            $missing[] = 'A senha deve conter letras minúsculas.';
        }

        // Verifica a presença de números.
        if (!preg_match('/[0-9]/', $password)) {
            $missing[] = 'A senha deve conter números.';
        }

        // Verifica a presença de caracteres especiais (não alfanuméricos).
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $missing[] = 'A senha deve conter caracteres especiais (ex: @, #, $, %).';
        }

        return $missing;
    }

    /**
     * Verifica se a senha atinge um nível de força considerado aceitável.
     *
     * O limiar foi definido como "estritamente maior que 50".
     *
     * Isso significa que uma senha precisa cumprir pelo menos 3 dos 4 critérios
     * de `getStrengthScore()` para ser aceita. Uma senha que atenda apenas
     * 2 critérios (score 50) será REJEITADA.
     *
     * Este método é chamado pelo `UserController` em `store()` e `resetPassword()`
     * para impedir que senhas fracas sejam cadastradas no sistema.
     *
     * @param string $password A senha a ser avaliada.
     * @return bool True se a senha for aceitável, False caso contrário.
     */
    public static function isAcceptable(string $password): bool
    {
        // O limiar é 50 (exclusivo). Portanto, senhas com score 0, 25 ou 50
        // são consideradas inaceitáveis. Apenas 75 ou 100 passam.
        return self::getStrengthScore($password) > 50;
    }

    /**
     * Gera uma senha forte e aleatória que atende a TODOS os critérios (score 100).
     *
     * Utilizado principalmente para:
     *   - Sugerir uma senha forte ao administrador ao criar um novo usuário.
     *   - Preencher automaticamente campos de senha no painel.
     *   - Servir de base para futuros recursos de "reset de senha por e-mail".
     *
     * Estratégia de geração:
     *   1. Garante que a senha contenha pelo menos 1 caractere de cada categoria
     *      (maiúscula, minúscula, número, símbolo). Isso garante que a senha
     *      atinja 100 pontos em `getStrengthScore()`.
     *   2. Preenche o restante do comprimento com caracteres aleatórios de TODAS
     *      as categorias combinadas.
     *   3. Embaralha o resultado final para que a posição dos primeiros caracteres
     *      (que foram inseridos em ordem previsível: maiúscula → minúscula → número → símbolo)
     *      não seja determinística.
     *
     * SEGURANÇA:
     * Usa `random_int()`, que é a função criptograficamente segura do PHP
     * (ao contrário de `rand()` ou `mt_rand()`), tornando a senha imprevisível.
     *
     * @param int $length O comprimento total da senha (padrão: 12).
     * @return string A senha forte gerada.
     */
    public static function generateStrongPassword(int $length = 12): string
    {
        // Define os caracteres permitidos em cada categoria.
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers   = '0123456789';
        $symbols   = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        // Concatena todas as categorias em uma única string (usada para os caracteres extras).
        $all = $uppercase . $lowercase . $numbers . $symbols;

        // Inicia a senha com uma string vazia.
        $password = '';

        // -----------------------------------------------------------------
        // PASSO 1: Garante ao menos um caractere de cada categoria
        // -----------------------------------------------------------------
        // Isso garante que a senha atinja o score máximo de 100,
        // pois todos os 4 critérios serão cumpridos.
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // -----------------------------------------------------------------
        // PASSO 2: Preenche o restante do comprimento com caracteres aleatórios
        // -----------------------------------------------------------------
        // Começa do índice 4 (já temos 4 caracteres garantidos acima).
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        // -----------------------------------------------------------------
        // PASSO 3: Embaralha a senha para não seguir o padrão previsível
        // -----------------------------------------------------------------
        // Sem isso, a senha sempre começaria com uma maiúscula, seguida de
        // uma minúscula, um número e um símbolo — um padrão explorável.
        // `str_shuffle()` usa o gerador de números aleatórios interno do PHP.
        return str_shuffle($password);
    }
}
