<?php

$openapi = \OpenApi\Generator::scan([BASE_PATH . '/api/routes']);

header('Content-Type: application/json');
echo $openapi->toJSON();
