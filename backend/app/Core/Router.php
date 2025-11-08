<?php

namespace App\Core;

class Router
{
    private static $instance = null;
    private array $routes = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function patch(string $path, $handler): void
    {
        $this->addRoute('PATCH', $path, $handler);
    }

    private function addRoute(string $method, string $path, $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
        ];
    }

    public function handle(Request $request, Response $response): void
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path)) {
                $params = $this->extractParams($route['path'], $path);
                $request->setParams($params);
                
                $this->executeHandler($route['handler'], $request, $response);
                return;
            }
        }

        $response->status(404)->json(['error' => 'Route not found']);
    }

    private function matchPath(string $routePath, string $requestPath): bool
    {
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        return preg_match($routePattern, $requestPath);
    }

    private function extractParams(string $routePath, string $requestPath): array
    {
        $params = [];
        
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        $routePattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $routePath);
        $routePattern = '#^' . $routePattern . '$#';
        
        if (preg_match($routePattern, $requestPath, $matches)) {
            array_shift($matches); // Remove full match
            
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }

    private function executeHandler($handler, Request $request, Response $response): void
    {
        if (is_callable($handler)) {
            $handler($request, $response);
        } elseif (is_string($handler) && strpos($handler, '@') !== false) {
            [$controllerClass, $method] = explode('@', $handler);
            
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, $method)) {
                    $controller->$method($request, $response);
                } else {
                    $response->status(500)->json(['error' => 'Method not found']);
                }
            } else {
                $response->status(500)->json(['error' => 'Controller not found']);
            }
        } else {
            $response->status(500)->json(['error' => 'Invalid handler']);
        }
    }
}