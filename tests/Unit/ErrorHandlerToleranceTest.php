<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Error\ErrorHandler;
use DomainSystem\Core\Error\ErrorLogger;
use DomainSystem\Core\Error\CircuitBreaker;
use DomainSystem\Core\Error\ErrorRenderer;

class ErrorHandlerToleranceTest extends TestCase
{
    public function testNonFatalWarningDoesNotRenderErrorInProduction(): void
    {
        $logger = $this->createMock(ErrorLogger::class);
        $circuitBreaker = $this->createMock(CircuitBreaker::class);
        $renderer = $this->createMock(ErrorRenderer::class);

        // O renderer NUNCA deve ser acionado para simples avisos/warnings em produção
        $renderer->expects($this->never())->method('render');

        $errorHandler = new ErrorHandler($logger, $circuitBreaker, $renderer);

        // Simula ambiente de produção onde APP_ENV não é 'development'
        putenv('APP_ENV=production');
        $_ENV['APP_ENV'] = 'production';

        $previousReporting = error_reporting(E_ALL);
        try {
            // Dispara um warning (nível E_USER_WARNING ou E_WARNING)
            $handled = $errorHandler->handleError(E_USER_WARNING, 'Teste de aviso não-fatal', __FILE__, __LINE__);
            $this->assertTrue($handled, 'O aviso deve ser tratado silenciosamente retornando true.');
        } finally {
            error_reporting($previousReporting);
        }
    }
}
