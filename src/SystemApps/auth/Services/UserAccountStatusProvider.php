<?php

declare(strict_types=1);

namespace DomainSystem\SystemApps\auth\Services;

use DomainSystem\Core\Contracts\AccountStatusProviderInterface;
use DomainSystem\SystemApps\auth\Contracts\UserRepositoryInterface;

class UserAccountStatusProvider implements AccountStatusProviderInterface
{
    private UserRepositoryInterface $userRepo;
    /** @var array<int, string> */
    private array $cache = [];

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function statusOf(int $userId): string
    {
        if ($userId <= 0) {
            return 'active';
        }

        if (isset($this->cache[$userId])) {
            return $this->cache[$userId];
        }

        try {
            $user = $this->userRepo->findById($userId);
            if ($user === null) {
                $status = 'banned';
            } else {
                $status = (string)($user['status'] ?? 'active');
            }
        } catch (\Throwable $e) {
            $status = 'active';
        }

        $this->cache[$userId] = $status;
        return $status;
    }
}
