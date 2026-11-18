<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method: string, pattern: string, params: array<int, string>, handler: mixed, middleware: array<int, string>}> */
    private array $routes = [];

    /** @param array<int, string> $middleware */
    public function get(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param array<int, string> $middleware */
    public function post(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param array<int, string> $middleware */
    public function put(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /** @param array<int, string> $middleware */
    public function delete(string $path, mixed $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    /** @param array<int, string> $middleware */
    private function add(string $method, string $path, mixed $handler, array $middleware): void
    {
        $params = [];

        $pattern = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '([^/]+)';
            },
            rtrim($path, '/') ?: '/'
        );

        $this->routes[] = [
            'method' => $method,
            'pattern' => '#^' . $pattern . '$#',
            'params' => $params,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path = rtrim($request->path(), '/') ?: '/';
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $request->method()) {
                continue;
            }

            array_shift($matches);

            $arguments = [];
            foreach ($route['params'] as $index => $name) {
                $arguments[$name] = $matches[$index] ?? null;
            }

            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            $handler = $route['handler'];

            if (is_array($handler) && is_string($handler[0])) {
                $controller = new $handler[0]();
                $method = $handler[1];
                $controller->{$method}(...array_values($arguments));

                return;
            }

            if (is_callable($handler)) {
                $handler(...array_values($arguments));

                return;
            }

            Response::abort(500, 'Route handler is not callable.');
        }

        if ($pathMatched) {
            Response::abort(405, 'That request method is not allowed for this page.');
        }

        Response::abort(404);
    }

    private function runMiddleware(string $middleware): void
    {
        match ($middleware) {
            'auth' => Auth::require(),
            'guest' => Auth::requireGuest(),
            'admin' => Auth::requirePlatformAdmin(),
            default => null,
        };
    }
}
