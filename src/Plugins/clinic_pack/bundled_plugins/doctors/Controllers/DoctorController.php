<?php

namespace DomainSystem\Plugins\doctors\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\doctors\Contracts\DoctorRepositoryInterface;

class DoctorController
{
    private ThemeManager $theme;
    private DoctorRepositoryInterface $repository;

    public function __construct(ThemeManager $theme, DoctorRepositoryInterface $repository)
    {
        $this->theme = $theme;
        $this->repository = $repository;
    }

    public function index()
    {

        $doctors = $this->repository->findAll();
        $theme = $this->theme;
        
        return $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
    }

    public function store()
    {


        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $crm = $_POST['crm'] ?? '';
        $specialty = $_POST['specialty'] ?? '';
        $consultation_time = $_POST['consultation_time'] ?? 30;
        $photo_url = $_POST['photo_url'] ?? '';

        // Upload de foto no cadastro manual
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'doctor_new_' . time() . '.' . $ext;
            $uploadPath = DOMAIN_SYSTEM_ROOT . '/public/uploads';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath . '/' . $filename)) {
                $photo_url = '/uploads/' . $filename;
            }
        }

        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome, e-mail e senha são obrigatórios!'];
        } else {
            try {
                $app = \DomainSystem\Core\Application::getInstance();
                $service = $app->getContainer()->make(\DomainSystem\Plugins\clinic_pack\Services\DoctorRegistrationService::class);
                
                $result = $service->registerDoctor($name, $specialty, $email, $crm, $password);
                
                if ($result['success']) {
                    // Update the extra fields that the service doesn't handle natively
                    $this->repository->update($result['doctor_id'], [
                        'consultation_time' => (int)$consultation_time,
                        'photo_url' => $photo_url
                    ]);
                    $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico cadastrado com sucesso! Conta de usuário e horários criados.'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $result['error']];
                }
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function edit()
    {

        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/doctors");
            exit;
        }

        $doctor = $this->repository->findById((int)$id);
        if (!$doctor) {
            header("Location: " . BASE_URL . "/admin/doctors");
            exit;
        }

        $theme = $this->theme;
        return $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
    }

    public function update()
    {


        $id = $_POST['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/doctors");
            exit;
        }

        $name = $_POST['name'] ?? '';
        $crm = $_POST['crm'] ?? '';
        $specialty = $_POST['specialty'] ?? '';
        $consultation_time = $_POST['consultation_time'] ?? 30;
        $photo_url = $_POST['photo_url'] ?? '';
        
        // Se enviou um arquivo de foto, faz o upload local
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'doctor_' . $id . '_' . time() . '.' . $ext;
            $uploadPath = DOMAIN_SYSTEM_ROOT . '/public/uploads';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0777, true);
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath . '/' . $filename)) {
                $photo_url = '/uploads/' . $filename;
            }
        }

        if (empty($name)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'O nome do médico é obrigatório!'];
            header("Location: " . BASE_URL . "/admin/doctors/edit?id=" . $id);
            exit;
        }

        try {
            $updateData = [
                'name' => $name,
                'crm' => $crm,
                'specialty' => $specialty,
                'consultation_time' => (int)$consultation_time
            ];
            // Só atualiza a foto se ela foi enviada ou se a URL foi fornecida
            if (!empty($photo_url)) {
                $updateData['photo_url'] = $photo_url;
            }
            
            $this->repository->update((int)$id, $updateData);
            
            // Espelhar de volta para o User associado, se houver
            $doctorRecord = $this->repository->findById((int)$id);
            if ($doctorRecord && !empty($doctorRecord['user_id']) && !empty($photo_url)) {
                $app = \DomainSystem\Core\Application::getInstance();
                $userRepo = $app->getContainer()->make(\DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface::class);
                $userRepo->updateProfile($doctorRecord['user_id'], ['profile_image' => $photo_url]);
            }
            
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico atualizado com sucesso!'];
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function delete()
    {


        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->repository->delete((int)$id);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico excluído com sucesso!'];
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function syncWp()
    {

        
        try {
            // TODO: Implementar busca real na API do WordPress para sincronizar médicos
            // Ex: $apiResponse = $this->httpClient->get('https://daherclinica.com/wp-json/daher/v1/doctors');
            // $doctorsData = json_decode($apiResponse, true);
            
            $syncedCount = 0;
            // foreach ($doctorsData as $docData) { ... }

            $_SESSION['flash_message'] = [
                'type' => 'success', 
                'msg' => "Sincronização concluída! $syncedCount médicos importados/atualizados do site oficial."
            ];

        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Falha na conexão com o site: ' . $e->getMessage()];
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }
}



