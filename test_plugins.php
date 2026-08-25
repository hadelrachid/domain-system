<?php $app = require "bootstrap.php"; $app->boot(); print_r(array_keys($app->getPluginManager()->getPlugins()));
