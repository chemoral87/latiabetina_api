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

    // Convert all keys in the snake_case input to camel case
    $camelCasedInput = collect($snakeCasedInput)
      ->mapWithKeys(function ($value, $key) {
        return [Str::camel($key) => $value];
      })
      ->toArray();

    // Merge both snake and camel case versions into the request
    $request->replace(array_merge($snakeCasedInput, $camelCasedInput));

    return $next($request);
  }
}
