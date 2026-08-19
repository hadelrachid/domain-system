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
        
        ob_start();
        include __DIR__ . '/../views/admin_index.php';
        return ob_get_clean();
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
        
        ob_start();
        include __DIR__ . '/../views/admin_edit.php';
        return ob_get_clean();
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
}
