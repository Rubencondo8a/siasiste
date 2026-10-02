<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_PATH . 'vendor/autoload.php';
require_once ROOT_PATH . 'config/database.php';
require_once ROOT_PATH . 'app/Models/User.php';
require_once ROOT_PATH . 'app/Models/Semana.php';
require_once ROOT_PATH . 'app/Models/Criterio.php';
require_once ROOT_PATH . 'app/Models/Calificacion.php';
require_once ROOT_PATH . 'app/Models/Carrera.php';
require_once ROOT_PATH . 'app/Models/PlanEstudio.php';
require_once ROOT_PATH . 'app/Models/Periodo.php';
require_once ROOT_PATH . 'app/Models/Curso.php';
require_once ROOT_PATH . 'app/Models/Asignacion.php';
require_once ROOT_PATH . 'app/Models/Grupo.php';
require_once ROOT_PATH . 'app/Models/Equipo.php';
require_once ROOT_PATH . 'app/Models/Ambiente.php';
require_once ROOT_PATH . 'app/Models/Horario.php';
require_once ROOT_PATH . 'app/Models/AyudaCompanero.php';
require_once ROOT_PATH . 'app/Models/SolicitudExamen.php';
require_once ROOT_PATH . 'app/Models/Asistencia.php';
require_once ROOT_PATH . 'app/Models/Rostro.php';
require_once ROOT_PATH . 'app/Models/NivelMago.php';
require_once ROOT_PATH . 'app/Models/PuntoParticipacion.php';
require_once ROOT_PATH . 'app/Models/Beneficio.php';
require_once ROOT_PATH . 'app/Models/Canje.php';
require_once ROOT_PATH . 'app/Models/Apreciacion.php';
require_once ROOT_PATH . 'app/Models/Discusion.php';
require_once ROOT_PATH . 'app/Models/MensajeDiscusion.php';
require_once ROOT_PATH . 'app/Models/DiscusionLeida.php';
require_once ROOT_PATH . 'app/Models/TypingIndicador.php';
require_once ROOT_PATH . 'app/Models/MensajeLike.php';
require_once ROOT_PATH . 'app/Models/MensajeAprobacion.php';
require_once ROOT_PATH . 'app/Models/NotificacionDebate.php';
require_once ROOT_PATH . 'app/Models/MotivoPunto.php';
require_once ROOT_PATH . 'app/Models/ParticipacionVivo.php';
require_once ROOT_PATH . 'app/Models/Examen.php';
require_once ROOT_PATH . 'app/Controllers/AuthController.php';
require_once ROOT_PATH . 'app/Controllers/AdminController.php';
require_once ROOT_PATH . 'app/Controllers/DocenteController.php';
require_once ROOT_PATH . 'app/Controllers/EstudianteController.php';
require_once ROOT_PATH . 'app/Controllers/ExamenController.php';
require_once ROOT_PATH . 'app/Libraries/Mailer.php';

// Parsear ruta amigable: /controlador/accion
$ruta = trim($_GET['ruta'] ?? '', '/');

// Si no hay ruta por .htaccess, parsear desde REQUEST_URI
if ($ruta === '') {
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
}

// Quitar el prefijo del BASE_URL si está presente (con o sin barra inicial)
$ruta = ltrim($ruta, '/');
$baseSegment = trim(BASE_URL, '/');
if ($baseSegment !== '' && strpos($ruta, $baseSegment) === 0) {
    $ruta = substr($ruta, strlen($baseSegment));
}
$ruta = trim($ruta, '/');

$segmentos = $ruta !== '' ? explode('/', $ruta) : [];

// Compatibilidad: si viene por ruta amigable, sobrescribe controller/action
if (isset($segmentos[0]) && $segmentos[0] !== '') {
    $_GET['controller'] = $segmentos[0];
    $accion = $segmentos[1] ?? ($segmentos[0] === 'auth' ? 'iniciarSesion' : 'inicio');
    if ($accion === 'login') $accion = 'iniciarSesion';
    $_GET['action'] = $accion;
}

$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'iniciarSesion';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $controller !== 'auth') {
    if (!validateCsrfToken($_POST['_csrf_token'] ?? null)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido. Recargue la página.']);
            exit;
        }
        setFlash('error', 'Token CSRF inválido. Recargue la página.');
        redirect(BASE_URL . 'auth/iniciarSesion');
        exit;
    }
}

$database = new Database();
$db = $database->getConnection();

// ==========================================================
// Auto-disparo diario de recordatorios de debates (sin cron).
// Se ejecuta una sola vez al dia, la primera vez que alguien
// entra al sistema despues de las 06:00.
// Usa fastcgi_finish_request() para no bloquear la pagina.
// ==========================================================
try {
    $marcaArchivo = ROOT_PATH . "storage/debate_cron_ultima.txt";
    $lockArchivo  = ROOT_PATH . "storage/debate_cron.lock";
    $hoyMarca = date("Y-m-d");
    $debeEjecutar = false;
    if (!is_file($marcaArchivo) || trim((string) @file_get_contents($marcaArchivo)) !== $hoyMarca) {
        if ((int) date("G") >= 6 && !is_file($lockArchivo)) {
            $debeEjecutar = true;
        }
    }
    if ($debeEjecutar) {
        @file_put_contents($lockArchivo, (string) time());
        require_once ROOT_PATH . "app/Helpers/debates_recordatorios.php";
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            @ob_end_flush();
            flush();
        }
        $resumenCron = enviarRecordatoriosDebates($db);
        @file_put_contents($marcaArchivo, $hoyMarca);
        @unlink($lockArchivo);
        logError("Recordatorios de debates auto-ejecutados", ["resumen" => $resumenCron]);
    }
} catch (Throwable $eCronAuto) {
    @unlink($lockArchivo);
    logError("Auto-disparo de recordatorios fallo", ["message" => $eCronAuto->getMessage()]);
}

switch($controller) {
    case 'auth':
        $ctrl = new AuthController($db);
        break;
    case 'admin':
        $ctrl = new AdminController($db);
        break;
    case 'docente':
        $ctrl = new DocenteController($db);
        break;
    case 'estudiante':
        $ctrl = new EstudianteController($db);
        break;
    case 'examen':
        $ctrl = new ExamenController($db);
        break;
    default:
        require_once ROOT_PATH . 'app/Views/errors/404.php';
        exit;
}

if (method_exists($ctrl, $action)) {
    $ctrl->$action();
} else {
    require_once ROOT_PATH . 'app/Views/errors/404.php';
}
