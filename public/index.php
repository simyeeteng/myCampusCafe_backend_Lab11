<?php 
use Psr\Http\Message\ResponseInterface as Response; 
use Psr\Http\Message\ServerRequestInterface as Request; 
use Slim\Factory\AppFactory; 
 
require __DIR__ . '/../vendor/autoload.php'; 
 
$app = AppFactory::create(); 
 
$app-, function (Request $request, Response $response, $args) { 
    $response- from Campus Cafe API!'); 
    return $response; 
}); 
 
$app-
