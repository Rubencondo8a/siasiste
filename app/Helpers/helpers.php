<?php

declare(strict_types=1);

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

function isDocente(): bool
{
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'docente';
}

function isEstudiante(): bool
{
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'estudiante';
}

/**
 * Formato estándar de registro:
 * - Apellidos: siempre en MAYÚSCULAS.
 * - Nombres: primera letra de cada palabra en mayúscula, el resto en minúscula.
 */
function formatApellidos(string $apellidos): string
{
    return mb_strtoupper(trim($apellidos), 'UTF-8');
}

function formatNombres(string $nombres): string
{
    return mb_convert_case(trim($nombres), MB_CASE_TITLE, 'UTF-8');
}

function redirect(string $url): void
{
    header("Location: {$url}");
    exit;
}

/**
 * Genera una URL amigable a partir de una ruta y parámetros opcionales.
 * Ejemplo: url('docente/semanas', ['curso_id' => 5]) => /sigametec/docente/semanas?curso_id=5
 */
function url(string $ruta, array $params = []): string
{
    $url = BASE_URL . ltrim($ruta, '/');
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function sanitize(mixed $data): string|array
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrfField(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * ¿La contraseña ya fue usada anteriormente por el usuario?
 * Verifica contra la contraseña actual y las últimas MAX_PASSWORD_HISTORY del historial.
 */
function passwordYaUsada(PDO $db, int $userId, string $password): bool
{
    $stmt = $db->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $current = $stmt->fetchColumn();
    if (is_string($current) && $current !== '' && password_verify($password, $current)) {
        return true;
    }

    $hist = $db->prepare('SELECT password_hash FROM historial_passwords WHERE user_id = :id ORDER BY id DESC LIMIT ' . MAX_PASSWORD_HISTORY);
    $hist->execute([':id' => $userId]);
    foreach ($hist->fetchAll(PDO::FETCH_COLUMN) as $hash) {
        if (is_string($hash) && $hash !== '' && password_verify($password, $hash)) {
            return true;
        }
    }
    return false;
}

/**
 * Guarda la contraseña que está por ser reemplazada en el historial
 * y limita el historial a las últimas MAX_PASSWORD_HISTORY.
 */
function guardarPasswordHistorial(PDO $db, int $userId, string $hashActual): void
{
    $stmt = $db->prepare('INSERT INTO historial_passwords (user_id, password_hash) VALUES (:id, :hash)');
    $stmt->execute([':id' => $userId, ':hash' => $hashActual]);

    $stmt = $db->prepare('DELETE FROM historial_passwords WHERE user_id = :id AND id NOT IN (
        SELECT id FROM (
            SELECT id FROM historial_passwords WHERE user_id = :id2 ORDER BY id DESC LIMIT ' . MAX_PASSWORD_HISTORY . '
        ) AS t
    )');
    $stmt->execute([':id' => $userId, ':id2' => $userId]);
}

/**
 * ¿La contraseña cumple los requisitos de seguridad?
 * Mínimo 8 caracteres, con al menos una mayúscula, una minúscula y un número.
 */
function passwordComplejidadOk(string $password): bool
{
    if (strlen($password) < 8) return false;
    if (!preg_match('/[A-Z]/', $password)) return false;
    if (!preg_match('/[a-z]/', $password)) return false;
    if (!preg_match('/\d/', $password)) return false;
    return true;
}

function isLoginLocked(): bool
{
    if (!isset($_SESSION['_login_attempts'])) {
        $_SESSION['_login_attempts'] = 0;
        $_SESSION['_login_lockout'] = 0;
    }
    if ($_SESSION['_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $elapsed = time() - $_SESSION['_login_lockout'];
        if ($elapsed < LOGIN_LOCKOUT_TIME) {
            return true;
        }
        $_SESSION['_login_attempts'] = 0;
    }
    return false;
}

function isUserLocked(PDO $db, int $userId): bool
{
    try {
        $stmt = $db->prepare('SELECT login_attempts, lockout_until FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        if ($row['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
            if ($row['lockout_until'] !== null && strtotime($row['lockout_until']) > time()) {
                return true;
            }
            $db->prepare('UPDATE usuarios SET login_attempts = 0, lockout_until = NULL WHERE id = :id')
                ->execute([':id' => $userId]);
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

function incrementLoginAttempt(): void
{
    $_SESSION['_login_attempts'] = ($_SESSION['_login_attempts'] ?? 0) + 1;
    if ($_SESSION['_login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['_login_lockout'] = time();
    }
}

function incrementUserLoginAttempt(PDO $db, int $userId): void
{
    try {
        $stmt = $db->prepare('SELECT login_attempts FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;

        $attempts = (int) $row['login_attempts'] + 1;
        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
            $lockout = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
            $db->prepare('UPDATE usuarios SET login_attempts = :a, lockout_until = :l WHERE id = :id')
                ->execute([':a' => $attempts, ':l' => $lockout, ':id' => $userId]);
        } else {
            $db->prepare('UPDATE usuarios SET login_attempts = :a WHERE id = :id')
                ->execute([':a' => $attempts, ':id' => $userId]);
        }
    } catch (PDOException $e) {}
}

function resetLoginAttempts(): void
{
    $_SESSION['_login_attempts'] = 0;
    $_SESSION['_login_lockout'] = 0;
}

function resetUserLoginAttempts(PDO $db, int $userId): void
{
    try {
        $db->prepare('UPDATE usuarios SET login_attempts = 0, lockout_until = NULL WHERE id = :id')
            ->execute([':id' => $userId]);
    } catch (PDOException $e) {}
}

function formatDate(?string $date): string
{
    if (!$date) return '';
    try {
        $dt = new DateTime($date);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return $date;
    }
}

function calculateAverage(array $grades): float
{
    $totalPorcentaje = 0;
    $sumaPonderada = 0;

    foreach ($grades as $grade) {
        $nota = (float) $grade['nota'];
        $porcentaje = (float) $grade['porcentaje'];
        $sumaPonderada += $nota * ($porcentaje / 100);
        $totalPorcentaje += $porcentaje;
    }

    if ($totalPorcentaje === 0) {
        return 0;
    }

    return round($sumaPonderada, 2);
}

/**
 * Costo de transferir un maná según el nivel del donante.
 * Si el nivel viene de la BD (tiene costo_donacion) se usa ese valor.
 * Fallback (nivel sin campo): Iniciado no puede transferir (null),
 * Aprendiz paga 1/64, Mago 1/128, Super Mago 1/256 y Merlín 1/512.
 */
function costoDonacionMana(array $nivel): ?float
{
    if (array_key_exists('costo_donacion', $nivel) && $nivel['costo_donacion'] !== null && (float) $nivel['costo_donacion'] > 0) {
        return (float) $nivel['costo_donacion'];
    }

    return match ($nivel['clave'] ?? '') {
        'aprendiz' => 1 / 64,
        'mago' => 1 / 128,
        'super' => 1 / 256,
        'merlin' => 1 / 512,
        default => null,
    };
}

/**
 * Devuelve el "modo de magia" del estudiante según su nota (escala 0-20).
 * Si se pasan los niveles de la BD (arrays con min_nota/max_nota/poderes JSON),
 * se hace match con la escalera de notas; si no, usa los valores por defecto
 * (comportamiento original) para no alterar el funcionamiento actual.
 */
function studentPowerMode(float $nota, array $niveles = []): array
{
    if (!empty($niveles)) {
        $ordenados = array_values($niveles);
        usort($ordenados, fn($a, $b) => (int) $a['rango'] <=> (int) $b['rango']);

        $match = null;
        foreach ($ordenados as $nivel) {
            if ($nota >= (float) $nivel['min_nota']) {
                $match = $nivel;
            } else {
                break;
            }
        }
        if ($match === null) {
            $match = $ordenados[0] ?? [];
        }

        return [
            'id'             => (int) ($match['id'] ?? 0),
            'clave'          => $match['clave'] ?? 'iniciado',
            'nombre'         => $match['nombre'] ?? 'Iniciado',
            'rango'          => (int) ($match['rango'] ?? 1),
            'icono'          => $match['icono'] ?? 'fas fa-feather-alt',
            'clase'          => $match['clase'] ?? 'badge-secondary',
            'avatar'         => $match['avatar'] ?? 'assets/img/avatar-iniciado.svg',
            'mana'        => (int) ($match['mana'] ?? 1),
            'color_mana'  => $match['color_mana'] ?? '#ffc107',
            'mensaje'        => $match['mensaje'] ?? 'Todo gran mago empieza por aquí.',
            'poderes'        => is_array($match['poderes'] ?? null) ? $match['poderes'] : (json_decode((string) ($match['poderes'] ?? '[]'), true) ?: []),
            'costo_donacion' => ($match['costo_donacion'] ?? null) !== null ? (float) $match['costo_donacion'] : null,
        ];
    }

    if ($nota < 11) {
        return [
            'clave' => 'iniciado',
            'nombre' => 'Iniciado',
            'rango' => 1,
            'icono' => 'fas fa-feather-alt',
            'clase' => 'badge-secondary',
            'avatar' => 'assets/img/avatar-iniciado.svg',
            'mana' => 1,
            'color_mana' => '#ffc107',
            'mensaje' => 'Todo gran mago empieza por aquí.',
            'poderes' => [
                ['icono' => 'fas fa-bolt', 'texto' => 'Tienes 1 maná de poder'],
                ['icono' => 'fas fa-lock', 'texto' => 'No puedes transferir maná'],
                ['icono' => 'fas fa-eye', 'texto' => 'Solo ves a compañeros Iniciados'],
            ],
        ];
    }

    if ($nota < 13) {
        return [
            'clave' => 'aprendiz',
            'nombre' => 'Aprendiz',
            'rango' => 2,
            'icono' => 'fas fa-book',
            'clase' => 'badge-info',
            'avatar' => 'assets/img/avatar-aprendiz.svg',
            'mana' => 2,
            'color_mana' => '#ffc107',
            'mensaje' => '¡Vas mejorando, sigue practicando!',
            'poderes' => [
                ['icono' => 'fas fa-bolt', 'texto' => 'Tienes 2 maná de poder'],
                ['icono' => 'fas fa-paper-plane', 'texto' => 'Envías maná a Iniciados'],
                ['icono' => 'fas fa-coins', 'texto' => 'Cada maná cuesta 1/64 de tu poder'],
            ],
        ];
    }

    if ($nota < 16) {
        return [
            'clave' => 'mago',
            'nombre' => 'Mago',
            'rango' => 3,
            'icono' => 'fas fa-hat-wizard',
            'clase' => 'badge-warning',
            'avatar' => 'assets/img/avatar-mago.svg',
            'mana' => 4,
            'color_mana' => '#ffc107',
            'mensaje' => '¡Tu magia se está fortaleciendo!',
            'poderes' => [
                ['icono' => 'fas fa-bolt', 'texto' => 'Tienes 4 maná de poder'],
                ['icono' => 'fas fa-paper-plane', 'texto' => 'Envías maná a Aprendices e Iniciados'],
                ['icono' => 'fas fa-coins', 'texto' => 'Cada maná cuesta solo 1/128 de tu poder'],
            ],
        ];
    }

    if ($nota < 19) {
        return [
            'clave' => 'super',
            'nombre' => 'Super Mago',
            'rango' => 4,
            'icono' => 'fas fa-bolt',
            'clase' => 'badge-success',
            'avatar' => 'assets/img/avatar-super-mago.svg',
            'mana' => 8,
            'color_mana' => '#ffc107',
            'mensaje' => '¡Estás brillando, casi llegas a Merlín!',
            'poderes' => [
                ['icono' => 'fas fa-bolt', 'texto' => 'Tienes 8 maná de poder'],
                ['icono' => 'fas fa-paper-plane', 'texto' => 'Envías maná a Magos, Aprendices e Iniciados'],
                ['icono' => 'fas fa-coins', 'texto' => 'Transferencia ilimitada a solo 1/256'],
            ],
        ];
    }

    return [
        'clave' => 'merlin',
        'nombre' => 'Merlín',
        'rango' => 5,
        'icono' => 'fas fa-crown',
        'clase' => 'badge-merlin',
        'avatar' => 'assets/img/avatar-merlin.svg',
        'mana' => 16,
        'color_mana' => '#9b59b6',
        'mensaje' => '¡Eres un legendario Merlín, impresionante!',
        'poderes' => [
            ['icono' => 'fas fa-bolt', 'texto' => 'Tienes 16 maná de poder púrpura'],
            ['icono' => 'fas fa-paper-plane', 'texto' => 'Envías maná a Super Magos, Magos, Aprendices e Iniciados'],
            ['icono' => 'fas fa-infinity', 'texto' => 'Transferencia ilimitada a solo 1/512'],
        ],
    ];
}

function puedeRecibirMana(array $nivelDonante, array $nivelReceptor): bool
{
    return ((int) ($nivelDonante['rango'] ?? 0)) > ((int) ($nivelReceptor['rango'] ?? 0));
}

/**
 * Semáforo del promedio semanal de un estudiante.
 * 0-9 rojo, 10-11 naranja, 12-13 amarillo, 14-16 azul, 17-20 morado.
 * Rojo/Naranja/Amarillo = necesita ayuda.
 */
function semaforoSemana(float $nota): array
{
    if ($nota < 10) {
        return ['clave' => 'rojo', 'clase' => 'sem-rojo', 'color' => '#dc3545', 'necesita' => true, 'texto' => 'Crítico'];
    }
    if ($nota < 12) {
        return ['clave' => 'naranja', 'clase' => 'sem-naranja', 'color' => '#fd7e14', 'necesita' => true, 'texto' => 'En riesgo'];
    }
    if ($nota < 14) {
        return ['clave' => 'amarillo', 'clase' => 'sem-amarillo', 'color' => '#ffc107', 'necesita' => true, 'texto' => 'Aceptable'];
    }
    if ($nota < 17) {
        return ['clave' => 'azul', 'clase' => 'sem-azul', 'color' => '#0d6efd', 'necesita' => false, 'texto' => 'Bien'];
    }
    return ['clave' => 'morado', 'clase' => 'sem-morado', 'color' => '#6f42c1', 'necesita' => false, 'texto' => 'Excelente'];
}

function profilePhoto(?string $foto): string
{
    if (!empty($foto)) {
        $path = ROOT_PATH . 'public/uploads/profile/' . $foto;
        if (file_exists($path)) {
            return BASE_URL . 'uploads/profile/' . $foto . '?v=' . filemtime($path);
        }
    }
    return BASE_URL . 'assets/img/user-default.svg';
}

function facePhotoUrl(?string $fotoPath): string
{
    if (!empty($fotoPath) && file_exists(ROOT_PATH . 'public/' . $fotoPath)) {
        return BASE_URL . $fotoPath;
    }
    return BASE_URL . 'assets/img/user-default.svg';
}

function saveFacePhoto(string $base64Data, int $studentId): ?string
{
    $data = preg_replace('#^data:image/\w+;base64,#i', '', $base64Data);
    $data = base64_decode($data, true);
    if ($data === false || $data === '') {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_buffer($finfo, $data);
    finfo_close($finfo);

    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => null,
    };
    if (!$ext) return null;

    $relativePath = 'uploads/faces/user_' . $studentId . '.' . $ext;
    $dir = ROOT_PATH . 'public/uploads/faces/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $destination = ROOT_PATH . 'public/' . $relativePath;
    if (file_put_contents($destination, $data) !== false) {
        return $relativePath;
    }
    return null;
}

function uploadProfilePhoto(array $file, int $userId): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mimeType, $allowed)) {
        return null;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        return null;
    }

    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => null,
    };

    if (!$ext) return null;

    $filename = 'user_' . $userId . '.' . $ext;
    $uploadDir = ROOT_PATH . 'public/uploads/profile/';
    $destination = $uploadDir . $filename;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $filename;
    }

    return null;
}

function isAjaxRequest(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function loadEnv(string $path): void
{
    static $loaded = false;
    if ($loaded || !is_file($path) || !is_readable($path)) {
        return;
    }
    $loaded = true;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = strtoupper(trim(substr($line, 0, $pos)));
        $value = trim(substr($line, $pos + 1));

        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) !== false) {
            continue;
        }
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

function env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'empty':
        case '(empty)':
            return '';
        case 'null':
        case '(null)':
            return null;
    }
    return $value;
}

function logError(string $message, array $context = []): void
{
    $logDir = ROOT_PATH . 'storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
    $line = "[{$timestamp}] {$message}{$contextStr}" . PHP_EOL;
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
