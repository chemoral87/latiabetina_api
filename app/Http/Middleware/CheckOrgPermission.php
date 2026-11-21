<?php

namespace App\Http\Middleware;

use Closure;

class CheckOrgPermission
{
  /**
   * Passes when the user holds ANY of the listed permissions, for one or many.
   * `permission_org:a` = has a. `permission_org:a,b,c` = has a OR b OR c.
   */
  public function handle($request, Closure $next, ...$permissions)
  {
    $user = auth('api')->user();
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    $required = count($permissions) > 0 ? $permissions : [''];

    foreach ($required as $perm) {
      $perm = trim($perm);
      if ($perm && isset($permissions_orgs[$perm])) {
        return $next($request);
      }
    }
    $perm = implode(', ', $required);
    return response()->json(['error' => "No tienes permiso para realizar esta acción. Se requiere el permiso: {$perm}"], 403);
  }
}
