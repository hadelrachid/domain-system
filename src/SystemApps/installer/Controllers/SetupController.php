<?php

namespace DomainSystem\SystemApps\installer\Controllers;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\PasswordPolicyInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Plugin\LinkRegistry;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\SystemApps\Database\Connection;

/**
 * Wizard de instalação.
 *
 * Cada etapa é ISOLADA: qualquer falha vira uma mensagem na própria tela do
 * wizard (e uma linha em temp/install.log) em vez de explodir como
 * "Falha de Núcleo Interceptada".
 */
class SetupController
{
    private PluginManager $manager;
    private ContainerInterface $container;
    private \DomainSystem\SystemApps\installer\Services\InstallerService $service;

    /** Tabelas sem as quais o sistema não sobe (criadas por SystemApps\auth). */
    private const ESSENTIAL_TABLES = ['users', 'roles', 'user_roles', 'capabilities'];

    public function __construct(
        PluginManager $manager,
        ContainerInterface $container,
        ?\DomainSystem\SystemApps\installer\Services\InstallerService $service = null
    ) {
        $this->manager = $manager;
        $this->container = $container;
        $this->service = $service ?? new \DomainSystem\SystemApps\installer\Services\InstallerService();
    }

    public function step1(Request $request): Response
    {
        \DomainSystem\Core\Bootstrap\RuntimeDirectories::ensure(DOMAIN_SYSTEM_ROOT);
        try {
            $this->service->getOrCreateSetupToken();
        } catch (\Exception $e) {
            return $this->renderStep('step1', $e->getMessage());
        }
        
        return $this->renderStep('step1');
    }

    // =====================================================================
    // PASSO 1 → 2: valida o servidor, testa a conexão e grava o .env
    // =====================================================================
    public function step2_database(Request $request): Response
    {
        $token = $request->input('setup_token', '');
        
        if (!$this->service->validateSetupToken($token)) {
            return $this->renderStep('step1', 'O Setup Token inserido é inválido. Verifique o arquivo token/setup.token.');
        }

        if ($this->preflight()) {
            return $this->renderStep('step1', 'O servidor não atende aos requisitos listados acima. Corrija e tente novamente.');
        }

        $driver = $request->input('db_driver') === 'sqlite' ? 'sqlite' : 'mysql';
        $host   = trim((string) $request->input('db_host', 'localhost'));
        $port   = trim((string) $request->input('db_port', '3306'));
        $name   = trim((string) $request->input('db_name', ''));
        $user   = trim((string) $request->input('db_user', ''));
        $pass   = (string) $request->input('db_pass', '');

        if ($driver === 'sqlite') {
            if (!extension_loaded('pdo_sqlite')) {
                return $this->renderStep('step1', 'A extensão PHP pdo_sqlite não está habilitada neste servidor.');
            }
        } else {
            if (!extension_loaded('pdo_mysql')) {
                return $this->renderStep('step1', 'A extensão PHP pdo_mysql não está habilitada.');
            }
            if ($name === '' || $user === '') {
                return $this->renderStep('step1', 'Informe o nome do banco e o usuário do banco.');
            }
        }

        try {
            // Delega a montagem e teste da conexão
            $result = $this->service->testConnection($driver, $host, $port, $name, $user, $pass);
            $dsn = $result['dsn'];
        } catch (\InvalidArgumentException $e) {
            return $this->renderStep('step1', $e->getMessage());
        } catch (\PDOException $e) {
            $this->log('step2-conexao', $e);
            return $this->renderStep('step1', 'Falha na conexão: ' . $this->explainPdoError($e));
        }

        // 2) Grava o .env de forma segura
        try {
            $this->service->writeEnvFile($dsn, $user, $pass);
        } catch (\RuntimeException $e) {
            return $this->renderStep('step1', $e->getMessage());
        }

        // 3) O que o bootstrap vai LER é igual ao que a pessoa digitou?
        //    Se não for, o sistema cairia silenciosamente no SQLite no próximo request.
        foreach (['DB_DSN' => $dsn, 'DB_USER' => $user, 'DB_PASS' => $pass] as $key => $expected) {
            if ($this->readEnvValue($key) !== $expected) {
                return $this->renderStep('step1', "O arquivo .env não conseguiu preservar o valor de {$key} (a senha contém caracteres que o .env não suporta?). Tente outra senha de banco.");
            }
        }

        // 4) Banco novo => o log de migrações do banco anterior não vale mais.
        //    (Antes isso era feito no passo 3, tarde demais: o boot daquele request já tinha pulado as migrações.)
        $migrations = DOMAIN_SYSTEM_ROOT . '/temp/migrations.json';
        if (file_exists($migrations)) {
            @unlink($migrations);
        }

        return $this->renderStep('step2');
    }

