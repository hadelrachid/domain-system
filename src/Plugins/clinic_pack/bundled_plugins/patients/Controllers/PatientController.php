<?php

namespace DomainSystem\Plugins\patients\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\patients\Contracts\PatientRepositoryInterface;

class PatientController
{
    private ThemeManager $theme;
    private PatientRepositoryInterface $repository;

    public function __construct(ThemeManager $theme, PatientRepositoryInterface $repository)
    {
        $this->theme = $theme;
        $this->repository = $repository;
    }

    public function index()
    {
        $patients = $this->repository->findAll();

        $theme = $this->theme;
        return $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
    }

    public function store()
    {


        $name = $_POST['name'] ?? '';
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        
        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
        } else {
            try {
                $this->repository->save([
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'birthdate' => !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
                    'zip_code' => $_POST['zip_code'] ?? null,
                    'address' => $_POST['address'] ?? null,
                    'address_number' => $_POST['address_number'] ?? null,
                    'address_complement' => $_POST['address_complement'] ?? null,
                    'city' => $_POST['city'] ?? null,
                    'state' => $_POST['state'] ?? null,
                    'insurance_number' => $_POST['insurance_number'] ?? null
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente cadastrado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro ao salvar paciente: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/patients");
        exit;
    }

    public function edit()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/patients");
            exit;
        }

        $patient = $this->repository->findById((int)$id);
        if (!$patient) {
            header("Location: " . BASE_URL . "/admin/patients");
            exit;
        }

        $theme = $this->theme;
        return $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
    }

    public function update()
    {


        $id = $_POST['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/patients");
            exit;
        }

        $name = $_POST['name'] ?? '';
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        
        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
            header("Location: " . BASE_URL . "/admin/patients/edit?id=" . $id);
            exit;
        }

        try {
            $this->repository->update((int)$id, [
                'name' => $name,
                'cpf' => $cpf,
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'birthdate' => !empty($_POST['birthdate']) ? $_POST['birthdate'] : null,
                'zip_code' => $_POST['zip_code'] ?? null,
                'address' => $_POST['address'] ?? null,
                'address_number' => $_POST['address_number'] ?? null,
                'address_complement' => $_POST['address_complement'] ?? null,
                'city' => $_POST['city'] ?? null,
                'state' => $_POST['state'] ?? null,
                'insurance_number' => $_POST['insurance_number'] ?? null
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente atualizado com sucesso!'];
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro ao atualizar paciente: ' . $e->getMessage()];
        }

        header("Location: " . BASE_URL . "/admin/patients");
        exit;
    }

    public function delete()
    {


        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->repository->delete((int)$id);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente removido com sucesso.'];
        }

        header("Location: " . BASE_URL . "/admin/patients");
        exit;
    }

    public function renderShortcodeForm(array $attributes = []): string
    {
        ob_start();
        include __DIR__ . '/../views/partials/form.php';
        return ob_get_clean();
    }

    public function renderShortcodeList(array $attributes = []): string
    {
        $limit = isset($attributes['limit']) ? (int)$attributes['limit'] : 10;
        $showActions = isset($attributes['actions']) ? filter_var($attributes['actions'], FILTER_VALIDATE_BOOLEAN) : true;
        
        $patients = $this->repository->findLatest($limit);

        ob_start();
        include __DIR__ . '/../views/partials/list.php';
        return ob_get_clean();
    }
}



