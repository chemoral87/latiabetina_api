<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use JWTAuth;

class JwtMiddleware {

  public function handle(Request $request, Closure $next) {
    // DOCS https://www.avyatech.com/rest-api-with-laravel-8-using-jwt-token/
    try {
      $user = JWTAuth::parseToken()->authenticate();
    } catch (Exception $e) {
      if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) {
        return response()->json(['errors' => ['status' => 'Token is Invalid']], 401);
      } else if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) {
        return response()->json(['errors' => ['status' => 'Token is Expired']], 401);
      } else {
        return response()->json(['errors' => ['status' => 'Authorization Token not found']], 401);
      }
    }
    return $next($request);
  }
}