    // =====================================================================
    // PASSO 2 → fim: cria schema, administrador e trava o instalador
    // =====================================================================
    public function step3_install(Request $request): Response
    {
        $lockFile = DOMAIN_SYSTEM_ROOT . '/config/installed.lock';
        if (file_exists($lockFile)) {
            return Response::redirect(BASE_URL . '/admin');
        }

        $adminName  = trim((string) $request->input('admin_name', ''));
        $adminEmail = trim((string) $request->input('admin_email', ''));
        $adminPass  = (string) $request->input('admin_pass', '');

        // --- Etapa A: entrada do usuário -----------------------------------
        if ($adminName === '' || $adminEmail === '' || $adminPass === '') {
            return $this->renderStep('step2', 'Preencha todos os campos do administrador.');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->renderStep('step2', 'E-mail inválido.');
        }
        $policy = $this->container->make(PasswordPolicyInterface::class);
        if (!$policy->isAcceptable($adminPass)) {
            return $this->renderStep('step2', 'Senha fraca. Faltando: ' . implode(', ', $policy->getMissingRequirements($adminPass)));
        }

        // --- Etapa B: o passo 1 foi realmente concluído? --------------------
        if (!file_exists(DOMAIN_SYSTEM_ROOT . '/.env') || !getenv('DB_DSN')) {
            return $this->renderStep('step1', 'Configuração do banco não encontrada. Refaça o passo 1.');
        }
        try {
            $db = $this->container->make(Connection::class)->getPdo();
        } catch (\Throwable $e) {
            $this->log('step3-conexao', $e);
            return $this->renderStep('step1', 'Não foi possível conectar ao banco configurado: ' . $e->getMessage());
        }

        // --- Etapa C: schema (activate() de cada plugin) --------------------
        $failures = $this->activatePlugins();

        $missing = $this->missingTables($db, self::ESSENTIAL_TABLES);
        if ($missing) {
            $detail = $failures ? ' Detalhes: ' . implode(' | ', $failures) : ' Veja temp/install.log.';
            return $this->renderStep('step2', 'As tabelas essenciais não foram criadas (' . implode(', ', $missing) . ').' . $detail);
        }
        foreach ($failures as $plugin => $message) {
            // Plugins opcionais não impedem a instalação, mas ficam registrados.
            $this->log("activate:{$plugin}", new \RuntimeException($message));
        }

        // --- Etapa D: conta do administrador (transação) --------------------
        $skipLogin = false;
        try {
            $userId = $this->createAdmin($db, $adminName, $adminEmail, $adminPass);
        } catch (\RuntimeException $e) {
            if (str_contains($e->getMessage(), 'existe neste banco')) {
                // Banco já tem administrador. Pula criação e login, mas finaliza instalação com sucesso.
                $skipLogin = true;
            } else {
                $this->log('step3-admin', $e);
                return $this->renderStep('step2', 'Não foi possível criar o administrador: ' . $e->getMessage());
            }
        } catch (\Throwable $e) {
            $this->log('step3-admin', $e);
            $msg = ($e instanceof \PDOException && ($e->errorInfo[0] ?? '') === '23000')
                ? 'Já existe um usuário com este e-mail.'
                : 'Não foi possível criar o administrador: ' . $e->getMessage();
            return $this->renderStep('step2', $msg);
        }

        // --- Etapa E: login automático (best-effort, não derruba a instalação)
        if (!$skipLogin) {
            try {
                $session = $this->container->make(SessionManagerInterface::class);
                $session->regenerate();
                $session->set('user_id', $userId);
                $session->set('user_name', $adminName);
                $session->set('user_role', 'admin');
                $session->remove('auth_error');
            } catch (\Throwable $e) {
                $this->log('step3-login', $e);
            }
        }

        // --- Etapa F: trava o instalador -----------------------------------
        // Sem o lock, /setup continuaria aberto e qualquer um poderia trocar o admin.
        if (@file_put_contents($lockFile, date('Y-m-d H:i:s')) === false) {
            return $this->renderStep('step2', 'Instalação concluída, mas não foi possível criar config/installed.lock. Crie esse arquivo manualmente (vazio) antes de usar o sistema, senão o instalador continuará aberto.');
        }

        return $this->renderStep('success', null, ['existingAdmin' => $skipLogin]);
    }

