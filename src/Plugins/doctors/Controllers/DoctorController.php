<?php

namespace Plugins\doctors\Controllers;

use Core\Database;

class DoctorController
{
    private $db;
    private $theme;

    public function __construct(Database $db, $theme = null)
    {
        $this->db = $db;
        $this->theme = $theme;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $doctors = $this->db->table('doctors')->get();
        $theme = $this->theme;
        
        ob_start();
        include __DIR__ . '/../views/admin_index.php';
        return ob_get_clean();
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
        
        ob_start();
        include __DIR__ . '/../views/admin_edit.php';
        return ob_get_clean();
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

    // Método que será chamado pelo botão de Sincronizar
    public function syncWp()
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        
        // Aqui simularíamos uma chamada cURL para a REST API do WordPress (ex: wp-json/wp/v2/users ou um endpoint customizado)
        // Por enquanto, faremos um Mock (Simulação) de como isso funcionaria:
        
        $wp_domain = 'https://daherclinica.com.br'; // No futuro isso virá do banco de configurações
        
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
                // Verificar se o médico já existe pelo wp_id
                $exists = $this->db->table('doctors')->where('wp_id', '=', $docData['wp_id'])->get();
                
                if (empty($exists)) {
                    // Cadastra novo médico vindo do WP
                    $this->db->table('doctors')->insert([
                        'wp_id' => $docData['wp_id'],
                        'name' => $docData['name'],
                        'crm' => $docData['crm'],
                        'specialty' => $docData['specialty'],
                        'consultation_time' => 30, // Tempo padrão
                        'photo_url' => $docData['photo_url']
                    ]);
                    $syncedCount++;
                } else {
                    // Atualiza os dados do médico existente
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
