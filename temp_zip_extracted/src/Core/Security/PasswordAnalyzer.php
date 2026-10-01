<?php

namespace DomainSystem\Core\Security;

class PasswordAnalyzer
{
    /**
     * Retorna a pontuação de força da senha (0 a 100)
     */
    public static function getStrengthScore(string $password): int
    {
        $score = 0;
        
        if (strlen($password) >= 8) $score += 25;
        if (preg_match('/[A-Z]/', $password)) $score += 25;
        if (preg_match('/[a-z]/', $password)) $score += 25;
        if (preg_match('/[0-9]/', $password) && preg_match('/[^a-zA-Z0-9]/', $password)) $score += 25;
        
        return $score;
    }

    /**
     * Retorna uma lista de requisitos faltantes para a senha atingir o máximo
     */
    public static function getMissingRequirements(string $password): array
    {
        $missing = [];
        
        if (strlen($password) < 8) {
            $missing[] = 'A senha deve ter no mínimo 8 caracteres.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $missing[] = 'A senha deve conter letras maiúsculas.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $missing[] = 'A senha deve conter letras minúsculas.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $missing[] = 'A senha deve conter números.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $missing[] = 'A senha deve conter caracteres especiais (ex: @, #, $, %).';
        }
        
        return $missing;
    }

    /**
     * Valida se a senha tem força aceitável (maior que 50)
     */
    public static function isAcceptable(string $password): bool
    {
        return self::getStrengthScore($password) > 50;
    }

    /**
     * Gera uma senha aleatória que satisfaz todos os critérios (score 100)
     */
    public static function generateStrongPassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $all = $uppercase . $lowercase . $numbers . $symbols;
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        for ($i = 4; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }
        
        // Embaralha para não seguir o padrão previsível dos 4 primeiros
        return str_shuffle($password);
    }
}
