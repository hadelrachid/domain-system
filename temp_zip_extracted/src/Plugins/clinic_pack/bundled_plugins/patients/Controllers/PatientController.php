<?php

namespace DomainSystem\Plugins\patients\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
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

    public function index(Request $request): Response
    {
        $patients = $this->repository->findAll();
        $theme = $this->theme;
        $html = $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
        return new Response($html);
    }

    public function store(Request $request): Response
    {
        $name = $request->input('name', '');
        $cpf = preg_replace('/[^0-9]/', '', $request->input('cpf', ''));
        
        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
        } else {
            try {
                $this->repository->save([
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $request->input('email', ''),
                    'phone' => $request->input('phone', ''),
                    'birthdate' => $request->input('birthdate') ?: null,
                    'zip_code' => $request->input('zip_code'),
                    'address' => $request->input('address'),
                    'address_number' => $request->input('address_number'),
                    'address_complement' => $request->input('address_complement'),
                    'city' => $request->input('city'),
                    'state' => $request->input('state'),
                    'insurance_number' => $request->input('insurance_number')
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente cadastrado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro ao salvar paciente: ' . $e->getMessage()];
            }
        }

        return Response::redirect(BASE_URL . '/admin/patients');
    }

    public function edit(Request $request): Response
    {
        $id = $request->input('id');
        if (!$id) {
            return Response::redirect(BASE_URL . '/admin/patients');
        }

        $patient = $this->repository->findById((int)$id);
        if (!$patient) {
            return Response::redirect(BASE_URL . '/admin/patients');
        }

        $theme = $this->theme;
        $html = $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
        return new Response($html);
    }

    public function update(Request $request): Response
    {
        $id = $request->input('id');
        if (!$id) {
            return Response::redirect(BASE_URL . '/admin/patients');
        }

        $name = $request->input('name', '');
        $cpf = preg_replace('/[^0-9]/', '', $request->input('cpf', ''));
        
        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
            return Response::redirect(BASE_URL . '/admin/patients/edit?id=' . $id);
        }

        try {
            $this->repository->update((int)$id, [
                'name' => $name,
                'cpf' => $cpf,
                'email' => $request->input('email', ''),
                'phone' => $request->input('phone', ''),
                'birthdate' => $request->input('birthdate') ?: null,
                'zip_code' => $request->input('zip_code'),
                'address' => $request->input('address'),
                'address_number' => $request->input('address_number'),
                'address_complement' => $request->input('address_complement'),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'insurance_number' => $request->input('insurance_number')
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente atualizado com sucesso!'];
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro ao atualizar paciente: ' . $e->getMessage()];
        }

        return Response::redirect(BASE_URL . '/admin/patients');
    }

    public function delete(Request $request): Response
    {
        $id = $request->input('id');
        if ($id) {
            $this->repository->delete((int)$id);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente removido com sucesso.'];
        }

        return Response::redirect(BASE_URL . '/admin/patients');
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



