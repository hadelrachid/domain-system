<?php

namespace DomainSystem\Plugins\clinic_pack\Contracts;

interface UserProfileServiceInterface
{
    /**
     * Atualiza o perfil do usuário, processando fotos, senhas e preferências.
     * 
     * @param int $userId ID do usuário.
     * @param array $data Dados vindos do formulário (email, password, two_factor_type, theme_color).
     * @param array $files Dados vindos de $_FILES (como a photo).
     * @return array Resultado da operação ['success' => bool, 'message' => string]
     */
    public function updateProfile(int $userId, array $data, array $files): array;
}
