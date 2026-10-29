<?php

declare(strict_types=1);

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
    /** @var string|null ID único do ciclo de vida do Singleton ativo (ex: 'KRN-8F12A0') */
    private static ?string $activeSingletonId = null;

    /** @var array<string, string> Mapa de pluginName => PID específico alocado para o Singleton */
    private static array $singletonPids = [];

    /** @var array<string, array> Mapa de PIDs ativos nesta requisição */
    private array $processes = [];

    /** @var string|null Caminho base da aplicação */
    private ?string $basePath = null;

    /**
     * Construtor do ProcessRegistry.
     *
     * @param string|null $basePath Caminho base do sistema para persistência do ciclo de vida
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : null);
    }

    /**
     * Inicia o monitoramento de um processo (plugin).
     * Quando o Singleton nasce, cada plugin recebe um PID aleatório, porém específico para aquele módulo.
     * O PID permanece 100% estável enquanto este Singleton estiver vivo.
     *
     * @param string $name        Nome do plugin (ex: 'database', 'academy')
     * @param bool   $isSystemApp true = Ring 0 (SystemApp), false = Ring 3 (UserPlugin)
     * @return string O PID alocado (ex: 'PID-8A92B3')
     */
    public function startProcess(string $name, bool $isSystemApp): string
    {
        $this->ensureSingletonLifecycle();

        if (!isset(self::$singletonPids[$name])) {
            self::$singletonPids[$name] = $this->generateRandomPid();
            $this->persistLifecycle();
        }

        $pid = self::$singletonPids[$name];

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
     * Garante que o ciclo de vida do Singleton esteja inicializado.
     * Restaura do arquivo temp/kernel_singleton.json se disponível ou cria um novo ciclo de vida.
     */
    private function ensureSingletonLifecycle(): void
    {
        if (self::$activeSingletonId !== null) {
            return;
        }

        $file = $this->getLifecycleFilePath();
        if ($file && file_exists($file)) {
            $content = @file_get_contents($file);
            $data = $content ? json_decode($content, true) : null;
            if (is_array($data) && !empty($data['singleton_id']) && isset($data['pids']) && is_array($data['pids'])) {
                self::$activeSingletonId = (string) $data['singleton_id'];
                self::$singletonPids = $data['pids'];
                return;
            }
        }

        // Quando nasce o Singleton, gera um ID de ciclo de vida único
        self::$activeSingletonId = 'KRN-' . strtoupper(bin2hex(random_bytes(3)));
        self::$singletonPids = [];
        $this->persistLifecycle();
    }

    /**
     * Gera um PID aleatório único garantindo não colidir com outros PIDs já alocados neste Singleton.
     */
    private function generateRandomPid(): string
    {
        do {
            $pid = 'PID-' . strtoupper(bin2hex(random_bytes(3)));
        } while (in_array($pid, self::$singletonPids, true));

        return $pid;
    }

    /**
     * Persiste o estado do ciclo de vida do Singleton em disco.
     */
    private function persistLifecycle(): void
    {
        $file = $this->getLifecycleFilePath();
        if (!$file) {
            return;
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'singleton_id' => self::$activeSingletonId,
            'born_at'      => microtime(true),
            'pids'         => self::$singletonPids,
        ];

        $this->writeJsonAtomically($file, $payload);
    }

    /**
     * Escreve JSON em disco de forma atômica com LOCK_EX para evitar leituras truncadas em concorrência (M-05).
     */
    private function writeJsonAtomically(string $file, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            return;
        }

        $tmpFile = $file . '.tmp.' . bin2hex(random_bytes(4));
        if (@file_put_contents($tmpFile, $json, LOCK_EX) !== false) {
            if (!@rename($tmpFile, $file)) {
                @file_put_contents($file, $json, LOCK_EX);
                @unlink($tmpFile);
            }
        } else {
            @file_put_contents($file, $json, LOCK_EX);
        }
    }

    /**
     * Retorna o caminho do arquivo de persistência do Singleton.
     */
    private function getLifecycleFilePath(): ?string
    {
        $base = $this->basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : null);
        if (!$base) {
            return null;
        }
        return rtrim($base, '/\\') . '/temp/kernel_singleton.json';
    }

    /**
     * Encerra o ciclo de vida do Singleton atual ("Morte do Singleton").
     * Limpa o estado em memória e remove o arquivo de persistência.
     * Na próxima inicialização, um novo Singleton nascerá com novos PIDs aleatórios.
     */
    public static function killSingleton(?string $basePath = null): void
    {
        self::$activeSingletonId = null;
        self::$singletonPids = [];

        $base = $basePath ?? (defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : null);
        if ($base) {
            $file = rtrim($base, '/\\') . '/temp/kernel_singleton.json';
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Alias para killSingleton().
     */
    public static function resetLifecycle(?string $basePath = null): void
    {
        self::killSingleton($basePath);
    }

    /**
     * Retorna o ID do Singleton ativo no momento.
     */
    public function getSingletonId(): string
    {
        $this->ensureSingletonLifecycle();
        return self::$activeSingletonId ?? '';
    }

    /**
     * Retorna o ID estático do Singleton ativo (se inicializado).
     */
    public static function getActiveSingletonId(): ?string
    {
        return self::$activeSingletonId;
    }

    /**
     * Retorna todos os PIDs específicos alocados para o Singleton ativo.
     *
     * @return array<string, string>
     */
    public function getSingletonPids(): array
    {
        $this->ensureSingletonLifecycle();
        return self::$singletonPids;
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
        $p['status']          = $finalStatus;
        $memDiff              = memory_get_usage() - $p['start_memory'];
        $kb                   = round(max(0.01, $memDiff / 1024), 2);
        $p['memory_used_kb']  = $kb;
        $p['peak_memory_kb']  = $kb;
        $p['memory_delta_kb'] = 0;
        $p['duration_ms']     = round((microtime(true) - $p['start_time']) * 1000, 2);
    }

    /**
     * Atualiza o consumo ou liberação de memória de um processo ativo.
     * Permite registrar oscilações dinâmicas de consumo (+ delta) e liberação (- delta).
     *
     * @param string $nameOrPid Nome do módulo ou PID
     * @param float  $memoryUsedKb Consumo atual em KB
     */
    public function updateProcessMemory(string $nameOrPid, float $memoryUsedKb): void
    {
        foreach ($this->processes as $pid => &$proc) {
            if ($pid === $nameOrPid || $proc['name'] === $nameOrPid) {
                $oldMem = $proc['memory_used_kb'] ?? 0;
                $proc['memory_delta_kb'] = round($memoryUsedKb - $oldMem, 2);
                $proc['memory_used_kb']  = round($memoryUsedKb, 2);
                $currentPeak             = $proc['peak_memory_kb'] ?? 0;
                $proc['peak_memory_kb']  = round(max($currentPeak, $memoryUsedKb), 2);
                $proc['last_updated']    = microtime(true);
                unset($proc);
                return;
            }
        }
        unset($proc);
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
     * Mantém o histórico de picos e calcula o delta de consumo/liberação de memória.
     */
    public function saveSnapshot(string $basePath): void
    {
        if ($this->basePath === null) {
            $this->basePath = $basePath;
        }
        $file = rtrim($basePath, '/\\') . '/temp/process_stack.json';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $previous = [];
        if (file_exists($file)) {
            $content = @file_get_contents($file);
            $previous = $content ? (json_decode($content, true) ?: []) : [];
        }

        foreach ($this->processes as $pid => &$proc) {
            if (isset($previous[$pid])) {
                $prevMem = $previous[$pid]['memory_used_kb'] ?? $proc['memory_used_kb'];
                $proc['memory_delta_kb'] = round($proc['memory_used_kb'] - $prevMem, 2);
                $prevPeak = $previous[$pid]['peak_memory_kb'] ?? $proc['memory_used_kb'];
                $proc['peak_memory_kb'] = round(max($prevPeak, $proc['memory_used_kb']), 2);
            } else {
                if (!isset($proc['peak_memory_kb'])) {
                    $proc['peak_memory_kb'] = $proc['memory_used_kb'];
                }
                if (!isset($proc['memory_delta_kb'])) {
                    $proc['memory_delta_kb'] = 0;
                }
            }
        }
        unset($proc);

        $this->writeJsonAtomically($file, $this->processes);
    }

    /**
     * Carrega o último snapshot salvo.
     */
    public function loadSnapshot(string $basePath): array
    {
        if ($this->basePath === null) {
            $this->basePath = $basePath;
        }
        $file = rtrim($basePath, '/\\') . '/temp/process_stack.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true) ?: [];
        }
        return [];
    }
}