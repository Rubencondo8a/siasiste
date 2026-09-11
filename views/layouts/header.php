<?php
$user = currentUser();
$currentRoute = $_GET['route'] ?? 'dashboard';
$pageTitle = $pageTitle ?? 'SIASISTE';
$menus = require CONFIG_PATH . '/menus.php';
function _hasAccess($item, $rol) {
  if (isset($item['roles']) && is_array($item['roles'])) return in_array($rol, $item['roles'], true);
  if (isset($item['rol'])) return $rol === $item['rol'];
  return true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIASISTE — <?php echo sanitize($pageTitle); ?></title>
<link rel="icon" href="<?php echo BASE_URL; ?>/public/img/logo.png">
<meta name="csrf-token" content="<?php echo csrfToken(); ?>">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/datatables/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/datatables-buttons/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/select2/css/select2.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/select2/css/select2-bootstrap-5-theme.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/vendor/sweetalert2/css/sweetalert2.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/appAdmin.css">
</head>
<body>

<div id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<nav id="sidebar">
  <div class="sidebar-brand">
    <div class="sidebar-brand-inner">
      <img src="<?php echo BASE_URL; ?>/public/img/logo.png" alt="SIASISTE" class="sidebar-logo">
      <span class="sidebar-brand-text">SIASISTE</span>
    </div>
  </div>
  <div class="sidebar-nav">
    <?php foreach ($menus as $menu): ?>
      <?php if (($menu['route'] ?? '') === 'logout') continue; ?>
      <?php if (isset($menu['divider'])) continue; ?>
      <?php if (isset($menu['section'])): ?>
        <?php
          // filtrar items visibles para el rol actual
          $visibleItems = array_filter($menu['items'], fn($it) => _hasAccess($it, $user['rol']));
          if (empty($visibleItems)) continue; // no mostrar sección vacía
          $isActiveGroup = false;
          foreach ($visibleItems as $it) {
            if (($it['route'] ?? '') === $currentRoute) { $isActiveGroup = true; break; }
          }
          $collapsedClass = $isActiveGroup ? '' : ' collapsed';
          $ariaExpanded = $isActiveGroup ? 'true' : 'false';
        ?>
        <div class="nav-section-group">
          <div class="nav-section-toggle<?= $collapsedClass ?>" onclick="toggleSection(this)" role="button" tabindex="0" aria-expanded="<?= $ariaExpanded ?>" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();toggleSection(this);}">
            <span class="nav-section-label"><?= $menu['section'] ?></span>
            <i class="fa-solid fa-chevron-down nav-section-arrow" aria-hidden="true"></i>
          </div>
          <div class="nav-section-items<?= $collapsedClass ?>">
            <?php foreach ($visibleItems as $item): ?>
              <a class="nav-link-item <?= ($item['class'] ?? '') ?> <?= $currentRoute === $item['route'] ? 'active' : '' ?>" href="index.php?route=<?= $item['route'] ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i><span class="nav-label"><?= $item['label'] ?></span><span class="nav-tooltip"><?= $item['label'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="nav-section-flyout">
            <?php foreach ($visibleItems as $item): ?>
              <a class="nav-flyout-item <?= $currentRoute === $item['route'] ? 'active' : '' ?>" href="index.php?route=<?= $item['route'] ?>">
                <i class="fa-solid <?= $item['icon'] ?>"></i><span><?= $item['label'] ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <?php if (!_hasAccess($menu, $user['rol'])) continue; ?>
        <a class="nav-link-item <?= ($menu['class'] ?? '') ?> <?= $currentRoute === $menu['route'] ? 'active' : '' ?>" href="index.php?route=<?= $menu['route'] ?>">
          <i class="fa-solid <?= $menu['icon'] ?>"></i><span class="nav-label"><?= $menu['label'] ?></span><span class="nav-tooltip"><?= $menu['label'] ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="sidebar-footer">
    <a class="nav-link-item logout" href="index.php?route=logout">
      <i class="fa-solid fa-right-from-bracket"></i><span class="nav-label">Salir</span><span class="nav-tooltip">Salir</span>
    </a>
    <div class="sidebar-collapse-btn" onclick="toggleCollapseSidebar()" title="Colapsar menú">
      <i class="fa-solid fa-chevron-left"></i>
    </div>
  </div>
</nav>

<!-- TOPBAR -->
<header id="topbar">
  <button id="sidebarToggle" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
  <div class="page-heading" id="pageHeading">
    <i class="fa-solid fa-house" id="pageIcon"></i>
    <span id="pageTitle"><?php echo sanitize($pageTitle); ?></span>
  </div>
  <div class="topbar-right">
    <button class="icon-btn-top" title="Notificaciones" onclick="location.href='index.php?route=alertas'"><i class="fa-solid fa-bell"></i></button>
    <button class="icon-btn-top" id="internetStatus" title="Verificando..."><i class="fa-solid fa-wifi"></i></button>
    <div class="user-dropdown" id="userDropdown">
      <div class="top-user-btn" onclick="toggleUserMenu()" id="topUserBtn">
        <div class="top-avatar" id="topAvatar"><?php echo strtoupper(substr($user['nombre'], 0, 1)); ?></div>
        <div class="top-user-info">
          <span class="top-user-name"><?php echo sanitize($user['nombre']); ?></span>
          <span class="top-user-role"><?php echo getNombreRol($user['rol']); ?></span>
        </div>
        <i class="fa-solid fa-chevron-down top-chevron" id="topChevron"></i>
      </div>
      <div class="user-menu" id="userMenu">
        <div class="user-menu-header">
          <div class="user-menu-avatar"><?php echo strtoupper(substr($user['nombre'], 0, 1)); ?></div>
          <div>
            <div class="user-menu-name"><?php echo sanitize($user['nombre']); ?></div>
            <div class="user-menu-email"><?php echo sanitize($user['email'] ?? $user['usuario']); ?></div>
          </div>
        </div>
        <div class="user-menu-divider"></div>
        <a class="user-menu-item" href="index.php?route=configuracion" onclick="closeUserMenu()">
          <i class="fa-solid fa-user-circle"></i> Mi Perfil
        </a>
        <a class="user-menu-item" href="index.php?route=configuracion" onclick="closeUserMenu()">
          <i class="fa-solid fa-key"></i> Cambiar Contraseña
        </a>
        <div class="user-menu-divider"></div>
        <a class="user-menu-item user-menu-logout" href="index.php?route=logout" onclick="closeUserMenu()">
          <i class="fa-solid fa-right-from-bracket"></i> Cerrar Sesión
        </a>
      </div>
    </div>
  </div>
</header>

<!-- MAIN -->
<main id="main">
  <div class="content-area">
