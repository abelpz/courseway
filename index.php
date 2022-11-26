<?php

use Slim\Exception\HttpNotFoundException;

require_once 'config.php';
require_once 'api/index.php';
require_once 'inc/handlers.php';

/**
 * Catch-all route to serve a 404 Not Found page if none of the routes match
 * NOTE: make sure this route is defined last
 */
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
  throw new HttpNotFoundException($request);
});

$app->run();
