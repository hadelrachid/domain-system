<?php

namespace DomainSystem\Plugins\test_os;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;
use DomainSystem\Core\Http\SessionManager;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    // Métodos Legados (ignorados pelo OS novo, mas exigidos pela classe abstrata)
    public function register(): void {}
    
    // ==========================================
    // 1. FASE DE NEGOCIAÇÃO
    // ==========================================
    public function osRegister(OsConnector $os): void
    {
        // Pede acesso à sessão
        $os->requireLink('core.session');
        
        // Solicita espaço no painel
        $os->requestSlot('admin.dashboard.alerts');
    }

    // ==========================================
    // 2. FASE DE EXECUÇÃO
    // ==========================================
    public function osBoot(OsRuntime $runtime): void
    {
        // O Runtime entrega o recurso com segurança!
        // No momento a gente ainda não cadastrou o core.session no registro de links global,
        // mas podemos fingir usando o container local só pra teste ou mock.
        
        // Para este teste de sucesso, vamos registrar uma mensagem no log!
        error_log("🚀 BINGO! O Plugin TestOS foi aprovado, negociou recursos e bootou pelo novo motor OsRuntime!");
        
        // Aqui injetaríamos no Slot
        $runtime->contributeTo('admin.dashboard.alerts', [
            'type' => 'success',
            'message' => 'O novo Sistema Operacional Web está operante!'
        ]);
    }
}
