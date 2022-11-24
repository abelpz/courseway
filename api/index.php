<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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

$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});
