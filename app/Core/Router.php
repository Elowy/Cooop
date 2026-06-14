<?php

namespace App\Core;

/**
 * Minimalista útvonalkezelő paraméteres útvonalakkal (pl. /termek/{slug}).
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = ['GET' => [], 'POST' => []];

    public function get(string $pattern, callable $handler): void
    {
        $this->routes['GET'][$pattern] = $handler;
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->routes['POST'][$pattern] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
        $path = $path === '//' ? '/' : $path;

        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                echo $handler($params);
                return;
            }
        }

        http_response_code(404);
        echo View::render('errors/404', ['title' => 'Az oldal nem található']);
    }
}
