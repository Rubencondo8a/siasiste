<?php

use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

Router::addGlobalMiddleware(CsrfMiddleware::class);
Router::csrfExempt('login');
Router::csrfExempt('logout');

$auth = AuthMiddleware::class;
$admin = ['App\\Middleware\\AuthMiddleware', 'admin'];
// Roles combinados (evita mezcla de menús)
$adminCoord = function(){ AuthMiddleware::role(['admin','coordinador','director']); }; // ver
$adminCoordManage = function(){ AuthMiddleware::role(['admin','coordinador']); }; // gestionar (director solo lectura)
$adminCoordDocente = function(){ AuthMiddleware::role(['admin','coordinador','docente','director']); }; // ver
$estudiantesView = function(){ AuthMiddleware::role(['admin','docente','tutor','coordinador','director']); };
$reportesView = function(){ AuthMiddleware::role(['admin','coordinador','tutor','director']); };
$asistenciaView = function(){ AuthMiddleware::role(['admin','docente','tutor','coordinador','director']); };

// ---- Auth ----
Router::add('login', 'App\\Controllers\\AuthController', 'loginForm');
Router::add('logout', 'App\\Controllers\\AuthController', 'logout');
Router::add('register', 'App\\Controllers\\AuthController', 'register', $admin);

// ---- Dashboard ----
Router::add('dashboard', 'App\\Controllers\\DashboardController', 'index', $auth);
Router::add('dashboard', 'App\\Controllers\\DashboardController', 'getStats', $auth, 'stats');
Router::add('dashboard', 'App\\Controllers\\DashboardController', 'getChartData', $auth, 'chart');

// ---- Docentes ---- Solo admin general
Router::add('docentes', 'App\\Controllers\\DocenteController', 'index', $admin);
Router::add('docentes', 'App\\Controllers\\DocenteController', 'all', $admin, 'all');
Router::add('docentes', 'App\\Controllers\\DocenteController', 'store', $admin, 'store');
Router::add('docentes', 'App\\Controllers\\DocenteController', 'update', $admin, 'update');
Router::add('docentes', 'App\\Controllers\\DocenteController', 'destroy', $admin, 'delete');
Router::add('docentes', 'App\\Controllers\\DocenteController', 'search', $admin, 'search');

// ---- Estudiantes ---- solo roles con alumnos
Router::add('estudiantes', 'App\\Controllers\\EstudianteController', 'index', $estudiantesView);
Router::add('estudiantes', 'App\\Controllers\\EstudianteController', 'all', $estudiantesView, 'all');
Router::add('estudiantes', 'App\\Controllers\\EstudianteController', 'store', $admin, 'store');
Router::add('estudiantes', 'App\\Controllers\\EstudianteController', 'update', $admin, 'update');
Router::add('estudiantes', 'App\\Controllers\\EstudianteController', 'destroy', $admin, 'delete');

// ---- Aulas ---- ver (admin/coordinador/director), gestionar solo admin/coordinador
Router::add('aulas', 'App\\Controllers\\AulaController', 'index', $adminCoord);
Router::add('aulas', 'App\\Controllers\\AulaController', 'all', $adminCoord, 'all');
Router::add('aulas', 'App\\Controllers\\AulaController', 'store', $adminCoordManage, 'store');
Router::add('aulas', 'App\\Controllers\\AulaController', 'update', $adminCoordManage, 'update');
Router::add('aulas', 'App\\Controllers\\AulaController', 'destroy', $adminCoordManage, 'delete');

// ---- Unidades ---- ver admin/coordinador/docente/director, gestionar solo admin/coordinador
Router::add('unidades', 'App\\Controllers\\UnidadController', 'index', $adminCoordDocente);
Router::add('unidades', 'App\\Controllers\\UnidadController', 'all', $adminCoordDocente, 'all');
Router::add('unidades', 'App\\Controllers\\UnidadController', 'store', $adminCoordManage, 'store');
Router::add('unidades', 'App\\Controllers\\UnidadController', 'update', $adminCoordManage, 'update');
Router::add('unidades', 'App\\Controllers\\UnidadController', 'destroy', $adminCoordManage, 'delete');

// ---- Horarios ---- ver admin/coordinador/docente/director (solo lectura director/docente), gestionar solo admin/coordinador
Router::add('horarios', 'App\\Controllers\\HorarioController', 'index', $adminCoordDocente);
Router::add('horarios', 'App\\Controllers\\HorarioController', 'all', $adminCoordDocente, 'all');
Router::add('horarios', 'App\\Controllers\\HorarioController', 'store', $adminCoordManage, 'store');
Router::add('horarios', 'App\\Controllers\\HorarioController', 'update', $adminCoordManage, 'update');
Router::add('horarios', 'App\\Controllers\\HorarioController', 'destroy', $adminCoordManage, 'delete');

