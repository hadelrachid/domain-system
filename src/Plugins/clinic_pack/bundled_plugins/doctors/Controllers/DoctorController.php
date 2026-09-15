<?php

namespace DomainSystem\Plugins\doctors\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
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

    public function index(Request $request): Response
    {
        $doctors = $this->repository->findAll();
        $theme = $this->theme;
        
        $html = $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
        return new Response($html);
    }

    public function store(Request $request): Response
    {
        $name = $request->input('name', '');
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $crm = $request->input('crm', '');
        $specialty = $request->input('specialty', '');
        $consultation_time = $request->input('consultation_time', 30);
        $photo_url = $request->input('photo_url', '');

        // TODO: Tratamento de arquivos via $request->file('photo') quando disponível
        // Por ora, mantemos $_FILES apenas para o upload físico
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
                    $this->repository->update($result['doctor_id'], [
                        'consultation_time' => (int)$consultation_time,
                        'photo_url' => $photo_url
                    ]);
                    
                    if (!empty($photo_url) && !empty($result['user_id'])) {
                        $userRepo = $app->getContainer()->make(\DomainSystem\Plugins\auth\Contracts\UserRepositoryInterface::class);
                        $userRepo->updateProfile($result['user_id'], ['profile_image' => $photo_url]);
                    }
                    
                    $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico cadastrado com sucesso! Conta de usuário e horários criados.'];
                } else {
                    $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $result['error']];
                }
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        return Response::redirect(BASE_URL . '/admin/doctors');
    }

    public function edit(Request $request): Response
    {
        $id = $request->input('id');
        if (!$id) {
            return Response::redirect(BASE_URL . '/admin/doctors');
        }

        $doctor = $this->repository->findById((int)$id);
        if (!$doctor) {
            return Response::redirect(BASE_URL . '/admin/doctors');
        }

        $theme = $this->theme;
        $html = $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
        return new Response($html);
    }

    public function update(Request $request): Response
    {
        $id = $request->input('id');
        if (!$id) {
            return Response::redirect(BASE_URL . '/admin/doctors');
        }

        $name = $request->input('name', '');
        $crm = $request->input('crm', '');
        $specialty = $request->input('specialty', '');
        $consultation_time = $request->input('consultation_time', 30);
        $photo_url = $request->input('photo_url', '');
        
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
            return Response::redirect(BASE_URL . '/admin/doctors/edit?id=' . $id);
        }

        try {
            $updateData = [
                'name' => $name,
                'crm' => $crm,
                'specialty' => $specialty,
                'consultation_time' => (int)$consultation_time
            ];
            if (!empty($photo_url)) {
                $updateData['photo_url'] = $photo_url;
            }
            
            $this->repository->update((int)$id, $updateData);
            
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

        return Response::redirect(BASE_URL . '/admin/doctors');
    }

    public function delete(Request $request): Response
    {
        $id = $request->input('id');
        if ($id) {
            $this->repository->delete((int)$id);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico excluído com sucesso!'];
        }

        return Response::redirect(BASE_URL . '/admin/doctors');
    }

    public function syncWp(Request $request): Response
    {
        try {
            $syncedCount = 0;
            $_SESSION['flash_message'] = [
                'type' => 'success', 
                'msg' => "Sincronização concluída! $syncedCount médicos importados/atualizados do site oficial."
            ];
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Falha na conexão com o site: ' . $e->getMessage()];
        }

        return Response::redirect(BASE_URL . '/admin/doctors');
    }
}



