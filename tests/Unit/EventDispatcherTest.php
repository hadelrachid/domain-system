<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Events\EventDispatcher;

class EventDispatcherTest extends TestCase
{
    public function testAddListenerAndDispatch()
    {
        $dispatcher = new EventDispatcher();
        
        $called = false;
        $dispatcher->addListener('test_event', function() use (&$called) {
            $called = true;
        });

        $dispatcher->dispatch('test_event');

        $this->assertTrue($called, "The listener should have been executed.");
    }

    public function testDispatchWithArguments()
    {
        $dispatcher = new EventDispatcher();
        
        $result = 0;
        $dispatcher->addListener('add_numbers', function($a, $b) use (&$result) {
            $result = $a + $b;
        });

        $dispatcher->dispatch('add_numbers', 5, 10);

        $this->assertEquals(15, $result, "The listener should receive arguments correctly.");
    }

    public function testPrioritySorting()
    {
        $dispatcher = new EventDispatcher();
        $order = [];

        $dispatcher->addListener('priority_test', function() use (&$order) {
            $order[] = 'third';
        }, 30);

        $dispatcher->addListener('priority_test', function() use (&$order) {
            $order[] = 'first';
        }, 10);

        $dispatcher->addListener('priority_test', function() use (&$order) {
            $order[] = 'second';
        }, 20);

        $dispatcher->dispatch('priority_test');

        $this->assertEquals(['first', 'second', 'third'], $order, "Listeners should execute in priority order (lowest first).");
    }

    public function testApplyFilters()
    {
        $dispatcher = new EventDispatcher();

        $dispatcher->addListener('modify_string', function($value) {
            return $value . ' World';
        }, 10);

        $dispatcher->addListener('modify_string', function($value) {
            return strtoupper($value);
        }, 20);

        $result = $dispatcher->applyFilters('modify_string', 'Hello');

        $this->assertEquals('HELLO WORLD', $result, "Filters should modify and return the value sequentially.");
    }
}
