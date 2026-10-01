<?php

namespace DomainSystem\Core\Events;

use DomainSystem\Core\Contracts\EventDispatcherInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    public function addListener(string $eventName, callable $listener, int $priority = 10): void
    {
        $this->listeners[$eventName][$priority][] = $listener;
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        foreach ($this->listeners[$eventName] as $priority => $listenersGroup) {
            foreach ($listenersGroup as $key => $registeredListener) {
                if ($registeredListener === $listener) {
                    unset($this->listeners[$eventName][$priority][$key]);
                }
            }
        }
    }

    public function dispatch(string $eventName, ...$args): void
    {
        if (!isset($this->listeners[$eventName])) {
            return;
        }

        // Sort by priority (lower number = higher priority, or vice-versa)
        // Let's use lower number = executes first, but we'll sort ascending
        ksort($this->listeners[$eventName]);

        foreach ($this->listeners[$eventName] as $priority => $listenersGroup) {
            foreach ($listenersGroup as $listener) {
                call_user_func_array($listener, $args);
            }
        }
    }

    public function applyFilters(string $filterName, mixed $value, ...$args): mixed
    {
        if (!isset($this->listeners[$filterName])) {
            return $value;
        }

        ksort($this->listeners[$filterName]);

        foreach ($this->listeners[$filterName] as $priority => $listenersGroup) {
            foreach ($listenersGroup as $listener) {
                $argsToPass = array_merge([$value], $args);
                $value = call_user_func_array($listener, $argsToPass);
            }
        }

        return $value;
    }
}