// ---- Asistencia ---- admin/docente/tutor/coordinador
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'index', $asistenciaView);
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'all', $asistenciaView, 'all');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'store', $asistenciaView, 'store');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'update', $asistenciaView, 'update');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'destroy', $admin, 'delete');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'iniciarJornada', $asistenciaView, 'iniciar-jornada');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'motivos', $asistenciaView, 'motivos');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'porUnidad', $asistenciaView, 'porUnidad');
Router::add('asistencia', 'App\\Controllers\\AsistenciaController', 'ultimaFecha', $asistenciaView, 'ultimaFecha');

// ---- Alertas ----
Router::add('alertas', 'App\\Controllers\\AlertaController', 'index', $auth);
Router::add('alertas', 'App\\Controllers\\AlertaController', 'activas', $auth, 'activas');
Router::add('alertas', 'App\\Controllers\\AlertaController', 'atender', $auth, 'atender');
Router::add('alertas', 'App\\Controllers\\AlertaController', 'derivar', $auth, 'derivar');

// ---- Reportes ---- admin/coordinador/tutor
Router::add('reportes', 'App\\Controllers\\ReporteController', 'index', $reportesView);
Router::add('reportes', 'App\\Controllers\\ReporteController', 'getResumen', $reportesView, 'getResumen');
Router::add('reportes', 'App\\Controllers\\ReporteController', 'exportCSV', $admin, 'exportCSV');

// ---- Matriculas ---- ver admin/coordinador/director, gestionar solo admin
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'index', $adminCoord);
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'all', $adminCoord, 'all');
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'store', $admin, 'store');
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'programas', $adminCoord, 'programas');
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'setSemestre', $admin, 'set-semestre');
Router::add('matriculas', 'App\\Controllers\\MatriculaController', 'periodos', $adminCoord, 'periodos');

// ---- Trazabilidad ----
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'index', $auth);
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'perfil', $auth, 'perfil');
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'historial', $auth, 'historial');
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'asistencias', $auth, 'asistencias');
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'buscar', $auth, 'buscar');
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'avanzar', $auth, 'avanzar');
Router::add('trazabilidad', 'App\\Controllers\\TrazabilidadController', 'retirar', $auth, 'retirar');

// ---- Configuracion ----
Router::add('configuracion', 'App\\Controllers\\ConfiguracionController', 'index', $auth);
Router::add('configuracion', 'App\\Controllers\\ConfiguracionController', 'perfil', $auth, 'perfil');
Router::add('configuracion', 'App\\Controllers\\ConfiguracionController', 'contrasena', $auth, 'contrasena');

// ---- Usuarios ----
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'index', $admin);
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'all', $admin, 'all');
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'roles', $admin, 'roles');
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'store', $admin, 'store');
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'update', $admin, 'update');
Router::add('usuarios', 'App\\Controllers\\UsuariosController', 'destroy', $admin, 'delete');

// ---- Especialidades ----
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'index', $admin);
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'all', $admin, 'all');
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'activas', $admin, 'activas');
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'store', $admin, 'store');
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'update', $admin, 'update');
Router::add('especialidades', 'App\\Controllers\\EspecialidadController', 'destroy', $admin, 'delete');

// ---- Programas ---- admin/coordinador (menú coordinador)
Router::add('programas', 'App\\Controllers\\ProgramaController', 'index', $adminCoord);
Router::add('programas', 'App\\Controllers\\ProgramaController', 'all', $adminCoord, 'all');
Router::add('programas', 'App\\Controllers\\ProgramaController', 'activos', $adminCoord, 'activos');
Router::add('programas', 'App\\Controllers\\ProgramaController', 'store', $admin, 'store');
Router::add('programas', 'App\\Controllers\\ProgramaController', 'update', $admin, 'update');
Router::add('programas', 'App\\Controllers\\ProgramaController', 'destroy', $admin, 'delete');

// ---- Motivos ----
Router::add('motivos', 'App\\Controllers\\MotivoController', 'index', $admin);
Router::add('motivos', 'App\\Controllers\\MotivoController', 'all', $admin, 'all');
Router::add('motivos', 'App\\Controllers\\MotivoController', 'store', $admin, 'store');
Router::add('motivos', 'App\\Controllers\\MotivoController', 'update', $admin, 'update');
Router::add('motivos', 'App\\Controllers\\MotivoController', 'destroy', $admin, 'delete');

// ---- Roles ----
Router::add('roles', 'App\\Controllers\\RolesController', 'index', $admin);
Router::add('roles', 'App\\Controllers\\RolesController', 'all', $admin, 'all');
Router::add('roles', 'App\\Controllers\\RolesController', 'store', $admin, 'store');
Router::add('roles', 'App\\Controllers\\RolesController', 'update', $admin, 'update');
Router::add('roles', 'App\\Controllers\\RolesController', 'destroy', $admin, 'delete');

// ---- Import ---- solo admin/coordinador
Router::add('import', 'App\\Controllers\\ImportController', 'template', $adminCoord, 'template');
Router::add('import', 'App\\Controllers\\ImportController', 'importEstudiantes', $admin, 'importEstudiantes');
Router::add('import', 'App\\Controllers\\ImportController', 'importDocentes', $admin, 'importDocentes');
