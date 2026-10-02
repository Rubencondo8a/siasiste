<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard';
}

$loggedIn = isset($_SESSION['user_id']);
$currentController = $_GET['controller'] ?? '';
$currentAction = $_GET['action'] ?? '';

if ($loggedIn && !empty($_SESSION['user_debe_cambiar_password']) && !($currentController === 'auth' && $currentAction === 'cambiarContrasena')) {
    redirect(BASE_URL . 'auth/cambiarContrasena');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Sigametec</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/3.0.2/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/4.0.2/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/base.css?v=<?= filemtime(ROOT_PATH . 'public/css/base.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/sidebar.css?v=<?= filemtime(ROOT_PATH . 'public/css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/navbar.css?v=<?= filemtime(ROOT_PATH . 'public/css/navbar.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/cards.css?v=<?= filemtime(ROOT_PATH . 'public/css/cards.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/tables.css?v=<?= filemtime(ROOT_PATH . 'public/css/tables.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/buttons.css?v=<?= filemtime(ROOT_PATH . 'public/css/buttons.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/forms.css?v=<?= filemtime(ROOT_PATH . 'public/css/forms.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/badges.css?v=<?= filemtime(ROOT_PATH . 'public/css/badges.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/modals.css?v=<?= filemtime(ROOT_PATH . 'public/css/modals.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/alerts.css?v=<?= filemtime(ROOT_PATH . 'public/css/alerts.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/misc.css?v=<?= filemtime(ROOT_PATH . 'public/css/misc.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/schedule.css?v=<?= filemtime(ROOT_PATH . 'public/css/schedule.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/weeks.css?v=<?= filemtime(ROOT_PATH . 'public/css/weeks.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/responsive.css?v=<?= filemtime(ROOT_PATH . 'public/css/responsive.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/dashboard.css?v=<?= filemtime(ROOT_PATH . 'public/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/course-cards.css?v=<?= filemtime(ROOT_PATH . 'public/css/course-cards.css') ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/stat-cards.css?v=<?= filemtime(ROOT_PATH . 'public/css/stat-cards.css') ?>">
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <script>window.BASE_URL = '<?= BASE_URL ?>'; window.SESSION_TIMEOUT = <?= (int) (SESSION_TIMEOUT ?? 1800) ?>;</script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<?php if (!empty($extraHead)): ?>
    <?= $extraHead ?>
<?php endif; ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<?php if ($loggedIn): ?>
<div class="wrapper">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="<?= BASE_URL . ($_SESSION['user_rol'] === 'admin' ? 'admin' : ($_SESSION['user_rol'] === 'docente' ? 'docente' : 'estudiante')) ?>/inicio" class="nav-link">Inicio</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto align-items-center">
            <li class="nav-item dropdown user-menu">
                <a class="nav-link d-flex align-items-center" href="#" data-toggle="dropdown">
                    <img src="<?= profilePhoto($_SESSION['user_foto'] ?? null) ?>" alt="Foto"
                         class="img-circle mr-2" style="width:32px;height:32px;object-fit:cover;">
                    <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_username'] ?? 'Usuario') ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right" style="min-width:240px;">
                    <div class="user-header p-3 text-center">
                        <img src="<?= profilePhoto($_SESSION['user_foto'] ?? null) ?>" alt="Foto"
                             class="img-circle elevation-2" style="width:72px;height:72px;object-fit:cover;border:3px solid rgba(255,255,255,.45);">
                        <p class="mt-2 mb-1 text-white font-weight-bold" style="font-size:1rem;"><?= htmlspecialchars(($_SESSION['user_nombres'] ?? '') ?: ($_SESSION['user_nombre'] ?? 'Usuario')) ?></p>
                        <small class="text-white-50"><?= htmlspecialchars($_SESSION['user_nombre'] ?? '') ?></small>
                    </div>
                    <div class="dropdown-divider"></div>
                    <?php $profileAction = $_SESSION['user_rol'] === 'admin' ? 'admin' : ($_SESSION['user_rol'] === 'docente' ? 'docente' : 'estudiante'); ?>
                    <a class="dropdown-item" href="<?= BASE_URL . $profileAction ?>/perfil">
                        <i class="fas fa-user-cog mr-2"></i>Mi Perfil
                    </a>
                    <a class="dropdown-item text-danger" href="#" id="btnLogout">
                        <i class="fas fa-sign-out-alt mr-2"></i>Cerrar Sesión
                    </a>
                </div>
            </li>
        </ul>
    </nav>

    <div class="content-wrapper">
<?php endif; ?>
