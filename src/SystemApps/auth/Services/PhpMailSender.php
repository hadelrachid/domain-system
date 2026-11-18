<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Services;

use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
use DomainSystem\SystemApps\Database\Connection;
use PDO;
use RuntimeException;
use Throwable;

class PhpMailSender implements EmailSenderInterface
{
    private PDO $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection->getPdo();
    }

    public function send(string $to, string $subject, string $message): void
    {
        // 1. Tenta carregar do banco de dados (se tabela settings existir)
        $settings = [];
        try {
            $stmt = $this->db->query("SELECT key_name, key_value FROM settings WHERE key_name LIKE 'smtp_%' OR key_name LIKE 'mail_%'");
            if ($stmt) {
                $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
            }
        } catch (Throwable $e) {
            $settings = [];
        }

        // 2. Fallback com suporte a variáveis do .env ou getenv()
        $host = trim((string)($settings['smtp_host'] ?? (getenv('SMTP_HOST') ?: '')));
        $port = (int)($settings['smtp_port'] ?? (getenv('SMTP_PORT') ?: 465));
        $user = trim((string)($settings['smtp_user'] ?? (getenv('SMTP_USER') ?: '')));
        $pass = trim((string)($settings['smtp_pass'] ?? (getenv('SMTP_PASS') ?: '')));
        $fromName = trim((string)($settings['mail_from_name'] ?? (getenv('MAIL_FROM_NAME') ?: 'RachidD Store')));
        $fromAddress = trim((string)($settings['mail_from_address'] ?? (getenv('MAIL_FROM_ADDRESS') ?: $user)));

        if ($host === '' || $user === '' || $pass === '') {
            error_log("Tentou enviar email para {$to}, mas SMTP não está configurado (defina no .env ou na tabela settings).");
            return;
        }

        // 3. Sender SMTP nativo com SSL (465) ou STARTTLS (587)
        try {
            $this->sendSmtpNative($host, $port, $user, $pass, $fromName, $fromAddress, $to, $subject, $message);
        } catch (Throwable $e) {
            error_log("Falha ao enviar email SMTP para {$to}: " . $e->getMessage());
        }
    }

    private function sendSmtpNative(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $fromName,
        string $fromAddress,
        string $to,
        string $subject,
        string $message
    ): void {
        $protocol = ($port === 465) ? 'ssl://' : '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $socket = @stream_socket_client(
            $protocol . $host . ':' . $port,
            $errno,
            $errstr,
            12,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new RuntimeException("Não foi possível conectar ao SMTP ({$host}:{$port}): {$errstr} (código {$errno})");
        }

        stream_set_timeout($socket, 12);
        $this->readSmtp($socket); // banner

        $clientHost = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';
        fwrite($socket, "EHLO {$clientHost}\r\n");
        $this->readSmtp($socket);

        if ($port !== 465) {
            fwrite($socket, "STARTTLS\r\n");
            $this->readSmtp($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($socket, "EHLO {$clientHost}\r\n");
            $this->readSmtp($socket);
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        $this->readSmtp($socket);

        fwrite($socket, base64_encode($user) . "\r\n");
        $this->readSmtp($socket);

        fwrite($socket, base64_encode($pass) . "\r\n");
        $authResponse = $this->readSmtp($socket);
        if (str_starts_with($authResponse, '535')) {
            fclose($socket);
            throw new RuntimeException("Falha de autenticação SMTP para o usuário {$user}");
        }

        fwrite($socket, "MAIL FROM:<{$user}>\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "DATA\r\n");
        $this->readSmtp($socket);

        $senderName = $fromName !== '' ? $fromName : 'RachidD Store';
        $senderMail = $fromAddress !== '' ? $fromAddress : $user;

        $headers = "From: =?UTF-8?B?" . base64_encode($senderName) . "?= <{$senderMail}>\r\n";
        $headers .= "Reply-To: <{$senderMail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=utf-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";

        fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
        $this->readSmtp($socket);

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    private function readSmtp($socket): string
    {
        $data = '';
        while ($str = fgets($socket, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) === ' ') {
                break;
            }
        }
        return $data;
    }
}
