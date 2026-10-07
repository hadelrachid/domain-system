<?php
namespace DomainSystem\Plugins\flex_builder\src\Registry;

use DomainSystem\Plugins\flex_builder\src\Contracts\VisualComponentInterface;

/**
 * Registro Central de Componentes Visuais do Flex-Builder
 * 
 * Segue o padrão Singleton ou Registry Pattern. 
 * Todos os plugins/temas do sistema avisam esta classe quando criam um novo Widget.
 */
class ComponentRegistry
{
    private static ?ComponentRegistry $instance = null;
    
    /** @var VisualComponentInterface[] */
    private array $components = [];

    private function __construct() {}

    public static function getInstance(): ComponentRegistry
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registra um novo bloco/widget no Builder.
     */
    public function registerComponent(VisualComponentInterface $component): void
    {
        $this->components[$component->getComponentId()] = $component;
    }

    /**
     * Busca um bloco específico pelo ID
     */
    public function getComponent(string $id): ?VisualComponentInterface
    {
        return $this->components[$id] ?? null;
    }

    /**
     * Retorna a lista de todos os Blocos para popular o menu lateral do Elementor
     */
    public function getAllComponents(): array
    {
        return $this->components;
    }

    /**
     * Entrega para o Javascript do navegador (Vue/React) o esquema completo de todos os blocos em JSON!
     */
    public function exportSchemaForFrontend(): string
    {
        $schema = [];
        foreach ($this->components as $id => $component) {
            $schema[$id] = [
                'name' => $component->getName(),
                'properties' => $component->getPropertiesSchema()
            ];
        }
        return json_encode($schema, JSON_PRETTY_PRINT);
    }
}
