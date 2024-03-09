<?php

namespace Openapi\ServerGenerator\Tests\Utils;

use Openapi\ServerGenerator\Exceptions\RouteControllerInvalidException;
use Openapi\ServerGenerator\Tests\TestCase;
use Openapi\ServerGenerator\Utils\RouteControllerResolver;

class RouteResolverTest extends TestCase
{
    public function test_invalid_controller_will_throw_exception()
    {
        $this->expectException(RouteControllerInvalidException::class);
        $controller = "App\Http\Controllers\Api\UserController";

        RouteControllerResolver::extract($controller);
    }

    public function test_extracts_route_correctly()
    {
        $controller = "App\Http\Controllers\Api\UserController@search";
        $expect = [
            'App\Http\Controllers\Api\UserController',
            'UserController',
            'search',
        ];

        $extractedRouteController = RouteControllerResolver::extract($controller);

        $this->assertSame($expect[0], $extractedRouteController->namespace);
        $this->assertSame($expect[1], $extractedRouteController->controller);
        $this->assertSame($expect[2], $extractedRouteController->action);
    }

    public function test_extracts_route_with_http_method_correctly()
    {
        $controller = "App\Http\Controllers\Api\UserController@search";
        $expect = [
            'App\Http\Controllers\Api\UserController',
            'UserController',
            'search',
            'post'
        ];

        $extractedRouteController = RouteControllerResolver::extract($controller, 'post');

        $this->assertSame($expect[0], $extractedRouteController->namespace);
        $this->assertSame($expect[1], $extractedRouteController->controller);
        $this->assertSame($expect[2], $extractedRouteController->action);
        $this->assertSame($expect[3], $extractedRouteController->httpMethod);
    }
}
