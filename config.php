<?php
/* For licensing terms, see /license.txt */

/**
 * Config the plugin.
 *
 * @package chamilo.plugin.courseway
 *
 * @author Abel Perez <abelperez@idiomaspuentes.org>
 */

require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once __DIR__ . '/inc/CourseWayPlugin.php';

$enabled = CourseWayPlugin::create()->isEnabled();
if (!$enabled) exit('Courseway plugin is disabled');

use Slim\Factory\AppFactory;
use Tuupola\Middleware\JwtAuthentication;

$uri = explode('courseway', $_SERVER['REQUEST_URI']);

define('BASE_PATH', __DIR__);
define('BASE_URI', $uri[0] . 'courseway');
define('COURSEWAY_API_URI', api_get_configuration_value('root_web') . "plugin/courseway/api/v1");

require 'inc/middlewares.php';

require BASE_PATH . '/vendor/autoload.php';
require 'inc/utils.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$app = AppFactory::create();
$app->setBasePath(BASE_URI);
$app->add(new JwtAuthentication([
    "path" => [BASE_URI . "/api/v1"],
    "ignore" => [
        BASE_URI . "/api/v1/auth",
        BASE_URI . "/api/v1/openapi",
        BASE_URI . "/api/v1/swagger"
    ],
    "secret" => $_ENV["JWT_SECRET"],
    "secure" => false,
    "error" => function ($response, $arguments) {
        $response->getBody()
            ->write(slim_msg('error', $arguments['message']));
        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(403);
    },
]));
