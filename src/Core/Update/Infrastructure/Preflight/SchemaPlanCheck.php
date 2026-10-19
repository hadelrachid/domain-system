<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Infrastructure\Preflight;

use DomainSystem\Core\Contracts\Update\PreflightCheckInterface;
use DomainSystem\Core\Contracts\Update\SchemaPlannerInterface;
use DomainSystem\Core\Update\Enum\CheckLevel;
use DomainSystem\Core\Update\Value\PreflightCheck;
use DomainSystem\Core\Update\Value\UpdateTarget;
use DomainSystem\Core\Update\Value\VerifiedPackage;
use Throwable;

final class SchemaPlanCheck implements PreflightCheckInterface
{
    public function __construct(
        private readonly SchemaPlannerInterface $schemaPlanner,
    ) {
    }

    public function id(): string
    {
        return 'schema_plan';
    }

    public function run(VerifiedPackage $package, UpdateTarget $target): array
    {
        try {
            $plan = $this->schemaPlanner->plan($package->manifest, $package->extractedPath);
        } catch (Throwable $e) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Block,
                    message: sprintf('Falha no planejamento de schema: %s', $e->getMessage()),
                ),
            ];
        }

        if ($plan->blocked !== []) {
            return [
                new PreflightCheck(
                    id: $this->id(),
                    level: CheckLevel::Block,
                    message: sprintf(
                        'O banco de dados possui migrações já executadas que não existem neste pacote (%s). Downgrade de banco bloqueado.',
                        implode(', ', $plan->blocked)
                    ),
                ),
            ];
        }

        $checks = [
            new PreflightCheck(
                id: $this->id(),
                level: CheckLevel::Ok,
                message: sprintf('Plano de schema validado (%d migração(ões) pendente(s)).', count($plan->pending)),
            ),
        ];

        if ($plan->destructive) {
            $checks[] = new PreflightCheck(
                id: $this->id() . '_destructive',
                level: CheckLevel::Warn,
                message: 'O pacote inclui migrações marcadas como destrutivas; backup de banco de dados é obrigatório.',
            );
        }

        return $checks;
    }
}
