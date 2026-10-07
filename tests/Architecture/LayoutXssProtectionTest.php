<?php
namespace DomainSystem\Tests\Architecture;

use PHPUnit\Framework\TestCase;

class LayoutXssProtectionTest extends TestCase
{
    public function testAdminLayoutDoesNotUseInnerHTMLForMessages()
    {
        $layoutPath = DOMAIN_SYSTEM_ROOT . '/themes/admin/layout.php';
        $this->assertFileExists($layoutPath);
        
        $content = file_get_contents($layoutPath);
        
        // Em addToHub, não podemos ter a variável message sendo interpolada dentro de innerHTML
        // Ex: item.innerHTML = `... ${message} ...`
        
        // Verifica se a função addToHub usa textContent para definir a mensagem
        $hasTextContent = strpos($content, ".textContent = message;") !== false || strpos($content, ".innerText = message;") !== false;
        
        $this->assertTrue(
            $hasTextContent, 
            'O arquivo layout.php está vulnerável a XSS. Ele deve usar textContent para injetar a variável "message" no DOM.'
        );
        
        // Adicionalmente, verificar se não há `${message}` logo após innerHTML
        preg_match('/innerHTML\s*=\s*`[^`]*\$\{message\}[^`]*`/i', $content, $matches);
        $this->assertEmpty($matches, 'Foi detectada injeção direta da variável message via innerHTML (Risco de XSS).');
    }
}
