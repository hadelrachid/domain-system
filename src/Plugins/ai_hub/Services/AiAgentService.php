<?php

namespace DomainSystem\Plugins\ai_hub\Services;

use DomainSystem\Plugins\ai_hub\Contracts\AiProviderInterface;

class AiAgentService
{
    private AiProviderInterface $provider;

    public function __construct(AiProviderInterface $provider)
    {
        $this->provider = $provider;
        
        // Define the strict rules for the Agent
        $this->provider->setSystemContext(
            "You are the autonomous AI Agent managing the Clinic System.\n" .
            "You MUST NOT execute direct SQL. You MUST only use the injected Repository Interfaces."
        );
    }

    public function executeTask(string $userIntent): string
    {
        return $this->provider->sendPrompt($userIntent);
    }
}
