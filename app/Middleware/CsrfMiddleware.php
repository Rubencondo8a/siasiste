<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware
{
    public function handle()
    {
        if (Request::isPost()) {
            $token = Request::post('_token', Request::input('_token', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
            if (!\verifyCsrf($token)) {
                error_log("CSRF token mismatch — expected: " . ($_SESSION['csrf_token'] ?? 'none') . ", got: " . substr($token, 0, 8) . "...");
                Response::error('Token CSRF inválido', 419);
            }
            if (!Request::isAjax()) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }
    }
}
