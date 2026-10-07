<?php

namespace DomainSystem\Core\Plugin;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use Exception;

/**
 * OsRuntime (A Ponte de Execução)
 *
 * Utilizado pelos plugins na Fase 2 para pedir os recursos.
 * Aplica o Princípio do Menor Privilégio: se o plugin não pediu na Fase 1, o Runtime barra!
 */
class OsRuntime implements OsRuntimeInterface
{
    private ContainerInterface $container;
    private OsConnector $connectorManifest;
    private LinkRegistry $linkRegistry;
    private \DomainSystem\Core\Contracts\EventDispatcherInterface $eventDispatcher;
    
    private string $pluginNamespace = '';

    public function __construct(ContainerInterface $container, OsConnector $connectorManifest, LinkRegistry $linkRegistry, \DomainSystem\Core\Contracts\EventDispatcherInterface $eventDispatcher, string $pluginNamespace = '') {
        $this->pluginNamespace = $pluginNamespace;
        $this->container = $container;
        $this->connectorManifest = $connectorManifest;
        $this->linkRegistry = $linkRegistry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Entrega a instância de um Link (Serviço) ao Plugin.
     */
    public function getLink(string $linkName)
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($linkName, $this->connectorManifest->getRequiredLinks())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou acessar o Link '{$linkName}', mas não o requisitou durante a Fase de Registro.");
        }

        return $this->linkRegistry->resolve($linkName);
    }

    /**
     * Permite ao plugin injetar visual/dado em um Slot que ele pediu acesso.
     */
    public function contributeTo(string $slotName, mixed $payload): void
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($slotName, $this->connectorManifest->getRequestedSlots())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou modificar o Slot visual '{$slotName}', mas não pediu permissão no registro.");
        }

        // No futuro: Injeta o $payload no sistema visual.
    }

    /**
     * Permite ao plugin executar um código (callback) quando o evento acontecer.
     */
    public function onHook(string $hookName, callable $callback, int $priority = 0): void
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($hookName, $this->connectorManifest->getListenedHooks())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou escutar o Evento/Hook '{$hookName}', mas não declarou intenção no registro.");
        }

        // Repassa para o EventDispatcher real do Kernel
        $this->eventDispatcher->addListener($hookName, $callback, $priority);
    }

    /**
     * Permite ao plugin avisar o sistema que algo aconteceu.
     */
    public function dispatchHook(string $hookName, mixed ...$payload): void
    {
        // 🚨 O GUARDIÃO DE PERMISSÕES 🚨
        if (!in_array($hookName, $this->connectorManifest->getProvidedHooks())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou disparar o Evento/Hook '{$hookName}', mas não declarou que o forneceria no registro.");
        }

        // Repassa para o EventDispatcher real do Kernel
        $this->eventDispatcher->dispatch($hookName, ...$payload);
    }

    /**
     * Permite ao plugin aplicar modificações sobre um valor usando filtros.
     */
    public function applyFilter(string $filterName, mixed $value, mixed ...$args): mixed
    {
        // ?? O GUARDIÃO DE PERMISSÕES ??
        if (!in_array($filterName, $this->connectorManifest->getProvidedHooks())) {
            throw new Exception("Auditoria de Segurança (Bloqueio): O plugin tentou aplicar o filtro '$filterName', mas não declarou que o forneceria no registro.");
        }

        // Repassa para o EventDispatcher real do Kernel
        if (method_exists($this->eventDispatcher, 'applyFilters')) {
            return $this->eventDispatcher->applyFilters($filterName, $value, ...$args);
        }
        
        return $value;
    }

    public function bind(string $abstract, callable|string $concrete): void
    {
        $this->container->bind($abstract, $concrete);
    }

    public function singleton(string $abstract, callable|string $concrete): void
    {
        $this->container->singleton($abstract, $concrete);
    }

    public function make(string $class)
    {
        if (str_starts_with($class, 'DomainSystem\\Core\\')) {
            throw new \Exception("Auditoria de Seguranca: Um Plugin Ring 3 nao pode instanciar Core classes diretamente. Use getLink().");
        }
        if (str_starts_with($class, 'DomainSystem\\SystemApps\\') || str_starts_with($class, 'DomainSystem\\Plugins\\')) {
            if (empty($this->pluginNamespace) || !str_starts_with($class, $this->pluginNamespace)) {
                throw new \Exception("Auditoria de Seguranca: Isolamento quebrado. Nao e permitido instanciar classes de outros plugins ou SystemApps diretamente ($class). Use getLink().");
            }
        }
        return $this->container->make($class);
    }
    
    public function registerCapability(string $slug, string $context = ''): void
    {
        try {
            $repo = $this->container->make(\DomainSystem\SystemApps\auth\Contracts\CapabilityRepositoryInterface::class);
            $repo->registerCapability($slug, $context);
        } catch (\Exception $e) {
            // Silently ignore if Auth plugin is not active/available yet
        }
    }
}