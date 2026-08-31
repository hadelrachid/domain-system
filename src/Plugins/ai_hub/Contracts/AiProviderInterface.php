<?php

namespace DomainSystem\Plugins\ai_hub\Contracts;

interface AiProviderInterface
{
    /**
     * Set the system prompt context for the AI
     */
    public function setSystemContext(string $context): void;

    /**
     * Send a natural language prompt and return the response.
     */
    public function sendPrompt(string $prompt): string;
    
    // Future: public function registerTool(ToolInterface $tool): void;
}