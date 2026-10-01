<?php
require 'bootstrap.php';
$router = \DomainSystem\Core\Application::getInstance()->getContainer()->make(\DomainSystem\Core\Routing\Router::class);
print_r($router->getRoutes());
