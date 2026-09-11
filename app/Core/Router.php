<?php

namespace App\Core;

class Router
{
    private static $routes = [];
    private static $globalMiddleware = [];
    private static $csrfExempt = [];

    public static function addGlobalMiddleware($middleware)
    {
        self::$globalMiddleware[] = $middleware;
    }

    public static function csrfExempt($route)
    {
        self::$csrfExempt[] = $route;
    }

    public static function add($route, $controller, $action = 'index', $middleware = [], $actionAlias = null)
    {
        $key = $route . ':' . ($actionAlias ?? $action);
        self::$routes[$key] = [
            'controller' => $controller,
            'action' => $action,
            'middleware' => (array) $middleware,
        ];
    }

    private static function resolveHandler($route, $action)
    {
        if ($action) {
            return self::$routes[$route . ':' . $action] ?? null;
        }

        $index = self::$routes[$route . ':index'] ?? null;
        if ($index) return $index;

        foreach (self::$routes as $key => $handler) {
            if (strpos($key, $route . ':') === 0) {
                return $handler;
            }
        }

        return null;
    }

    public static function dispatch()
    {
        $route = Request::uri();
        $action = Request::action() ?? '';
        $id = Request::id();

        $handler = self::resolveHandler($route, $action);

        if (!$handler) {
            Response::notFound('Ruta no encontrada: ' . $route);
        }

        $middlewareStack = array_merge(self::$globalMiddleware, $handler['middleware']);

        foreach ($middlewareStack as $mw) {
            if (is_string($mw)) {
                if (!class_exists($mw)) {
                    $mw = 'App\\Middleware\\' . $mw;
                }
                if (class_exists($mw)) {
                    $instance = new $mw();
                    if (method_exists($instance, 'handle')) {
                        if ($instance instanceof \App\Middleware\CsrfMiddleware) {
                            if (!in_array($route, self::$csrfExempt, true)) {
                                $instance->handle();
                            }
                        } else {
                            $instance->handle();
                        }
                    }
                }
            } elseif (is_callable($mw)) {
                $mw();
            }
        }

        $controllerClass = $handler['controller'];
        $actionMethod = $handler['action'];

        if (!class_exists($controllerClass)) {
            Response::error("Controlador no encontrado: {$controllerClass}", 500);
        }

        $controller = new $controllerClass();

        $params = [];
        if ($id !== null) {
            $params[] = $id;
        }

        if (!method_exists($controller, $actionMethod)) {
            Response::error("Acción '{$actionMethod}' no encontrada en {$controllerClass}", 500);
        }

        return call_user_func_array([$controller, $actionMethod], $params);
    }
}
