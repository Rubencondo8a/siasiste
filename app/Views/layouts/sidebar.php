<?php
$role = $_SESSION['user_rol'] ?? '';
$controller = $_GET['controller'] ?? '';
$action = $_GET['action'] ?? '';

function sidebarLink(string $url, string $icon, string $text, string $activeController, string $activeAction, string $currentController, string $currentAction): string
{
    $isActive = ($currentController === $activeController && $currentAction === $activeAction);
    $activeClass = $isActive ? ' active' : '';
    return '<li class="nav-item"><a href="' . BASE_URL . $url . '" class="nav-link' . $activeClass . '"><i class="nav-icon ' . $icon . '"></i><p>' . $text . '</p></a></li>';
}

function sidebarLinkMulti(string $url, string $icon, string $text, string $activeController, array $activeActions, string $currentController, string $currentAction): string
{
    $isActive = ($currentController === $activeController && in_array($currentAction, $activeActions));
    $activeClass = $isActive ? ' active' : '';
    return '<li class="nav-item"><a href="' . BASE_URL . $url . '" class="nav-link' . $activeClass . '"><i class="nav-icon ' . $icon . '"></i><p>' . $text . '</p></a></li>';
}
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="<?= BASE_URL . ($role === 'admin' ? 'admin' : ($role === 'docente' ? 'docente' : 'estudiante')) ?>/inicio" class="brand-link text-center">
        <img src="<?= BASE_URL ?>favicon.svg" alt="Sigametec" class="brand-image img-circle elevation-1" style="width:34px;height:34px;">
        <span class="brand-text font-weight-light" style="font-size: 1.1rem;"><b>Sigametec</b></span>
    </a>

    <div class="sidebar">
        <?php
        $roleLabels = [
            'admin'      => ['label' => 'Administrador', 'color' => 'danger',  'icon' => 'fas fa-user-shield'],
            'docente'    => ['label' => 'Docente',       'color' => 'primary', 'icon' => 'fas fa-chalkboard-teacher'],
            'estudiante' => ['label' => 'Estudiante',    'color' => 'success', 'icon' => 'fas fa-user-graduate'],
        ];
        $roleInfo = $roleLabels[$role] ?? ['label' => ucfirst($role), 'color' => 'secondary', 'icon' => 'fas fa-user'];
        ?>
        <div class="user-panel d-flex align-items-center px-3 py-2 mb-2" style="border-bottom:1px solid rgba(255,255,255,.1);">
            <img src="<?= profilePhoto($_SESSION['user_foto'] ?? null) ?>" alt="Foto"
                 class="img-circle elevation-1" style="width:36px;height:36px;object-fit:cover;">
            <div class="ml-2">
                <div class="text-white font-weight-bold" style="font-size:.9rem;line-height:1.15;"><?= htmlspecialchars(($_SESSION['user_nombres'] ?? '') ?: ($_SESSION['user_nombre'] ?? 'Usuario')) ?></div>
                <div class="text-white-50" style="font-size:.72rem;line-height:1.15;"><?= htmlspecialchars($_SESSION['user_apellidos'] ?? '') ?></div>
                <span class="badge badge-<?= $roleInfo['color'] ?> mt-1" style="font-size:.7rem;"><?= $roleInfo['label'] ?></span>
            </div>
        </div>
        <?php
        $periodoActivo = null;
        try {
            static $_periodoCache = null;
            if ($_periodoCache === null) {
                $db = (new Database())->getConnection();
                $stmtPeriodo = $db->query("SELECT id, nombre, fecha_inicio, fecha_fin FROM periodos WHERE estado = 1 ORDER BY id DESC LIMIT 1");
                $_periodoCache = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);
            }
            $periodoActivo = $_periodoCache;
        } catch (Exception $e) {
            $periodoActivo = null;
        }
        ?>
        <div class="px-3 py-2 mb-2 periodo-activo">
            <small class="text-uppercase text-white-50 d-block mb-1" style="font-size:.65rem;letter-spacing:.05em;">
                <i class="fas fa-calendar-check mr-1"></i><span class="periodo-label">Periodo Activo</span>
            </small>
            <?php if ($periodoActivo): ?>
                <div class="text-white font-weight-bold" style="font-size:.85rem;"><?= htmlspecialchars($periodoActivo['nombre']) ?></div>
                <small class="text-white-50 periodo-fechas" style="font-size:.7rem;">
                    <?= date('d/m/Y', strtotime($periodoActivo['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($periodoActivo['fecha_fin'])) ?>
                </small>
            <?php else: ?>
                <div class="text-warning" style="font-size:.8rem;"><i class="fas fa-exclamation-triangle mr-1"></i>Sin periodo activo</div>
            <?php endif; ?>
        </div>

        <nav class="mt-2" style="padding-bottom:28px;">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                <?php if ($role === 'admin'): ?>

                    <li class="nav-header">General</li>

                    <?= sidebarLink(
                        'admin/inicio',
                        'fas fa-tachometer-alt',
                        'Inicio',
                        'admin', 'inicio',
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/periodos',
                        'fas fa-calendar-alt',
                        'Periodos Académicos',
                        'admin',
                        ['periodos', 'formularioPeriodo', 'guardarPeriodo', 'eliminarPeriodo', 'detallePeriodo'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Gestión Académica</li>

                    <?= sidebarLinkMulti(
                        'admin/carreras',
                        'fas fa-graduation-cap',
                        'Carreras',
                        'admin',
                        ['carreras', 'formularioCarrera', 'guardarCarrera', 'eliminarCarrera', 'detalleCarrera'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/planes',
                        'fas fa-clipboard-list',
                        'Planes de Estudio',
                        'admin',
                        ['planes', 'formularioPlan', 'guardarPlan', 'eliminarPlan', 'detallePlan'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/cursos',
                        'fas fa-book-open',
                        'Cursos',
                        'admin',
                        ['cursos', 'formularioCurso', 'guardarCurso', 'eliminarCurso', 'detalleCurso'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/asignaciones',
                        'fas fa-link',
                        'Asignaciones',
                        'admin',
                        ['asignaciones', 'formularioAsignacion', 'guardarAsignacion', 'eliminarAsignacion', 'detalleAsignacion'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/ambientes',
                        'fas fa-door-open',
                        'Ambientes',
                        'admin',
                        ['ambientes', 'formularioAmbiente', 'guardarAmbiente', 'eliminarAmbiente', 'detalleAmbiente'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Comunidad</li>

                    <?= sidebarLinkMulti(
                        'admin/usuarios',
                        'fas fa-users-cog',
                        'Usuarios',
                        'admin',
                        ['usuarios', 'formularioUsuario', 'guardarUsuario', 'eliminarUsuario', 'detalleUsuario'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/estudiantes',
                        'fas fa-user-graduate',
                        'Estudiantes',
                        'admin',
                        ['estudiantes', 'formularioEstudiante', 'guardarEstudiante', 'eliminarEstudiante', 'detalleEstudiante'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Sistema</li>

                    <?= sidebarLinkMulti(
                        'admin/configurarCorreo',
                        'fas fa-envelope-open-text',
                        'Configuración de Correo',
                        'admin',
                        ['configurarCorreo', 'guardarConfigCorreo'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/nivelesMago',
                        'fas fa-hat-wizard',
                        'Niveles de Magia',
                        'admin',
                        ['nivelesMago', 'formularioNivel', 'guardarNivel', 'eliminarNivel', 'detalleNivel'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'admin/beneficios',
                        'fas fa-gift',
                        'Beneficios',
                        'admin',
                        ['beneficios', 'formularioBeneficio', 'guardarBeneficio', 'eliminarBeneficio', 'detalleBeneficio'],
                        $controller, $action
                    ); ?>

                <?php elseif ($role === 'docente'): ?>

                    <li class="nav-header">General</li>

                    <?= sidebarLink(
                        'docente/inicio',
                        'fas fa-tachometer-alt',
                        'Inicio',
                        'docente', 'inicio',
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/semanas',
                        'fas fa-calendar-week',
                        'Calificar',
                        'docente',
                        ['semanas', 'semanasCurso', 'formularioSemana', 'guardarSemana', 'eliminarSemana', 'criterios', 'formularioCriterio', 'guardarCriterio', 'eliminarCriterio', 'notas', 'guardarNotas', 'alternarBloqueoCriterio', 'reporteResumenCurso', 'reporteDetalleEstudiante', 'obtenerEstudiantesCurso', 'exportarReporteCurso', 'exportarNotasSemana', 'alternarBloqueoSemana', 'grupos', 'generarGrupos', 'regenerarGrupos', 'eliminarGrupos', 'copiarGrupos', 'moverEstudiante'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLink(
                        'examen/lista',
                        'fas fa-file-signature',
                        'Exámenes',
                        'examen', 'lista',
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/discusiones',
                        'fas fa-comments',
                        'Debates',
                        'docente',
                        ['discusiones', 'chatDiscusion', 'enviarMensaje', 'obtenerMensajes', 'cerrarDiscusion', 'reabrirDiscusion', 'procesarNotificacionDebate', 'anularDiscusion', 'editarDiscusion', 'editarMensaje', 'aprobarMensaje'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Docencia</li>

                    <?= sidebarLinkMulti(
                        'docente/misCursos',
                        'fas fa-user-graduate',
                        'Mis Cursos',
                        'docente',
                        ['misCursos', 'estudiantesCurso', 'formularioAgregarEstudiantes', 'guardarEstudiantes', 'eliminarEstudianteCurso'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLink(
                        'docente/solicitudesExamen',
                        'fas fa-hat-wizard',
                        'Solicitudes de Examen',
                        'docente', 'solicitudesExamen',
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/asistencias',
                        'fas fa-user-check',
                        'Asistencia',
                        'docente',
                        ['asistencias', 'tomarAsistencia', 'guardarAsistencia', 'reporteAsistencia', 'exportarAsistenciaCurso', 'exportarAsistenciaCursoDetalle', 'formularioRostro', 'guardarRostro', 'eliminarRostro', 'obtenerRostrosCurso'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/registrarPuntos',
                        'fas fa-gem',
                        'Participaci&oacute;n',
                        'docente',
                        ['registrarPuntos', 'guardarPuntosDirecto', 'asistenciaHoy', 'activarParticipacion', 'estadoParticipacion', 'marcarParticipacion', 'asignarMotivoParticipacion', 'cerrarParticipacion'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Administraci&oacute;n</li>

                    <?= sidebarLink(
                        'docente/motivos',
                        'fas fa-tags',
                        'Motivos de Puntos',
                        'docente', 'motivos',
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/apreciaciones',
                        'fas fa-sliders-h',
                        'Apreciaci&oacute;n',
                        'docente',
                        ['apreciaciones', 'apreciarEstudiante', 'eliminarApreciacion', 'historialApreciaciones'],
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Informes</li>

                    <?= sidebarLinkMulti(
                        'docente/reportes',
                        'fas fa-chart-bar',
                        'Reportes',
                        'docente',
                        ['reportes', 'reporteNotasPorSemana', 'reportePromediosEstudiantes', 'reporteRendimientoGrupo'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'docente/horarios',
                        'fas fa-calendar-alt',
                        'Horario',
                        'docente',
                        ['horarios', 'guardarHorario', 'eliminarHorario', 'formularioHorario'],
                        $controller, $action
                    ); ?>

                <?php elseif ($role === 'estudiante'): ?>

                    <li class="nav-header">General</li>

                    <?= sidebarLink(
                        'estudiante/inicio',
                        'fas fa-tachometer-alt',
                        'Inicio',
                        'estudiante', 'inicio',
                        $controller, $action
                    ); ?>

                    <?= sidebarLink(
                        'estudiante/miRostro',
                        'fas fa-user-circle',
                        'Mi Rostro',
                        'estudiante', 'miRostro',
                        $controller, $action
                    ); ?>

                    <li class="nav-header">Académico</li>

                    <?= sidebarLinkMulti(
                        'estudiante/horarios',
                        'fas fa-calendar-alt',
                        'Mi Horario',
                        'estudiante',
                        ['horarios', 'informacionCurso'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLinkMulti(
                        'estudiante/misCursos',
                        'fas fa-book',
                        'Mis Cursos',
                        'estudiante',
                        ['misCursos', 'detalleCurso', 'detalleCursoAjax', 'verSemanaAjax'],
                        $controller, $action
                    ); ?>

                    <?= sidebarLink(
                        'examen/misExamenes',
                        'fas fa-file-signature',
                        'Mis Ex&aacute;menes',
                        'examen', 'misExamenes',
                        $controller, $action
                    ); ?>

                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>
