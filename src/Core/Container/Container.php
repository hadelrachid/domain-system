<?php

namespace DomainSystem\Core\Container;

use Exception;
use ReflectionClass;
use ReflectionMethod;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function bind(string $abstract, callable|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, callable|string $concrete): void
    {
        $this->bind($abstract, function () use ($concrete, $abstract) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = is_callable($concrete) ? $concrete($this) : $this->make($concrete);
            }
            return $this->instances[$abstract];
        });
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
    }

    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if (is_callable($concrete)) {
                return $concrete($this);
            }
            return $this->make($concrete);
        }

        return $this->autowire($abstract);
    }

    private function autowire(string $abstract)
    {
        if (!class_exists($abstract)) {
            throw new Exception("Target class [$abstract] does not exist.");
        }

        $reflector = new ReflectionClass($abstract);

        if (!$reflector->isInstantiable()) {
            throw new Exception("Target [$abstract] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if (is_null($constructor)) {
            return new $abstract;
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Cannot resolve parameter [{$parameter->name}] in class [$abstract].");
                }
            } else {
                $dependencies[] = $this->make($type->getName());
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
