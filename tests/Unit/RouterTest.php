<?php

namespace DomainSystem\Tests\Unit;

use PHPUnit\Framework\TestCase;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Container\Container;
use Exception;

class DummyController {
    public function index() {
        return "Hello World";
    }

    public function show($id) {
        return "Showing " . $id;
    }
}

class RouterTest extends TestCase
{
    public function testAddAndDispatchStaticRoute()
    {
        $container = new Container();
        $router = new Router($container);

        $router->addRoute('GET', '/test', function() {
            return 'Static Route OK';
        });

        $result = $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/test']));
        $this->assertEquals('Static Route OK', $result);
    }

    public function testAddAndDispatchDynamicRoute()
    {
        $container = new Container();
        $router = new Router($container);

        $router->addRoute('GET', '/users/{id}', function($id) {
            return "User: " . $id;
        });

        $result = $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/123']));
        $this->assertEquals('User: 123', $result);
    }

    public function testRouteToController()
    {
        $container = new Container();
        $router = new Router($container);

        $router->addRoute('GET', '/ctrl', [DummyController::class, 'index']);
        $router->addRoute('GET', '/ctrl/{id}', [DummyController::class, 'show']);

        $result1 = $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ctrl']));
        $result2 = $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ctrl/999']));

        $this->assertEquals('Hello World', $result1);
        $this->assertEquals('Showing 999', $result2);
    }

    public function testRouteNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);

        $container = new Container();
        $router = new Router($container);

        $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/does-not-exist']));
    }

    public function testMethodNotAllowedButRouteExistsIsConsideredNotFoundForNow()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(404);

        $container = new Container();
        $router = new Router($container);

        $router->addRoute('POST', '/only-post', function() { return 'OK'; });

        $router->dispatch(new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/only-post']));
    }

    public function testRouteCollisionThrowsException()
    {
        $this->expectException(\DomainSystem\Core\Exceptions\RouteAlreadyRegisteredException::class);

        $container = new Container();
        $router = new Router($container);

        // Registro original (Simulando Ring 0 / App do Sistema)
        $router->addRoute('GET', '/login', function() { return 'Core Login'; }, 'core_auth');

        // Tentativa de sequestro da rota (Simulando Ring 3 / Plugin de Usuário)
        $router->addRoute('GET', '/login', function() { return 'Fake Login'; }, 'malicious_plugin');
    }
}