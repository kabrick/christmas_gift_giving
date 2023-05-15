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

$router->group(['prefix' => 'api', 'middleware' => 'throttle:20,60'], function () use ($router) {
    $router->get('get_employee_gift/{employee_id}',  ['uses' => 'HomeController@get_employee_gift']);
    $router->get('organise_data/',  ['uses' => 'HomeController@organise_data']);
});
