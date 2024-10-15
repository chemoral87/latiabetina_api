<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ConvertRequestKeysToSnakeCase {
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response {
    // Convert all keys in the request input to snake_case
    $snakeCasedInput = collect($request->all())
      ->mapWithKeys(function ($value, $key) {
        return [Str::snake($key) => $value];
      })
      ->toArray();

    // Replace the request data with the snake_case version
    $request->replace($snakeCasedInput);

    return $next($request);
  }
}
