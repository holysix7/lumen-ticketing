<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/tickets', [
    'as' => 'tickets', 'uses' => 'API\TicketController@index'
]);
$router->post('/ticket/new', [
    'as' => 'tickets', 'uses' => 'API\TicketController@create'
]);