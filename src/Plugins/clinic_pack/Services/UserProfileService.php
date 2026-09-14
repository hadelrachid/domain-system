<?php

namespace DomainSystem\Plugins\clinic_pack\Services;

use DomainSystem\Plugins\clinic_pack\Contracts\UserProfileServiceInterface;
use DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class UserProfileService implements UserProfileServiceInterface
{
    private UserRepositoryInterface $userRepo;
    private DoctorRepositoryInterface $doctorRepo;
    private string $uploadPath;

    public function __construct(UserRepositoryInterface $userRepo, DoctorRepositoryInterface $doctorRepo)
    {
        $this->userRepo = $userRepo;
        $this->doctorRepo = $doctorRepo;
        $this->uploadPath = dirname(__DIR__, 4) . '/public/uploads';
    }

    public function updateProfile(int $userId, array $data, array $files): array
    {
        $updateData = [];

        // 1. Processamento da Foto de Perfil
        if (isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($files['photo']['name'], PATHINFO_EXTENSION);
            // Validar extensão se necessário
            $filename = 'profile_' . $userId . '_' . time() . '.' . $ext;
            
            if (!is_dir($this->uploadPath)) {
                mkdir($this->uploadPath, 0777, true);
            }
            
            $dest = $this->uploadPath . '/' . $filename;
            if (move_uploaded_file($files['photo']['tmp_name'], $dest)) {
                $updateData['profile_image'] = '/uploads/' . $filename;
            }
        }

        // 2. E-mail
        if (!empty($data['email'])) {
            $updateData['email'] = $data['email'];
        }

        // 3. Senha (Hashing e Validação de Complexidade)
        if (!empty($data['password'])) {
            if (!\DomainSystem\Core\Security\PasswordAnalyzer::isAcceptable($data['password'])) {
                $missing = implode(' ', \DomainSystem\Core\Security\PasswordAnalyzer::getMissingRequirements($data['password']));
                throw new \Exception("A senha fornecida é muito fraca. " . $missing);
            }
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        // 4. 2FA
        if (!empty($data['two_factor_type']) && in_array($data['two_factor_type'], ['none', 'app', 'email'])) {
            $updateData['two_factor_type'] = $data['two_factor_type'];
        }

        // 5. Tema (Tratado via Eventos por plugins externos como visual_themes em JSON)

        // Executar atualização
        if (!empty($updateData)) {
            $this->userRepo->updateProfile($userId, $updateData);
            
            // Espelhamento: se alterou a foto e o usuário for um médico, atualiza a foto no registro do médico
            if (isset($updateData['profile_image'])) {
                $doctor = $this->userRepo->findDoctorByUserId($userId);
                if ($doctor) {
                    $this->doctorRepo->update($doctor['id'], ['photo_url' => $updateData['profile_image']]);
                }
            }
        }

        return ['success' => true, 'message' => 'Configurações atualizadas com sucesso!'];
    }
}
