<?php

namespace DomainSystem\Core\Plugin\Services;

class ProcessRegistry
{
    private array $processes = [];

    public function startProcess(string $name, bool $isSystemApp): string
    {
        $pid = 'PID-' . strtoupper(substr(hash('crc32', $name . microtime()), 0, 6));
        $this->processes[$pid] = [
            'pid' => $pid,
            'name' => $name,
            'type' => $isSystemApp ? 'SystemApp' : 'UserPlugin',
            'status' => 'Booting',
            'start_memory' => memory_get_usage(),
            'start_time' => microtime(true),
            'memory_used_kb' => 0,
            'duration_ms' => 0
        ];
        return $pid;
    }

    public function endProcess(string $pid, string $finalStatus = 'Running'): void
    {
        if (isset($this->processes[$pid])) {
            $p = &$this->processes[$pid];
            $p['status'] = $finalStatus;
            $memDiff = memory_get_usage() - $p['start_memory'];
            $p['memory_used_kb'] = round($memDiff / 1024, 2);
            $p['duration_ms'] = round((microtime(true) - $p['start_time']) * 1000, 2);
        }
    }

    public function getProcesses(): array
    {
        return $this->processes;
    }
}