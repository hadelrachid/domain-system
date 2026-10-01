<?php

namespace DomainSystem\Plugins\medical_records\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Plugins\medical_records\Contracts\RecordRepositoryInterface;
use DomainSystem\Core\Http\Response;

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
        if (!$appointmentId) return Response::redirect(BASE_URL . '/admin/appointments?error=ID_Invalido');

        // Buscar dados do agendamento
        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) return Response::redirect(BASE_URL . '/admin/appointments?error=Agendamento_nao_encontrado');


        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            return Response::redirect(BASE_URL . '/admin/appointments?error=Acesso_Negado');
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
            return Response::redirect(BASE_URL . "/admin/appointments?error=ID_INVALIDO");
        }

        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) {
             return Response::redirect(BASE_URL . "/admin/appointments?error=AGENDAMENTO_INEXISTENTE");
        }


        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            return Response::redirect(BASE_URL . "/admin/appointments?error=Acesso_Negado");
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
             return Response::redirect(BASE_URL . "/admin/appointments/history?success=Atendimento Finalizado");
        } else {
             $this->repository->updateAppointmentStatus($appointmentId, 'Em Atendimento');
             return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Salvo");
        }
    }

    public function printPdf($appointmentId)
    {
        if (!$appointmentId) return Response::redirect(BASE_URL . "/admin/appointments?error=ID_INVALIDO");

        $appointment = $this->repository->getAppointmentDetails($appointmentId);

        if (!$appointment) return Response::redirect(BASE_URL . "/admin/appointments?error=Agendamento_nao_encontrado");


        if (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null)) {
            return Response::redirect(BASE_URL . "/admin/appointments?error=Acesso_Negado");
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

        if (empty($_FILES['exam_file']['name'])) {
            return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Nenhum arquivo enviado");
        }

        $appointment = $this->repository->getAppointmentDetails($appointmentId);
        
        if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
            return Response::redirect(BASE_URL . "/admin/appointments?error=Acesso_Negado");
        }
        
        $uploadDir = dirname(__DIR__, 4) . '/public/uploads/exams/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $originalName = preg_replace("/[^a-zA-Z0-9\._-]/", "", basename($_FILES['exam_file']['name']));
        $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($fileExt, $allowedExts)) {
            return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Extensao_invalida");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['exam_file']['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes)) {
            return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Tipo_de_arquivo_invalido");
        }

        $fileName = time() . '_' . $originalName;
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['exam_file']['tmp_name'], $targetFile)) {
            $this->repository->attachExam($appointmentId, $_FILES['exam_file']['name'], '/uploads/exams/' . $fileName);
            return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame anexado com sucesso");
        } else {
            return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?error=Falha no upload");
        }
    }

    public function deleteExam($appointmentId)
    {

        $examId = $_POST['exam_id'] ?? null;
        
        if ($examId) {
            $appointment = $this->repository->getAppointmentDetails($appointmentId);
            
            if (!$appointment || (($_SESSION['user_role'] ?? '') === 'doctor' && $appointment['doctor_id'] != ($_SESSION['doctor_id'] ?? null))) {
                return Response::redirect(BASE_URL . "/admin/appointments?error=Acesso_Negado");
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
        
        return Response::redirect(BASE_URL . "/admin/appointments/record/" . $appointmentId . "?success=Exame removido");
    }
}
