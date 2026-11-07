<?php
namespace App\Core;

/**
 * Very small router.
 * 
 * Supports GET and POST routes. URIs are matched against literal paths, so
 * there are no route parameters - plenty for this dashboard.
 */
class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [
        'GET' =>  [],
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
        echo '<h1>' . htmlspecialchars(Lang::get('errors.not_found_title')) . '</h1>';
        // Escape the language-file text first, then put the (escaped) URL in <code>.
        $text = htmlspecialchars(Lang::get('errors.not_found_text', ['path' => '@@PATH@@']));
        echo '<p>' . str_replace('@@PATH@@', '<code>' . htmlspecialchars($path) . '</code>', $text) . '</p>';
    }

    private function normalize(string $uri): string
    {
        // Strip the /public prefix when the site runs from a subfolder under WAMP
        // (e.g. http://localhost/THBO-dashboards/public/dashboard).
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if ($scriptDir !== '' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }
}