    // =====================================================================
    // Internos
    // =====================================================================

    /**
     * Roda o activate() de cada plugin com um OsRuntime real.
     *
     * É o único ponto do sistema que cria o schema (users, roles, pages, settings...).
     * Replica a Fase 1 do PluginBootstrapper (osRegister → LinkRegistry) porque
     * OsRuntime::getLink() só libera links que o plugin declarou, e 'core.db.schema'
     * é fornecido pelo plugin Database.
     *
     * @return array<string,string> plugin => mensagem de erro
     */
    private function activatePlugins(): array
    {
        $failures = [];
        $rank = static function ($plugin): int {
            $class = get_class($plugin);
            if ($class === 'DomainSystem\\SystemApps\\auth\\Plugin') return 0; // cria users/roles/capabilities
            if (str_starts_with($class, 'DomainSystem\\SystemApps\\')) return 1;
            return 2; // plugins de usuário (dependem das tabelas acima)
        };

        $plugins = $this->manager->getPlugins();
        uasort($plugins, static fn($a, $b) => $rank($a) <=> $rank($b));

        $linkRegistry = new LinkRegistry($this->container);
        $dispatcher   = $this->container->make(EventDispatcherInterface::class);

        // Fase 1: manifestos (quem requer / quem fornece cada link)
        $connectors = [];
        foreach ($plugins as $name => $plugin) {
            $connector = new OsConnector();
            try {
                if ($plugin instanceof OsExtensionInterface) {
                    $plugin->osRegister($connector);
                    $linkRegistry->registerConnector($name, $connector);
                }
                $connectors[$name] = $connector;
            } catch (\Throwable $e) {
                $failures[$name] = 'osRegister: ' . $e->getMessage();
            }
        }

        // Fase 2: activate() na ordem de dependência
        foreach ($plugins as $name => $plugin) {
            if (!isset($connectors[$name])) continue;
            $namespace = implode('\\', array_slice(explode('\\', get_class($plugin)), 0, 3));
            $runtime = new OsRuntime($this->container, $connectors[$name], $linkRegistry, $dispatcher, $namespace);
            try {
                $plugin->activate($runtime);
            } catch (\Throwable $e) {
                $failures[$name] = 'activate: ' . $e->getMessage();
            }
        }

        return $failures;
    }

