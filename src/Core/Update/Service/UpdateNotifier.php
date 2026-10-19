<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Service;

use DomainSystem\Core\Contracts\NotificationManagerInterface;
use DomainSystem\Core\Contracts\Update\UpdateStateStoreInterface;
use DomainSystem\Core\Update\Enum\UpdateStatus;
use DomainSystem\Core\Update\Value\UpdateCheckResult;

final class UpdateNotifier
{
    public function __construct(
        private readonly UpdateStateStoreInterface $stateStore,
        private readonly ?NotificationManagerInterface $notifications = null,
    ) {
    }

    /**
     * Dispara uma notificação no painel administrativo caso haja nova versão disponível
     * e ela ainda não tenha sido notificada anteriormente.
     */
    public function notifyIfAvailable(UpdateCheckResult $result): bool
    {
        if (
            $this->notifications === null
            || $result->status !== UpdateStatus::Available
            || $result->release === null
        ) {
            return false;
        }

        $newVersion = $result->release->version;
        $state = $this->stateStore->load($result->target);
        $alreadyNotified = (string)($state->context['notified_version'] ?? '');

        if ($alreadyNotified === $newVersion) {
            return false;
        }

        $updatedState = $state->withContext(['notified_version' => $newVersion]);
        $this->stateStore->save($updatedState);

        $this->notifications->push(
            "Nova versão v{$newVersion} do Domain-System OS disponível para atualização.",
            'info',
            'SystemUpdater',
            [
                'version' => $newVersion,
                'tag'     => $result->release->tag,
            ],
        );

        return true;
    }
}
