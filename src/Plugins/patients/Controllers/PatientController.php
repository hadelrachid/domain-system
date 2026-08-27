<?php

namespace DomainSystem\Plugins\patients\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class PatientController
{
    private ThemeManager $theme;
    private QueryBuilder $db;

    public function __construct(ThemeManager $theme, QueryBuilder $db)
    {
        $this->theme = $theme;
        $this->db = $db;
    }

    public function index()
    {
        $patients = $this->db->table('patients')->get();

        // Passamos o $theme para a view para poder renderizar os headers
        $theme = $this->theme;
        
        return $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $name = $_POST['name'] ?? '';
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        
        $zip_code = $_POST['zip_code'] ?? null;
        $address = $_POST['address'] ?? null;
        $address_number = $_POST['address_number'] ?? null;
        $address_complement = $_POST['address_complement'] ?? null;
        $city = $_POST['city'] ?? null;
        $state = $_POST['state'] ?? null;
        $insurance_number = $_POST['insurance_number'] ?? null;

        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
        } else {
            try {
                $this->db->table('patients')->insert([
                    'name' => $name,
                    'cpf' => $cpf,
                    'email' => $email,
                    'phone' => $phone,
                    'birthdate' => $birthdate,
                    'zip_code' => $zip_code,
                    'address' => $address,
                    'address_number' => $address_number,
                    'address_complement' => $address_complement,
                    'city' => $city,
                    'state' => $state,
                    'insurance_number' => $insurance_number
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente cadastrado com sucesso!'];
            } catch (\Exception $e) {
                // Muito provável de ser CPF duplicado (UNIQUE)
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

        $patient = $this->db->table('patients')->where('id', '=', $id)->get();
        if (empty($patient)) {
            header("Location: " . BASE_URL . "/admin/patients");
            exit;
        }

        $patient = $patient[0];
        $theme = $this->theme;
        
        return $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
    }

    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/patients");
            exit;
        }

        $name = $_POST['name'] ?? '';
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $birthdate = !empty($_POST['birthdate']) ? $_POST['birthdate'] : null;
        
        $zip_code = $_POST['zip_code'] ?? null;
        $address = $_POST['address'] ?? null;
        $address_number = $_POST['address_number'] ?? null;
        $address_complement = $_POST['address_complement'] ?? null;
        $city = $_POST['city'] ?? null;
        $state = $_POST['state'] ?? null;
        $insurance_number = $_POST['insurance_number'] ?? null;

        if (empty($name) || empty($cpf)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Nome e CPF são obrigatórios!'];
            header("Location: " . BASE_URL . "/admin/patients/edit?id=" . $id);
            exit;
        }

        try {
            $this->db->table('patients')->where('id', '=', $id)->update([
                'name' => $name,
                'cpf' => $cpf,
                'email' => $email,
                'phone' => $phone,
                'birthdate' => $birthdate,
                'zip_code' => $zip_code,
                'address' => $address,
                'address_number' => $address_number,
                'address_complement' => $address_complement,
                'city' => $city,
                'state' => $state,
                'insurance_number' => $insurance_number
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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->db->table('patients')->where('id', '=', $id)->delete();
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Paciente removido com sucesso.'];
        }

        header("Location: " . BASE_URL . "/admin/patients");
        exit;
    }

    /**
     * Renderiza o formulário de pacientes via Shortcode.
     */
    public function renderShortcodeForm(array $attributes = []): string
    {
        ob_start();
        include __DIR__ . '/../views/partials/form.php';
        return ob_get_clean();
    }

    /**
     * Renderiza a lista de pacientes via Shortcode.
     */
    public function renderShortcodeList(array $attributes = []): string
    {
        $limit = isset($attributes['limit']) ? (int)$attributes['limit'] : 10;
        $showActions = isset($attributes['actions']) ? filter_var($attributes['actions'], FILTER_VALIDATE_BOOLEAN) : true;
        
        // Pega os últimos X pacientes
        // Nota: Um order by ideal exigiria 'ORDER BY id DESC', mas como o QueryBuilder base não tem, pegamos todos e filtramos.
        // Se a DB for sqlite com a QueryBuilder simples atual, vamos apenas fazer array_slice.
        $allPatients = $this->db->table('patients')->get();
        
        // Se tivesse order by na query: $this->db->table('patients')->orderBy('id', 'DESC')->limit($limit)->get();
        // Fallback rápido para o array_reverse e slice:
        $patients = array_slice(array_reverse($allPatients), 0, $limit);

        ob_start();
        include __DIR__ . '/../views/partials/list.php';
        return ob_get_clean();
    }
}



