<?php

namespace app\middleware;

class CrossDomain
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);

        $response->header(['Access-Control-Allow-Origin'=>'*']);
        $response->header(['Access-Control-Allow-Headers'=>'Origin, Content-Type, Cookie, Accept ,Token']);
        $response->header(['Access-Control-Allow-Methods'=>'GET, POST, PATCH, PUT, OPTIONS']);
        $response->header(['Access-Control-Allow-Credentials'=>'false']);

        return $response;
    }
}
