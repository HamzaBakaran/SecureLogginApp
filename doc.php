<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require("vendor/autoload.php");
$openapi = \OpenApi\scan('api/routes');
header('Content-Type: application/json');
echo $openapi->toJson();
