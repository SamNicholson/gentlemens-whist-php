<?php

namespace App\System;

use Slim\Http\Request;
use Slim\Http\Response;

class LoginMiddleware
{
    public function __invoke(Request  $request, Response $response, $next)
    {
        $route = $request->getAttribute('route');

        if ($route->getName() != 'login' && empty($_SESSION['user'])) {
            return $response->withRedirect('/');
        }
        $response = $next($request, $response);
        return $response;
    }
}