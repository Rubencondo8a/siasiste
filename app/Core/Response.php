<?php

namespace App\Core;

class Response
{
    public static function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function success($data = null, $message = 'Operación exitosa', $code = 200)
    {
        self::json(['success' => true, 'message' => $message, 'data' => $data], $code);
    }

    public static function error($message = 'Error', $code = 400, $errors = null)
    {
        $res = ['success' => false, 'error' => $message];
        if ($errors !== null) {
            $res['errors'] = $errors;
        }
        self::json($res, $code);
    }

    public static function notFound($message = 'Recurso no encontrado')
    {
        self::error($message, 404);
    }

    public static function unauthorized($message = 'No autenticado')
    {
        self::error($message, 401);
    }

    public static function forbidden($message = 'Sin permisos')
    {
        self::error($message, 403);
    }

    public static function redirect($url)
    {
        header("Location: " . BASE_URL . $url);
        exit;
    }

    public static function view($view, $data = [])
    {
        extract($data);
        $viewPath = VIEWS_PATH . '/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        require $viewPath;
    }

    public static function viewOnly($view, $data = [])
    {
        extract($data);
        $viewPath = VIEWS_PATH . '/' . ltrim($view, '/') . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        require $viewPath;
    }
}
