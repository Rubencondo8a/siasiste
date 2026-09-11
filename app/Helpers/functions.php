<?php

function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit;
}

function isAuthenticated()
{
    return isset($_SESSION['user_id']);
}

function requireAuth()
{
    if (!isAuthenticated()) {
        redirect('/public/index.php?route=login');
    }
}

function requireRole($roles)
{
    requireAuth();
    if (!in_array($_SESSION['user_role'] ?? '', (array) $roles)) {
        redirect('/public/index.php?route=dashboard');
    }
}

function currentUser()
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'usuario' => $_SESSION['user_usuario'] ?? '',
        'nombre' => $_SESSION['user_nombre'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'rol' => $_SESSION['user_role'] ?? '',
    ];
}

function sanitize($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="_token" value="' . csrfToken() . '">';
}

function verifyCsrf($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flash($key, $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
    } else {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

function setFlash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function formatFecha($date)
{
    $months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'];
    $d = new DateTime($date);
    return $d->format('d') . ' de ' . $months[(int)$d->format('m') - 1] . ' ' . $d->format('Y');
}

function slugify($text)
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $text);
    $text = preg_replace('/[^a-z0-9_-]+/', '_', $text);
    return trim($text, '_');
}

function getNombreRol($rol)
{
    if (!$rol) return '—';
    static $cache = [];
    if (!isset($cache[$rol])) {
        $r = (new \App\Models\Role())->findAll();
        $cache = array_column($r, 'nombre_mostrar', 'nombre');
    }
    return $cache[$rol] ?? $rol;
}

function getBadgeColor($estado)
{
    $colors = ['presente' => 'success', 'ausente' => 'danger', 'justificado' => 'warning', 'activo' => 'success', 'retirado' => 'danger', 'egresado' => 'secondary'];
    return $colors[$estado] ?? 'primary';
}

function jsonResponse($data, $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function _logAudit($accion, $entidad, $entidadId = null, $detalle = null)
{
    try {
        $uid = $_SESSION['user_id'] ?? null;
        $model = new \App\Models\Auditoria();
        $model->log($uid, $accion, $entidad, $entidadId, $detalle);
    } catch (\Exception $e) {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("Audit log error: " . $e->getMessage());
        }
    }
}
