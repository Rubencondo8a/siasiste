<?php

namespace App\Core;

class Request
{
    public static function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public static function isPost()
    {
        return self::method() === 'POST';
    }

    public static function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

    public static function get($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    public static function post($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public static function input($key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    public static function all()
    {
        return $_REQUEST;
    }

    public static function only(array $keys)
    {
        $data = [];
        foreach ($keys as $key) {
            if (isset($_REQUEST[$key])) {
                $data[$key] = $_REQUEST[$key];
            }
        }
        return $data;
    }

    public static function uri()
    {
        return $_GET['route'] ?? 'dashboard';
    }

    public static function action()
    {
        return $_GET['action'] ?? null;
    }

    public static function id()
    {
        return $_GET['id'] ?? null;
    }

    public static function sanitize($value)
    {
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
}
