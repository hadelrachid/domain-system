<?php
require __DIR__ . "/../bootstrap.php";
$app = \DomainSystem\Core\Application::getInstance();
$app->boot();
$app->getDispatcher()->dispatch("router.register", $app->getRouter());
$router = $app->getRouter();
$rf = new ReflectionClass($router);
$rp = $rf->getProperty("routes");
$rp->setAccessible(true);
$routes = $rp->getValue($router);
print_r(array_keys($routes["GET"] ?? []));

