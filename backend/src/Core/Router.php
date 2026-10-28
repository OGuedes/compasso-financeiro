<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [strtoupper($method), '#^' . $pattern . '$#', $handler];
    }

    public function get(string $path, callable $handler): void { $this->add('GET', $path, $handler); }
    public function post(string $path, callable $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, callable $handler): void { $this->add('PUT', $path, $handler); }
    public function patch(string $path, callable $handler): void { $this->add('PATCH', $path, $handler); }
    public function delete(string $path, callable $handler): void { $this->add('DELETE', $path, $handler); }

    public function dispatch(Request $request): mixed
    {
        $pathMatched = false;
        foreach ($this->routes as [$method, $pattern, $handler]) {
            if (!preg_match($pattern, $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($method !== $request->method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            return $handler($request, $params);
        }

        throw new HttpException($pathMatched ? 405 : 404, $pathMatched ? 'Método não permitido.' : 'Rota não encontrada.');
    }
}
