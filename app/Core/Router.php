<?php
namespace App\Core;

class Router {
  private array $routes = ['GET'=>[], 'POST'=>[], 'PUT'=>[], 'DELETE'=>[]];

  public function get(string $path, $handler){ $this->routes['GET'][$this->norm($path)] = $handler; }
  public function post(string $path, $handler){ $this->routes['POST'][$this->norm($path)] = $handler; }

  private function norm(string $p): string {
    $p = '/' . ltrim(parse_url($p, PHP_URL_PATH) ?? '/', '/');
    return rtrim($p, '/') ?: '/';
  }

  public function dispatch(string $uri, string $method): void {
    $path = $this->norm($uri);
    $handler = $this->routes[$method][$path] ?? null;
    if (!$handler) { http_response_code(404); echo 'Not Found'; return; }
    if (is_array($handler)) { [$class,$fn] = $handler; $c = new $class; $c->$fn(); return; }
    call_user_func($handler);
  }
}
