<?php

declare(strict_types=1);

namespace DomainSystem\Tests\Unit;

use DomainSystem\Core\Container\Container;
use DomainSystem\Core\Events\EventDispatcher;
use DomainSystem\Core\Plugin\LinkRegistry;
use DomainSystem\Core\Plugin\OsConnector;
use DomainSystem\Core\Plugin\OsRuntime;
use DomainSystem\Core\Theme\Parts\Blocks\DefaultBlockRegistryFactory;
use DomainSystem\Core\Theme\Parts\Contracts\BlockRegistryInterface;
use DomainSystem\Plugins\academy\Blocks\AcademyBlockProvider;
use DomainSystem\Plugins\academy\Plugin as AcademyPlugin;
use PHPUnit\Framework\TestCase;

class AcademyBlockDecouplingTest extends TestCase
{
    public function testCoreBlockRegistryDoesNotIncludeUserAuthByDefault(): void
    {
        $registry = DefaultBlockRegistryFactory::create();

        $this->assertCount(14, $registry->all());
        $this->assertFalse($registry->has('user_auth'));
        $this->assertNull($registry->get('user_auth'));
    }

    public function testAcademyBlockProviderInjectsUserAuthBlock(): void
    {
        $registry = DefaultBlockRegistryFactory::create();
        $this->assertFalse($registry->has('user_auth'));

        $provider = new AcademyBlockProvider();
        $provider->registerBlocks($registry);

        $this->assertTrue($registry->has('user_auth'));
        $this->assertCount(15, $registry->all());
        $block = $registry->get('user_auth');
        $this->assertNotNull($block);
        $this->assertSame('Área de Login / Aluno', $block->getName());
        $this->assertSame('👤', $block->getIcon());
        $this->assertSame('navigation', $block->getCategory());
    }

    public function testAcademyPluginRegistersUserAuthViaEventHook(): void
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $connector = new OsConnector('academy');

        $plugin = new AcademyPlugin(dirname(__DIR__, 2) . '/src/Plugins/academy');
        $plugin->osRegister($connector);

        $runtime = new OsRuntime($container, $connector, new LinkRegistry($container), $dispatcher, 'academy');

        $registry = DefaultBlockRegistryFactory::create();
        $this->assertFalse($registry->has('user_auth'));

        // Simula boot do plugin Academy
        $plugin->osBoot($runtime);

        // Dispara o hook de registro de blocos
        $dispatcher->dispatch('theme.blocks.register', $registry);

        $this->assertTrue($registry->has('user_auth'));
        $this->assertCount(15, $registry->all());
        $this->assertSame('Área de Login / Aluno', $registry->get('user_auth')->getName());
    }
}
