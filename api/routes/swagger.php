<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$api->get('/swagger', function (Request $request, Response $response, $args) {
    include(BASE_PATH . '/api/doc/swagger/index.php');

    return $response;
});
