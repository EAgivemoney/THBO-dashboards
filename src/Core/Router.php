<?php
namespace App\Core;

/**
 * Heel kleine routerklasse.
 * 
 * Ondersteunt GET en POST routes. URI's worden gematcht op letterlijke paden,
 * dus geen route-parameters - voor dit dashboard volstaat dat ruim.
 */
class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $uri, callable $handler): void
    {
        $this->routes['GET'][$this->normalize($uri)] = $handler;
    }

    public function post(string $uri, callable $handler): void
    {
        $this->routes['POST'][$this->normalize($uri)] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path   = $this->normalize(parse_url($uri, PHP_URL_PATH) ?? '/');

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        http_response_code(404);
        echo '<h1>404 — Pagina niet gevonden</h1>';
        echo '<p>De URL <code>' . htmlspecialchars($path) . '</code> bestaat niet.</p>';
    }

    private function normalize(string $uri): string
    {
        // Verwijder de /public prefix als de site via WAMP onder een submap draait
        // (bijv. http://localhost/THBO-dashboards/public/dashboard).
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }
}