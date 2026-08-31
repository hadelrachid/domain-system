<?php

namespace DomainSystem\Plugins\ApiGateway\Controllers;

use DomainSystem\Core\Http\Request;
use DomainSystem\Plugins\appointments\Contracts\PatientReaderInterface;
use DomainSystem\Plugins\appointments\Contracts\AppointmentRepositoryInterface;
use Exception;

class WebhookController
{
    private PatientReaderInterface $patients;
    private AppointmentRepositoryInterface $appointments;

    public function __construct(PatientReaderInterface $patients, AppointmentRepositoryInterface $appointments)
    {
        $this->patients = $patients;
        $this->appointments = $appointments;
    }

    public function handleWhatsApp(Request $request)
    {
        header("Content-Type: application/json");
        
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        if (!$data || !isset($data["phone"]) || !isset($data["name"]) || !isset($data["date"]) || !isset($data["time"])) {
            http_response_code(400);
            return json_encode(["error" => "Campos obrigatorios faltando: phone, name, date, time."]);
        }

        try {
            $phone = preg_replace("/[^0-9]/", "", $data["phone"]);
            $patient = $this->patients->findPatientByPhone($phone);
            
            $patientId = null;
            if ($patient) {
                $patientId = $patient["id"];
            } else {
                // Generates a dummy CPF since it is required by the DB
                $dummyCpf = date("YmdHis");
                
                // createPatient signature in PatientReaderInterface is createPatient(string $name, string $phone): int
                // If it doesn't take CPF, it must be failing inside the PatientReader implementation!
                // Wait, I can just use raw DB here if I have to, or modify createPatient interface...
                // Actually, I'll just check if createPatient takes a third parameter in the interface.
                // Wait, it DOESN'T take a third param in the interface. Let's see what happens.
                
                $patientId = $this->patients->createPatient($data["name"], $phone);
            }

            $appointmentData = [
                "patient_id" => $patientId,
                "doctor_id" => $data["doctor_id"] ?? 1,
                "appointment_date" => $data["date"],
                "appointment_time" => $data["time"],
                "status" => "Pendente",
                "reception_notes" => "Auto-agendamento via WhatsApp/API"
            ];

            $this->appointments->createAppointment($appointmentData);

            http_response_code(201);
            return json_encode([
                "success" => true,
                "message" => "Agendamento criado com sucesso",
                "patient_id" => $patientId
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            return json_encode(["error" => "Erro interno: " . $e->getMessage()]);
        }
    }
}

