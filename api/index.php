<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

/**
 * GET / The main route
 */
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Silence is golden");
    return $response;
});

/**
 * GROUP / Each of the api routes
 */
$app->group('/api/v1', function ($api) {
    foreach (glob("api/routes/*.php") as $filename) {
        include $filename;
    }
    $api->group('', function ($endpoint) {
        foreach (glob("api/routes/endpoints/*.php") as $filename) {
            include $filename;
        }
    })->add(new GroupValidation());
});

/**
 * GET /{file} - Load static assets.
 */
$app->get('/resources/swagger/{file}', function (Request $request, Response $response, $args) {
    $filePath = BASE_PATH . '/resources/swagger/' . $args['file'];

    if (!file_exists($filePath)) {
        return $response->withStatus(404, 'File Not Found');
    }

    switch (pathinfo($filePath, PATHINFO_EXTENSION)) {
        case 'css':
            $mimeType = 'text/css';
            break;

        case 'js':
            $mimeType = 'application/javascript';
            break;

            // Add more supported mime types per file extension as you need here

        default:
            $mimeType = 'text/html';
    }

    $newResponse = $response->withHeader('Content-Type', $mimeType . '; charset=UTF-8');

    $newResponse->getBody()->write(file_get_contents($filePath));

    return $newResponse;
});
