<?php

namespace DomainSystem\Core\Plugin\Services;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * CLASSE: ProcessRegistry (O Gerenciador de Tarefas do Domain System OS)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * Em Sistemas Operacionais reais (Windows, Linux), cada programa em execução 
 * recebe um PID (Process Identifier) que permite ao Kernel monitorar consumo 
 * de memória, tempo de CPU e status de vida. O Domain System OS adota esse 
 * mesmo conceito para os seus módulos (plugins).
 *
 * COMO FUNCIONA:
 * ──────────────
 * 1. Quando o PluginBootstrapper inicia o Boot de um plugin, ele chama 
 *    $this->processRegistry->startProcess('nome', true/false).
 * 2. O ProcessRegistry gera um PID único (ex: PID-8A92B), registra o 
 *    snapshot de memória (memory_get_usage) e o relógio (microtime).
 * 3. Quando o plugin termina de dar Boot (ou explode), o Bootstrapper 
 *    chama endProcess($pid, 'Running') ou endProcess($pid, 'Crashed').
 * 4. O SystemMonitor lê getProcesses() e exibe a tabela visual de PIDs.
 *
 * INTEGRAÇÃO COM O NO-BREAK SHIELD:
 * ──────────────────────────────────
 * Quando um plugin explode durante o Boot, o Bootstrapper consulta o 
 * ProcessRegistry para saber se o PID pertence a um SystemApp (Ring 0) 
 * ou a um UserPlugin (Ring 3). Isso determina o NÍVEL DE SEVERIDADE:
 *   - CRITICAL (P0): Ring 0 caiu → O sistema entra em modo degradado.
 *   - WARNING  (P2): Ring 3 caiu → O sistema sobrevive normalmente.
 *
 * IMPACTO NO DESEMPENHO:
 * ──────────────────────
 * As funções memory_get_usage() e microtime() são nativas do Zend Engine 
 * e resolvem em nanosegundos. O custo de monitorar 50 plugins é inferior 
 * a 0.5ms. Não há impacto perceptível em produção (Hostinger, Vercel, etc).
 *
 * PADRÃO DE PROJETO: Registry Pattern
 * PRINCÍPIO SOLID:   SRP (Single Responsibility) — só gerencia PIDs.
 */
class ProcessRegistry
{
    /** @var array<string, array> Mapa de PIDs ativos nesta requisição */
    private array $processes = [];

    /**
     * Inicia o monitoramento de um processo (plugin).
     * Gera um PID único baseado no nome + timestamp para evitar colisão.
     *
     * @param string $name        Nome do plugin (ex: 'database', 'academy')
     * @param bool   $isSystemApp true = Ring 0 (SystemApp), false = Ring 3 (UserPlugin)
     * @return string O PID gerado (ex: 'PID-8A92B3')
     */
    public function startProcess(string $name, bool $isSystemApp): string
    {
        $pid = 'PID-' . strtoupper(substr(hash('crc32', $name . microtime()), 0, 6));

        $this->processes[$pid] = [
            'pid'            => $pid,
            'name'           => $name,
            'type'           => $isSystemApp ? 'SystemApp' : 'UserPlugin',
            'status'         => 'Booting',
            'start_memory'   => memory_get_usage(),
            'start_time'     => microtime(true),
            'memory_used_kb' => 0,
            'duration_ms'    => 0,
        ];

        return $pid;
    }

    /**
     * Finaliza o monitoramento de um processo, calculando o delta de 
     * memória RAM e o tempo total de Boot em milissegundos.
     *
     * @param string $pid         O PID retornado por startProcess()
     * @param string $finalStatus 'Running' (sucesso) ou 'Crashed' (falha)
     */
    public function endProcess(string $pid, string $finalStatus = 'Running'): void
    {
        if (!isset($this->processes[$pid])) {
            return;
        }

        $p = &$this->processes[$pid];
        $p['status']         = $finalStatus;
        $memDiff             = memory_get_usage() - $p['start_memory'];
        $p['memory_used_kb'] = round($memDiff / 1024, 2);
        $p['duration_ms']    = round((microtime(true) - $p['start_time']) * 1000, 2);
    }

    /**
     * Consulta um PID específico. Usado pelo Bootstrapper para determinar 
     * a Severidade de um crash (Ring 0 = CRITICAL, Ring 3 = WARNING).
     *
     * @return array|null Dados do processo ou null se não encontrado
     */
    public function getProcess(string $pid): ?array
    {
        return $this->processes[$pid] ?? null;
    }

    /**
     * Retorna todos os processos mapeados nesta requisição.
     * Consumido pelo SystemMonitor para exibir o Gerenciador de Tarefas.
     *
     * @return array<string, array>
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }

    /**
     * Tira uma "fotografia" do estado final dos processos e salva em disco
     * para que o painel de administração (Frontend) possa exibir a tabela.
     */
    public function saveSnapshot(string $basePath): void
    {
        $file = rtrim($basePath, '/\\') . '/temp/process_stack.json';
        file_put_contents($file, json_encode($this->processes, JSON_PRETTY_PRINT));
    }

    /**
     * Carrega o último snapshot salvo.
     */
    public function loadSnapshot(string $basePath): array
    {
        $file = rtrim($basePath, '/\\') . '/temp/process_stack.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
}