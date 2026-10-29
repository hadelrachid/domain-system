<?php

declare(strict_types=1);

namespace DomainSystem\Core\Container;

use Exception;
use ReflectionClass;
use ReflectionMethod;

use DomainSystem\Core\Contracts\ContainerInterface;

class Container implements ContainerInterface
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
                if (is_callable($concrete)) {
                    $this->instances[$abstract] = $concrete($this);
                } elseif ($concrete === $abstract) {
                    $this->instances[$abstract] = $this->autowire($concrete);
                } else {
                    $this->instances[$abstract] = $this->make($concrete);
                }
            }
            return $this->instances[$abstract];
        });
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]) || class_exists($abstract);
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];
            if (is_callable($concrete)) {
                return $concrete($this);
            }
            if ($concrete === $abstract) {
                return $this->autowire($concrete);
            }
            return $this->make($concrete);
        }

        if (interface_exists($abstract)) {
            foreach (array_keys($this->bindings) as $boundKey) {
                if ((class_exists($boundKey) || interface_exists($boundKey)) && is_subclass_of($boundKey, $abstract)) {
                    return $this->make($boundKey);
                }
            }
        }

        return $this->autowire($abstract);
    }

    private function autowire(string $abstract)
    {
        if (interface_exists($abstract)) {
            throw new Exception("Interface [$abstract] cannot be instantiated directly. Please bind an implementation in a plugin.");
        }
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
            $resolved = false;
            $dependency = null;
            $lastException = null;

            if ($type instanceof \ReflectionNamedType) {
                if (!$type->isBuiltin()) {
                    try {
                        $dependency = $this->make($type->getName());
                        $resolved = true;
                    } catch (\Throwable $e) {
                        // Permite tentar valor padrão se a resolução falhar
                        $lastException = $e;
                    }
                }
            } elseif ($type instanceof \ReflectionUnionType) {
                foreach ($type->getTypes() as $subType) {
                    if ($subType instanceof \ReflectionNamedType && !$subType->isBuiltin()) {
                        try {
                            $dependency = $this->make($subType->getName());
                            $resolved = true;
                            break;
                        } catch (\Throwable $e) {
                            $lastException = $e;
                            continue;
                        }
                    }
                }
            }

            if ($resolved) {
                $dependencies[] = $dependency;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
            } else {
                $cause = $lastException ? ' (Causa: ' . $lastException->getMessage() . ')' : '';
                throw new Exception("Cannot resolve parameter [{$parameter->name}] in class [$abstract].{$cause}", 0, $lastException);
            }
        }

        return $reflector->newInstanceArgs($dependencies);
    }
}
