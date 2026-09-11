<?php

namespace App\Core;

abstract class Controller
{
    protected function view($view, $data = [])
    {
        return Response::view($view, $data);
    }

    protected function viewOnly($view, $data = [])
    {
        return Response::viewOnly($view, $data);
    }

    protected function json($data, $code = 200)
    {
        return Response::json($data, $code);
    }

    protected function success($data = null, $message = 'Operación exitosa', $code = 200)
    {
        return Response::success($data, $message, $code);
    }

    protected function error($message = 'Error', $code = 400)
    {
        return Response::error($message, $code);
    }

    protected function notFound($message = 'Recurso no encontrado')
    {
        return Response::notFound($message);
    }

    protected function redirect($url)
    {
        return Response::redirect($url);
    }

    protected function getInput($key, $default = null)
    {
        return Request::input($key, $default);
    }

    protected function isPost()
    {
        return Request::isPost();
    }

    protected function requireMethod($method)
    {
        if (Request::method() !== strtoupper($method)) {
            Response::error('Método no permitido', 405);
        }
    }

    protected function requireAuth()
    {
        \App\Middleware\AuthMiddleware::check();
    }

    protected function requireRole($roles)
    {
        \App\Middleware\AuthMiddleware::role($roles);
    }

    protected function requireAdmin()
    {
        \App\Middleware\AuthMiddleware::admin();
    }

    protected function verifyCsrf()
    {
        $token = Request::post('_token', Request::input('_token', ''));
        if (!\verifyCsrf($token)) {
            Response::error('Token CSRF inválido', 419);
        }
    }
}
