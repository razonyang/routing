<?php

require __DIR__ . '/../vendor/autoload.php';

$router = new \DevLibs\Routing\Router();
$router->get('/', '/');
var_dump($router->getAllowMethods('/'));

$path = '([GET|POST"]) /';
$pattern = '~^(?:(GET)\ (/?))$~x';
var_dump(preg_match_all($pattern,$path,$matches));
var_dump($matches);

var_dump(time(1));
var_dump(time(333333));