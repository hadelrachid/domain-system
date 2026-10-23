<?php

declare(strict_types=1);

namespace Tests\Unit;

use DomainSystem\Core\Theme\Parts\Blocks\ButtonBlock;
use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Blocks\NavMenuBlock;
use DomainSystem\Core\Theme\Parts\Models\DesignTokens;
use PHPUnit\Framework\TestCase;

class ButtonBlockAndInspectorTokensTest extends TestCase
{
    public function testButtonBlockMetadataAndRegistry(): void
    {
        $block = new ButtonBlock();
        $this->assertSame('button', $block->getType());
        $this->assertSame('Botão Genérico', $block->getName());
        $this->assertSame('🔘', $block->getIcon());
        $this->assertSame('action', $block->getCategory());

        // Verifica registro na Factory padrão
        $registry = DefaultBlockRegistryFactory::create();
        $this->assertNotNull($registry->get('button'));
        $this->assertNotNull($registry->get('cta_button'));
        $this->assertInstanceOf(ButtonBlock::class, $registry->get('button'));
    }

    public function testButtonBlockDefaultLinkRender(): void
    {
        $block = new ButtonBlock();
        $html = $block->render([
            'label' => 'Acessar Plataforma',
            'url'   => 'https://exemplo.com',
        ]);

        $this->assertStringContainsString('Acessar Plataforma', $html);
        $this->assertStringContainsString('href="https://exemplo.com"', $html);
        $this->assertStringContainsString('ds-block-btn', $html);
        $this->assertStringContainsString('ds-btn-lift', $html); // Animação padrão
    }

    public function testButtonBlockScrollToAnchorAction(): void
    {
        $block = new ButtonBlock();
        $html = $block->render([
            'label'       => 'Ir para Contato',
            'action_type' => ButtonBlock::ACTION_SCROLL,
            'url'         => '#contato',
        ]);

        $this->assertStringContainsString('href="#contato"', $html);
        $this->assertStringContainsString('<a ', $html);
    }

    public function testButtonBlockWhatsAppAction(): void
    {
        $block = new ButtonBlock();
        $html = $block->render([
            'label'            => 'Fale Conosco no Zap',
            'action_type'      => ButtonBlock::ACTION_WHATSAPP,
            'url'              => '+55 (11) 99999-8888',
            'whatsapp_message' => 'Olá! Gostaria de um orçamento.',
        ]);

        $this->assertStringContainsString('href="https://wa.me/5511999998888?text=Ol%C3%A1%21+Gostaria+de+um+or%C3%A7amento."', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testButtonBlockTelAndEmailActions(): void
    {
        $block = new ButtonBlock();

        $telHtml = $block->render([
            'label'       => 'Ligar Agora',
            'action_type' => ButtonBlock::ACTION_TEL,
            'url'         => '+55 11 3333-4444',
        ]);
        $this->assertStringContainsString('href="tel:+551133334444"', $telHtml);

        $emailHtml = $block->render([
            'label'       => 'Envie um Email',
            'action_type' => ButtonBlock::ACTION_EMAIL,
            'url'         => 'comercial@empresa.com',
        ]);
        $this->assertStringContainsString('href="mailto:comercial@empresa.com"', $emailHtml);
    }

    public function testButtonBlockModalAndJavaScriptActions(): void
    {
        $block = new ButtonBlock();

        $modalHtml = $block->render([
            'label'       => 'Abrir Proposta',
            'action_type' => ButtonBlock::ACTION_MODAL,
            'url'         => 'modal_lead_form',
        ]);
        $this->assertStringContainsString('<button type="button"', $modalHtml);
        $this->assertStringContainsString('data-ds-modal="modal_lead_form"', $modalHtml);

        $jsHtml = $block->render([
            'label'       => 'Executar Teste',
            'action_type' => ButtonBlock::ACTION_JAVASCRIPT,
            'url'         => 'console.log("clicado");',
        ]);
        $this->assertStringContainsString('<button type="button"', $jsHtml);
        $this->assertStringContainsString('onclick="console.log(&quot;clicado&quot;);"', $jsHtml);
    }

    public function testButtonBlockSubmitAction(): void
    {
        $block = new ButtonBlock();
        $html = $block->render([
            'label'       => 'Finalizar Pedido',
            'action_type' => ButtonBlock::ACTION_SUBMIT,
        ]);

        $this->assertStringContainsString('<button type="submit"', $html);
        $this->assertStringContainsString('Finalizar Pedido', $html);
    }

    public function testButtonBlockHoverAnimations(): void
    {
        $block = new ButtonBlock();

        $growHtml = $block->render(['hover_animation' => 'grow']);
        $this->assertStringContainsString('ds-btn-grow', $growHtml);

        $glowHtml = $block->render(['hover_animation' => 'glow']);
        $this->assertStringContainsString('ds-btn-glow', $glowHtml);

        $shimmerHtml = $block->render(['hover_animation' => 'shimmer']);
        $this->assertStringContainsString('ds-btn-shimmer', $shimmerHtml);

        $bounceHtml = $block->render(['hover_animation' => 'bounce']);
        $this->assertStringContainsString('ds-btn-bounce', $bounceHtml);

        $staticHtml = $block->render(['hover_animation' => 'none']);
        $this->assertStringContainsString('ds-btn-static', $staticHtml);
    }

    public function testButtonBlockIconsAndSubtext(): void
    {
        $block = new ButtonBlock();
        $html = $block->render([
            'label'         => 'Download Grátis',
            'subtext'       => 'Versão 2.4 - 15MB',
            'icon'          => '📥',
            'icon_position' => 'left',
        ]);

        $this->assertStringContainsString('📥', $html);
        $this->assertStringContainsString('Download Grátis', $html);
        $this->assertStringContainsString('Versão 2.4 - 15MB', $html);
        $this->assertStringContainsString('ds-btn-subtext', $html);
    }

    public function testNavMenuBlockWithHoverAndTokens(): void
    {
        $block = new NavMenuBlock();
        $html = $block->render([
            'hover_animation' => 'pill_glow',
            'link_color'      => 'var(--color-text-main)',
            'active_color'    => 'var(--color-primary)',
        ]);

        $this->assertStringContainsString('ds-nav-anim-pill', $html);
        $this->assertStringContainsString('var(--color-text-main)', $html);
        $this->assertStringContainsString('var(--color-primary)', $html);
    }

    public function testDesignTokensTertiaryFontAndBorderPalette(): void
    {
        $tmpFile = sys_get_temp_dir() . '/test_tokens_' . uniqid() . '.json';
        $tokens = new DesignTokens($tmpFile);

        $palette = $tokens->getColorPalette();
        $this->assertArrayHasKey('border', $palette);

        $typo = $tokens->getTypography();
        $this->assertArrayHasKey('tertiary_font', $typo);

        $css = $tokens->generateCssVariables();
        $this->assertStringContainsString('--font-tertiary:', $css);
        $this->assertStringContainsString('--color-border:', $css);

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }
}
