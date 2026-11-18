<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\DemandLogStorageInterface;
use DomainSystem\Plugins\domain_drop\DTO\DemandEvent;
use Throwable;

/**
 * Armazenamento de logs de demanda em arquivos JSON particionados por mês e ano (SRP).
 *
 * Estrutura de diretórios criada:
 *   storage/demand_logs/{ano}/json_{nome_do_mes}_{ano}/interactions.json
 *
 * Garante concorrência segura com trava de arquivo (LOCK_EX) e
 * expurgo automático de dados com mais de 2 anos.
 */
class FileDemandLogStorage implements DemandLogStorageInterface
{
    private const MONTH_NAMES = [
        1  => 'janeiro',
        2  => 'fevereiro',
        3  => 'marco',
        4  => 'abril',
        5  => 'maio',
        6  => 'junho',
        7  => 'julho',
        8  => 'agosto',
        9  => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    private string $baseDir;

    public function __construct(?string $baseDir = null)
    {
        if ($baseDir !== null && $baseDir !== '') {
            $this->baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');
        } else {
            $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);
            $this->baseDir = rtrim(str_replace('\\', '/', $root), '/') . '/storage/demand_logs';
        }
    }

    public function appendInteraction(DemandEvent $event): void
    {
        try {
            $filePath = $this->getFilePathForPeriod();
            $dir = dirname($filePath);

            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $record = [
                'id'             => bin2hex(random_bytes(8)),
                'product_id'     => $event->productId,
                'product_title'  => $event->productTitle,
                'category_slug'  => $event->categorySlug,
                'action_type'    => $event->actionType,
                'user_type'      => $event->userType,
                'customer_id'    => $event->customerId,
                'customer_name'  => $event->customerName,
                'customer_email' => $event->customerEmail,
                'session_id'     => $event->sessionId,
                'ip_address'     => $event->ipAddress ?: (\DomainSystem\Plugins\domain_drop\DTO\DemandEvent::resolveClientIp()),
                'created_at'     => date('Y-m-d H:i:s'),
            ];

            $line = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
            @file_put_contents($filePath, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            error_log('[FileDemandLogStorage] Falha ao registrar log de demanda JSON: ' . $e->getMessage());
        }
    }

    public function getRecentInteractions(?string $yearMonth = null, int $limit = 50): array
    {
        $filePath = $this->getFilePathForPeriod($yearMonth);
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || empty($lines)) {
            return [];
        }

        $records = [];
        // Itera de trás para frente para pegar os mais recentes primeiro
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $records[] = $decoded;
                if (count($records) >= $limit) {
                    break;
                }
            }
        }

        return $records;
    }

    public function getLeadEmails(?string $yearMonth = null): array
    {
        $filePath = $this->getFilePathForPeriod($yearMonth);
        if (!file_exists($filePath)) {
            return [];
        }

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || empty($lines)) {
            return [];
        }

        $emails = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $item = json_decode($line, true);
            if (is_array($item)) {
                $email = strtolower(trim((string)($item['customer_email'] ?? '')));
                if ($email !== '' && ($item['user_type'] ?? '') === 'cliente_logado') {
                    $emails[] = $email;
                }
            }
        }

        return array_values(array_unique($emails));
    }

    public function purgeExpiredLogs(int $retentionYears = 2): int
    {
        if (!is_dir($this->baseDir)) {
            return 0;
        }

        $currentYear = (int)date('Y');
        $cutoffYear = $currentYear - max(1, $retentionYears);
        $purgedCount = 0;

        $entries = @scandir($this->baseDir);
        if ($entries === false) {
            return 0;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $yearDirPath = $this->baseDir . '/' . $entry;
            if (is_dir($yearDirPath) && preg_match('/^\d{4}$/', $entry)) {
                $dirYear = (int)$entry;
                // Expurga anos anteriores à janela de 2 anos
                if ($dirYear < $cutoffYear) {
                    $this->deleteDirectoryRecursive($yearDirPath);
                    $purgedCount++;
                }
            }
        }

        return $purgedCount;
    }

    public function getFilePathForPeriod(?string $yearMonth = null): string
    {
        $period = $yearMonth ?: date('Y-m');
        if (!preg_match('/^(\d{4})-(\d{2})$/', $period, $matches)) {
            $year = date('Y');
            $monthNum = (int)date('n');
        } else {
            $year = $matches[1];
            $monthNum = (int)$matches[2];
        }

        $monthName = self::MONTH_NAMES[$monthNum] ?? 'geral';
        $folderName = "json_{$monthName}_{$year}";

        return "{$this->baseDir}/{$year}/{$folderName}/interactions.json";
    }

    public function promoteInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $ipAddress = null,
        ?string $yearMonth = null
    ): int {
        $cleanSession = trim($sessionId);
        $cleanIp = trim($ipAddress ?? '');
        if ($customerId <= 0 || ($cleanSession === '' && $cleanIp === '')) {
            return 0;
        }

        $filePath = $this->getFilePathForPeriod($yearMonth);
        if (!file_exists($filePath)) {
            return 0;
        }

        $fp = @fopen($filePath, 'c+');
        if (!$fp) {
            return 0;
        }

        $updatedCount = 0;
        try {
            if (!flock($fp, LOCK_EX)) {
                fclose($fp);
                return 0;
            }

            $lines = [];
            while (($line = fgets($fp)) !== false) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }
                $record = json_decode($trimmed, true);
                if (is_array($record)) {
                    $recSession = (string)($record['session_id'] ?? '');
                    $recIp = (string)($record['ip_address'] ?? '');
                    $recCustId = (int)($record['customer_id'] ?? 0);
                    $recUserType = (string)($record['user_type'] ?? 'externo');

                    $matchesSession = ($cleanSession !== '' && $recSession === $cleanSession);
                    $matchesIp = ($cleanIp !== '' && $recIp === $cleanIp);

                    if (($matchesSession || $matchesIp) && ($recCustId <= 0 || $recUserType === 'externo')) {
                        $record['customer_id'] = $customerId;
                        $record['customer_name'] = $customerName;
                        $record['customer_email'] = $customerEmail;
                        $record['user_type'] = 'cliente_logado';
                        if (empty($record['ip_address']) && $cleanIp !== '') {
                            $record['ip_address'] = $cleanIp;
                        }
                        $updatedCount++;
                    }
                    $lines[] = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                }
            }

            if ($updatedCount > 0) {
                ftruncate($fp, 0);
                rewind($fp);
                foreach ($lines as $lineStr) {
                    fwrite($fp, $lineStr);
                }
                fflush($fp);
            }

            flock($fp, LOCK_UN);
            fclose($fp);
        } catch (Throwable $e) {
            @fclose($fp);
            error_log('[FileDemandLogStorage] Falha ao promover interações de sessão/IP: ' . $e->getMessage());
        }

        return $updatedCount;
    }

    public function promoteSessionInteractions(
        string $sessionId,
        int $customerId,
        string $customerName,
        string $customerEmail,
        ?string $yearMonth = null
    ): int {
        return $this->promoteInteractions($sessionId, $customerId, $customerName, $customerEmail, null, $yearMonth);
    }

    private function deleteDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = @scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }
}
