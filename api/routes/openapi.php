<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$api->get('/openapi', function (Request $request, Response $response, $args) {
    include(BASE_PATH . '/api/doc/openapi.php');
    return $response;
});
