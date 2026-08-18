<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Theme\ThemeManager;
use Exception;

class ThemeManagerTest extends TestCase
{
    private string $dummyThemePath = __DIR__ . '/../UI/dummy_theme';

    public function testRenderTemplate()
    {
        $themeManager = new ThemeManager($this->dummyThemePath);

        // Rendering 'index' passing variables
        $html = $themeManager->render('index', [
            'title' => 'Test Title',
            'content' => 'Test Content'
        ]);

        $this->assertStringContainsString('<header>Header: Test Title</header>', $html);
        $this->assertStringContainsString('<main>Content: Test Content</main>', $html);
        $this->assertStringContainsString('<footer>Footer</footer>', $html);
    }

    public function testFallbackTemplate()
    {
        $themeManager = new ThemeManager($this->dummyThemePath);

        // Try to render a non-existent template, it should look for index.php as fallback
        // Or throw exception depending on design. Let's make it throw exception if not found, 
        // but render has a strict mode and fallback mode. We'll stick to simple file inclusion for now.
        $this->expectException(Exception::class);
        $themeManager->render('non_existent');
    }

    public function testGetHeaderFunction()
    {
        $themeManager = new ThemeManager($this->dummyThemePath);
        
        ob_start();
        $themeManager->get_header(['title' => 'Header Only']);
        $html = ob_get_clean();
        
        $this->assertEquals('<header>Header: Header Only</header>', trim($html));
    }

    public function testGetFooterFunction()
    {
        $themeManager = new ThemeManager($this->dummyThemePath);
        
        ob_start();
        $themeManager->get_footer();
        $html = ob_get_clean();
        
        $this->assertEquals('<footer>Footer</footer>', trim($html));
    }
}
