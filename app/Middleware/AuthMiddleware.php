<?php

namespace App\Middleware;

use App\Core\Response;
use App\Core\Request;

class AuthMiddleware
{
    public static function check()
    {
        if (!\isAuthenticated()) {
            if (Request::isAjax()) {
                Response::unauthorized('No autenticado');
            }
            Response::redirect('/public/index.php?route=login');
        }
    }

    public static function role($roles)
    {
        self::check();
        if (!in_array($_SESSION['user_role'] ?? '', (array) $roles)) {
            if (Request::isAjax()) {
                Response::forbidden('Sin permisos');
            }
            Response::redirect('/public/index.php?route=dashboard');
        }
    }

    public static function admin()
    {
        self::role(['admin']);
    }

    public function handle()
    {
        self::check();
    }
}
