<?php

namespace DomainSystem\Plugins\doctors\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\Database\QueryBuilder;

class DoctorController
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
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $doctors = $this->db->table('doctors')->get();
        $theme = $this->theme;
        
        return $this->theme->render('admin_index', get_defined_vars(), __DIR__ . '/../views');
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $name = $_POST['name'] ?? '';
        $crm = $_POST['crm'] ?? '';
        $specialty = $_POST['specialty'] ?? '';
        $consultation_time = $_POST['consultation_time'] ?? 30;
        $photo_url = $_POST['photo_url'] ?? '';

        if (empty($name)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'O nome do médico é obrigatório!'];
        } else {
            try {
                $this->db->table('doctors')->insert([
                    'name' => $name,
                    'crm' => $crm,
                    'specialty' => $specialty,
                    'consultation_time' => (int)$consultation_time,
                    'photo_url' => $photo_url
                ]);
                $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico cadastrado com sucesso!'];
            } catch (\Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
            }
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function edit()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header("Location: " . BASE_URL . "/admin/doctors");
            exit;
        }

        $doctor = $this->db->table('doctors')->where('id', '=', $id)->get();
        if (empty($doctor)) {
            header("Location: " . BASE_URL . "/admin/doctors");
            exit;
        }

        $doctor = $doctor[0];
        $theme = $this->theme;
        
        return $this->theme->render('admin_edit', get_defined_vars(), __DIR__ . '/../views');
    }

    public function update()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

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

        if (empty($name)) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'O nome do médico é obrigatório!'];
            header("Location: " . BASE_URL . "/admin/doctors/edit?id=" . $id);
            exit;
        }

        try {
            $this->db->table('doctors')->where('id', '=', $id)->update([
                'name' => $name,
                'crm' => $crm,
                'specialty' => $specialty,
                'consultation_time' => (int)$consultation_time,
                'photo_url' => $photo_url
            ]);
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico atualizado com sucesso!'];
        } catch (\Exception $e) {
            $_SESSION['flash_message'] = ['type' => 'error', 'msg' => 'Erro: ' . $e->getMessage()];
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function delete()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $id = $_POST['id'] ?? null;
        if ($id) {
            $this->db->table('doctors')->where('id', '=', $id)->delete();
            $_SESSION['flash_message'] = ['type' => 'success', 'msg' => 'Médico excluído com sucesso!'];
        }

        header("Location: " . BASE_URL . "/admin/doctors");
        exit;
    }

    public function syncWp()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        try {
            // Simulando um JSON recebido do WordPress
            $mockApiResponse = [
                [
                    'wp_id' => 101,
                    'name' => 'Dr. House (Via WP)',
                    'crm' => 'CRM/SP 123456',
                    'specialty' => 'Diagnóstico',
                    'photo_url' => 'https://randomuser.me/api/portraits/men/32.jpg'
                ],
                [
                    'wp_id' => 102,
                    'name' => 'Dra. Meredith Grey (Via WP)',
                    'crm' => 'CRM/SP 654321',
                    'specialty' => 'Cirurgia Geral',
                    'photo_url' => 'https://randomuser.me/api/portraits/women/44.jpg'
                ]
            ];

            $syncedCount = 0;

            foreach ($mockApiResponse as $docData) {
                $exists = $this->db->table('doctors')->where('wp_id', '=', $docData['wp_id'])->get();
                
                if (empty($exists)) {
                    $this->db->table('doctors')->insert([
                        'wp_id' => $docData['wp_id'],
                        'name' => $docData['name'],
                        'crm' => $docData['crm'],
                        'specialty' => $docData['specialty'],
                        'consultation_time' => 30,
                        'photo_url' => $docData['photo_url']
                    ]);
                    $syncedCount++;
                } else {
                    $this->db->table('doctors')->where('id', '=', $exists[0]['id'])->update([
                        'name' => $docData['name'],
                        'crm' => $docData['crm'],
                        'specialty' => $docData['specialty'],
                        'photo_url' => $docData['photo_url']
                    ]);
                    $syncedCount++;
                }
            }

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



