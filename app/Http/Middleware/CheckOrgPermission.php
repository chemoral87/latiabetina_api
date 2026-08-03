<?php

namespace App\Http\Middleware;

use Closure;

class CheckOrgPermission
{
  public function handle($request, Closure $next, $permission)
  {
    $user = auth('api')->user();
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    if (!$user || !isset($permissions_orgs[$permission])) {
      return response()->json(['error' => "No tienes permiso para realizar esta acción. Se requiere el permiso: {$permission}"], 403);
    }

    return $next($request);
  }
}
