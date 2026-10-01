<?php

namespace DomainSystem\Plugins\auth\Services;

use DomainSystem\Plugins\auth\Contracts\EmailSenderInterface;
use DomainSystem\Core\Application;
use DomainSystem\Plugins\Database\Connection;

class PhpMailSender implements EmailSenderInterface
{
    public function send(string $to, string $subject, string $message): void
    {
        // Pega as configurações do banco
        $db = Application::getInstance()->getContainer()->make(Connection::class)->getPdo();
        $stmt = $db->query("SELECT key_name, key_value FROM settings WHERE key_name LIKE 'smtp_%'");
        $settings = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        $host = $settings['smtp_host'] ?? '';
        $port = $settings['smtp_port'] ?? '587';
        $user = $settings['smtp_user'] ?? '';
        $pass = $settings['smtp_pass'] ?? '';

        if (empty($host) || empty($user) || empty($pass)) {
            // Se não tem SMTP configurado, cai no mail() padrão ou log
            error_log("Tentou enviar email para $to, mas SMTP não está configurado.");
            return;
        }

        // Simples sender SMTP nativo com SSL/TLS
        try {
            $this->sendSmtpNative($host, $port, $user, $pass, $to, $subject, $message);
        } catch (\Exception $e) {
            error_log("Falha ao enviar email SMTP: " . $e->getMessage());
        }
    }

    private function sendSmtpNative($host, $port, $user, $pass, $to, $subject, $message)
    {
        $protocol = ($port == 465) ? 'ssl://' : '';
        $socket = stream_socket_client($protocol . $host . ':' . $port, $errno, $errstr, 10);
        
        if (!$socket) throw new \Exception("Nao foi possivel conectar ao SMTP: $errstr");

        $this->readSmtp($socket); // banner

        fwrite($socket, "EHLO localhost\r\n");
        $this->readSmtp($socket);

        if ($port != 465) {
            fwrite($socket, "STARTTLS\r\n");
            $this->readSmtp($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($socket, "EHLO localhost\r\n");
            $this->readSmtp($socket);
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $this->readSmtp($socket);

        fwrite($socket, base64_encode($user) . "\r\n");
        $this->readSmtp($socket);

        fwrite($socket, base64_encode($pass) . "\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "MAIL FROM:<$user>\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "RCPT TO:<$to>\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "DATA\r\n");
        $this->readSmtp($socket);

        $headers = "From: Daher Clínica <$user>\r\n";
        $headers .= "To: <$to>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";

        fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    private function readSmtp($socket)
    {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $data;
    }
}
