<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\DatabaseConnectionInterface;
use DomainSystem\Core\Contracts\JsonStoreInterface;
use DomainSystem\Core\Contracts\RouterInterface;
use DomainSystem\Core\Contracts\SchemaDoctorInterface;
use DomainSystem\Core\Contracts\SessionManagerInterface;
use DomainSystem\Core\Contracts\Update\HealthCheckInterface;
use DomainSystem\Core\Contracts\Update\InstalledStateProviderInterface;
use DomainSystem\Core\Contracts\Update\SchemaVerifierInterface;
use DomainSystem\Core\Contracts\Update\UpdateManifestParserInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Storage\AtomicJsonStore;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\HealthReport;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use Throwable;

/**
 * Verificação de saúde pós-atualização do núcleo (executada em requisição HTTP nova).
 */
final class CoreHealthCheck implements HealthCheckInterface
{
    private string $rootPath;
    private ContainerInterface $container;
    private InstalledStateProviderInterface $installedState;
    private ?UpdateStateStoreInterface $stateStore;
    private ?SchemaVerifierInterface $schemaVerifier;
    private ?UpdateManifestParserInterface $manifestParser;
    private JsonStoreInterface $jsonStore;

    public function __construct(
        string $rootPath,
        ContainerInterface $container,
        InstalledStateProviderInterface $installedState,
        ?UpdateStateStoreInterface $stateStore = null,
        ?SchemaVerifierInterface $schemaVerifier = null,
        ?UpdateManifestParserInterface $manifestParser = null,
        ?JsonStoreInterface $jsonStore = null,
    ) {
        $this->rootPath = rtrim(str_replace('\\', '/', $rootPath), '/');
        $this->container = $container;
        $this->installedState = $installedState;
        $this->stateStore = $stateStore;
        $this->schemaVerifier = $schemaVerifier;
        $this->manifestParser = $manifestParser;
        $this->jsonStore = $jsonStore ?? new AtomicJsonStore();
    }

    public function run(UpdateTarget $target): HealthReport
    {
        $checks = [];
        $allOk = true;

        $state = $this->stateStore?->load($target);
        $expectedVersion = $state?->release?->version ?? $target->installedVersion;
        $activeVersion = $this->installedState->coreVersion();

        // 1. Versão ativa carregada == versão alvo
        if ($activeVersion === $expectedVersion) {
            $checks[] = new PreflightCheck(
                id: 'core_version',
                level: CheckLevel::Ok,
                message: sprintf('Versão do núcleo carregada com sucesso (%s).', $activeVersion),
            );
        } else {
            $allOk = false;
            $checks[] = new PreflightCheck(
                id: 'core_version',
                level: CheckLevel::Block,
                message: sprintf(
                    'Versão ativa (%s) diverge da versão alvo esperada (%s).',
                    $activeVersion,
                    $expectedVersion
                ),
            );
        }

        // 2. Contratos essenciais resolvem no Container
        $requiredContracts = [
            RouterInterface::class,
            SessionManagerInterface::class,
            DatabaseConnectionInterface::class,
            SchemaDoctorInterface::class,
        ];

        $missingContracts = [];
        foreach ($requiredContracts as $contract) {
            try {
                if (!$this->container->has($contract)) {
                    $missingContracts[] = $contract;
                    continue;
                }
                $resolved = $this->container->make($contract);
                if (!is_object($resolved)) {
                    $missingContracts[] = $contract;
                }
            } catch (Throwable) {
                $missingContracts[] = $contract;
            }
        }

        if ($missingContracts === []) {
            $checks[] = new PreflightCheck(
                id: 'container_contracts',
                level: CheckLevel::Ok,
                message: 'Todos os contratos vitais do Kernel resolveram no Container.',
            );
        } else {
            $allOk = false;
            $checks[] = new PreflightCheck(
                id: 'container_contracts',
                level: CheckLevel::Block,
                message: sprintf('Falha ao resolver contratos no Container: %s.', implode(', ', $missingContracts)),
            );
        }

        // 3. Conexão com o banco de dados responde
        $dbOk = false;
        try {
            if ($this->container->has(DatabaseConnectionInterface::class)) {
                /** @var DatabaseConnectionInterface $conn */
                $conn = $this->container->make(DatabaseConnectionInterface::class);
                $stmt = $conn->getPdo()->query('SELECT 1');
                $dbOk = $stmt !== false;
            }
        } catch (Throwable) {
            $dbOk = false;
        }

        if ($dbOk) {
            $checks[] = new PreflightCheck(
                id: 'database_connection',
                level: CheckLevel::Ok,
                message: 'Conexão com o banco de dados ativa e respondendo.',
            );
        } else {
            $allOk = false;
            $checks[] = new PreflightCheck(
                id: 'database_connection',
                level: CheckLevel::Block,
                message: 'Falha ao consultar a conexão ativa com o banco de dados.',
            );
        }

        // 4. SchemaVerifier (se houver manifest disponível na quarentena ou no contexto)
        if ($this->schemaVerifier !== null && $this->manifestParser !== null && $state !== null) {
            $extractedPath = isset($state->context['extracted_path']) && is_string($state->context['extracted_path'])
                ? $state->context['extracted_path']
                : null;
            $manifestFile = $extractedPath !== null ? rtrim($extractedPath, '/\\') . '/manifest.json' : null;

            if ($manifestFile !== null && is_file($manifestFile)) {
                try {
                    $manifest = $this->manifestParser->parse((string)file_get_contents($manifestFile));
                    $verification = $this->schemaVerifier->verify($manifest);
                    if ($verification->ok) {
                        $checks[] = new PreflightCheck(
                            id: 'schema_verifier',
                            level: CheckLevel::Ok,
                            message: 'Estrutura do banco de dados verificada conforme o manifest.',
                        );
                    } else {
                        $allOk = false;
                        $checks[] = new PreflightCheck(
                            id: 'schema_verifier',
                            level: CheckLevel::Block,
                            message: 'Divergência na verificação de schema pós-atualização.',
                        );
                    }
                } catch (Throwable $e) {
                    $allOk = false;
                    $checks[] = new PreflightCheck(
                        id: 'schema_verifier',
                        level: CheckLevel::Block,
                        message: 'Erro ao verificar schema pós-atualização: ' . $e->getMessage(),
                    );
                }
            }
        }

        // 5. config/plugins.json legível
        $pluginsFile = $this->rootPath . '/config/plugins.json';
        if (is_file($pluginsFile) && is_readable($pluginsFile)) {
            $pluginsData = $this->jsonStore->read($pluginsFile, []);
            if ($pluginsData !== []) {
                $checks[] = new PreflightCheck(
                    id: 'plugins_config',
                    level: CheckLevel::Ok,
                    message: 'Arquivo config/plugins.json íntegro e legível.',
                );
            } else {
                $allOk = false;
                $checks[] = new PreflightCheck(
                    id: 'plugins_config',
                    level: CheckLevel::Block,
                    message: 'Arquivo config/plugins.json está vazio ou corrompido.',
                );
            }
        } else {
            $allOk = false;
            $checks[] = new PreflightCheck(
                id: 'plugins_config',
                level: CheckLevel::Block,
                message: 'Arquivo config/plugins.json não encontrado ou ilegível.',
            );
        }

        return new HealthReport(
            ok: $allOk,
            checks: $checks,
        );
    }
}