    /** Cria (ou substitui) o administrador e o vincula ao papel RBAC 'admin'. */
    private function createAdmin(\PDO $db, string $name, string $email, string $password): int
    {
        $db->beginTransaction();
        try {
            // SEGURANÇA CRÍTICA: Bloqueia a criação se já existir um admin!
            // Evita que um invasor ou um erro de lock resete o administrador.
            $existingAdmin = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
            if ($existingAdmin) {
                throw new \RuntimeException("Um administrador já existe neste banco de dados. Instalação bloqueada por segurança.");
            }

            $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_BCRYPT)]);
            $userId = (int) $db->lastInsertId();

            // O ACL (IdentityManager::hasRole) lê user_roles, não users.role. A role foi semeada
            // em activate(), antes deste usuário existir; sem este vínculo o admin ficaria sem permissões.
            $roleId = $db->query("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();
            if ($roleId) {
                $db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)")->execute([$userId, $roleId]);
            }

            $db->commit();
            return $userId;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /** @return string[] tabelas que não existem */
    private function missingTables(\PDO $db, array $tables): array
    {
        $missing = [];
        foreach ($tables as $table) {
            try {
                $db->query("SELECT 1 FROM {$table} LIMIT 1");
            } catch (\Throwable $e) {
                $missing[] = $table;
            }
        }
        return $missing;
    }

    /** Requisitos do servidor. Retorna a lista de problemas (vazia = tudo certo). */
    private function preflight(): array
    {
        $problems = [];
        if (PHP_VERSION_ID < 80000) {
            $problems[] = 'PHP 8.0 ou superior é necessário (versão atual: ' . PHP_VERSION . ').';
        }
        foreach (['pdo', 'mbstring', 'openssl', 'json'] as $ext) {
            if (!extension_loaded($ext)) {
                $problems[] = "Extensão PHP ausente: {$ext}.";
            }
        }

        $env = DOMAIN_SYSTEM_ROOT . '/.env';
        if (file_exists($env) ? !is_writable($env) : !is_writable(DOMAIN_SYSTEM_ROOT)) {
            $problems[] = 'Sem permissão de escrita para criar/atualizar o arquivo .env na raiz do projeto.';
        }
        foreach (['config', 'temp'] as $dir) {
            $path = DOMAIN_SYSTEM_ROOT . '/' . $dir;
            if (!is_dir($path) && !@mkdir($path, 0755, true)) {
                $problems[] = "Não foi possível criar a pasta /{$dir}.";
            } elseif (!is_writable($path)) {
                $problems[] = "A pasta /{$dir} não tem permissão de escrita.";
            }
        }
        return $problems;
    }

    /**
     * Atualiza/insere chaves no .env sem apagar as demais (APP_KEY, EMERGENCY_KEY, comentários...).
     *
     * @param array<string,string> $values        sempre gravadas
     * @param array<string,string> $onlyIfMissing gravadas apenas se a chave ainda não existir
     */
    private function writeEnv(array $values, array $onlyIfMissing = []): bool
    {
        $file  = DOMAIN_SYSTEM_ROOT . '/.env';
        $lines = file_exists($file) ? preg_split('/\R/', rtrim((string) file_get_contents($file))) : [];
        if ($lines === [''] || $lines === false) {
            $lines = [];
        }

        $seen = [];
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=/', $line, $m)) {
                $seen[$m[1]] = true;
                if (array_key_exists($m[1], $values)) {
                    $lines[$i] = $m[1] . '="' . $values[$m[1]] . '"';
                }
            }
        }
        foreach ($values as $key => $value) {
            if (!isset($seen[$key])) {
                $lines[] = $key . '="' . $value . '"';
            }
        }
        foreach ($onlyIfMissing as $key => $value) {
            if (!isset($seen[$key])) {
                $lines[] = $key . '="' . $value . '"';
            }
        }

        return @file_put_contents($file, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX) !== false;
    }

    /** Lê uma chave do .env exatamente como o bootstrap.php faz (mantenha em sincronia). */
    private function readEnvValue(string $key): ?string
    {
        $vars = @parse_ini_file(DOMAIN_SYSTEM_ROOT . '/.env', false, INI_SCANNER_RAW);
        if (!is_array($vars) || !array_key_exists($key, $vars)) {
            return null;
        }
        $value = (string) $vars[$key];
        if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'") && substr($value, -1) === $value[0]) {
            $value = substr($value, 1, -1);
        }
        return $value;
    }

    /** Traduz erros comuns do MySQL para algo acionável. */
    private function explainPdoError(\PDOException $e): string
    {
        $message = $e->getMessage();
        $code = preg_match('/\[(\d{4})\]/', $message, $m) ? (int) $m[1] : 0;

        $hint = match ($code) {
            1045 => 'Usuário ou senha do banco incorretos.',
            1049 => 'O banco informado não existe. Na Hostinger o nome costuma ter prefixo (ex.: u123456789_nome).',
            2002, 2003, 2006 => 'Não foi possível alcançar o servidor MySQL. Na Hostinger o host normalmente é "localhost".',
            default => '',
        };

        return $hint !== '' ? "{$hint} ({$message})" : $message;
    }

    private function renderStep(string $view, ?string $error = null, array $data = []): Response
    {
        $preflight = $this->preflight();
        extract($data);
        ob_start();
        include dirname(__DIR__) . '/views/' . $view . '.php';
        return new Response(ob_get_clean());
    }

    /** Registra a falha em temp/install.log (nunca grava senhas). */
    private function log(string $stage, \Throwable $e): void
    {
        $dir = DOMAIN_SYSTEM_ROOT . '/temp';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/install.log',
            sprintf("[%s] [%s] %s: %s (%s:%d)\n", date('Y-m-d H:i:s'), $stage, get_class($e), $e->getMessage(), basename($e->getFile()), $e->getLine()),
            FILE_APPEND | LOCK_EX
        );
    }
}
