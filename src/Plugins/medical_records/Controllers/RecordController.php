<?php

namespace DomainSystem\Plugins\medical_records\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\medical_records\Contracts\RecordRepositoryInterface;

class RecordController
{
    private RecordRepositoryInterface $repository;
    private ThemeManager $theme;

    public function __construct(RecordRepositoryInterface $repository, ThemeManager $theme)
    {
        $this->repository = $repository;
        $this->theme = $theme;
    }

    public function view($appointmentId)
    {
        if (!$appointmentId) die("ID Inválido");

        // Buscar dados do agendamento
        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) die("Agendamento não encontrado.");

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para acessar este prontuário.");
        }

        // Buscar registro médico (se já existir)
        $record = $this->repository->findByAppointment($appointmentId);

        // Se não existir, preparamos vazio
        if (!$record) {
            $record = [
                'id' => null,
                'anamnese' => '',
                'exame_fisico' => '',
                'cid_10' => '',
                'prescricao' => '',
                'evolucao' => ''
            ];
        }

        // Buscar histórico anterior do paciente (para o médico ver)
        $pastRecords = $this->repository->getPatientHistory($appointment['patient_id'], $appointmentId);

        // Buscar exames anexados
        $exams = $this->repository->getExamsByAppointment($appointmentId);

        return $this->theme->render('record', [
            'appointment' => $appointment, 
            'record' => $record,
            'pastRecords' => $pastRecords,
            'exams' => $exams
        ], __DIR__ . '/../views');
    }

    public function save($appointmentId)
    {
        if (!$appointmentId) {
            header("Location: " . BASE_URL . "/admin/appointments?error=ID_INVALIDO");
            exit;
        }

        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) {
             header("Location: " . BASE_URL . "/admin/appointments?error=AGENDAMENTO_INEXISTENTE");
             exit;
        }

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para editar este prontuário.");
        }

        $data = [
            'anamnese' => $_POST['anamnese'] ?? '',
            'exame_fisico' => $_POST['exame_fisico'] ?? '',
            'cid_10' => $_POST['cid_10'] ?? '',
            'prescricao' => $_POST['prescricao'] ?? '',
            'evolucao' => $_POST['evolucao'] ?? ''
        ];

        $this->repository->saveRecord($appointmentId, $appointment['patient_id'], $appointment['doctor_id'], $data);

        // Atualizar status do agendamento para "Atendido" ou "Em Atendimento"
        if (isset($_POST['finalizar'])) {
             $this->repository->updateAppointmentStatus($appointmentId, 'Finalizado');
             header("Location: " . BASE_URL . "/admin/appointments/history?success=Atendimento Finalizado");
        } else {
             $this->repository->updateAppointmentStatus($appointmentId, 'Em Atendimento');
             header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Salvo");
        }
        exit;
    }

    public function printPdf($appointmentId)
    {
        if (!$appointmentId) die("ID Inválido");

        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) die("Agendamento não encontrado.");

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            die("Acesso Negado: Você não tem permissão para imprimir este prontuário.");
        }

        $record = $this->repository->findByAppointment($appointmentId);
        $prescricao = $record ? $record['prescricao'] : '';

        // TODO: Isolar busca de settings em um Repositório Global ou injetar o SettingsManager
        $settings = [];

        // Não vamos usar layout do SO para a impressão, ela é uma pág em branco pro papel
        require __DIR__ . '/../views/print.php';
    }

    public function uploadExam($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_FILES['exam_file']['name'])) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Nenhum arquivo enviado");
            exit;
        }

        $appointment = $this->repository->getAppointmentDetails($appointmentId);
        
        if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
            die("Acesso Negado: Permissão insuficiente.");
        }
        
        $uploadDir = dirname(__DIR__, 4) . '/public/uploads/exams/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['exam_file']['name']));
        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($fileExt, $allowedExts)) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Extensao invalida");
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['exam_file']['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Tipo de arquivo invalido");
            exit;
        }

        $fileName = time() . '_' . $originalName;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $targetFile)) {
            $this->repository->attachExam($appointmentId, $_FILES['exam_file']['name'], '/uploads/exams/' . $fileName);
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame anexado com sucesso");
        } else {
            header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Falha no upload");
        }
        exit;
    }

    public function deleteExam($appointmentId)
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $examId = $_POST['exam_id'] ?? null;
        
        if ($examId) {
            $appointment = $this->repository->getAppointmentDetails($appointmentId);
            
            if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
                die("Acesso Negado: Permissão insuficiente.");
            }

            $exam = $this->repository->getExamById($examId);
            
            if ($exam && $exam['appointment_id'] == $appointmentId) {
                $filePath = dirname(__DIR__, 4) . '/public' . $exam['file_path'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                $this->repository->deleteExam($examId);
            }
        }
        
        header("Location: " . BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame removido");
        exit;
    }
}

