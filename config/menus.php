<?php
// SIASISTE - Menús por rol (sin mezcla)
// Cada item puede usar 'rol' (unico) o 'roles' (array). Si no se define, visible para todos autenticados.
// Secciones se ocultan si ningún item es visible para el rol actual (ver header.php _hasAccess)

return [
    [
        'label' => 'Inicio', 'icon' => 'fa-house', 'route' => 'dashboard',
        // visible para todos
    ],
    [
        'section' => 'Registro Académico',
        'items' => [
            ['label' => 'Asistencia', 'icon' => 'fa-fingerprint', 'route' => 'asistencia', 'roles' => ['admin','docente','coordinador','tutor','director']],
            ['label' => 'Docentes',   'icon' => 'fa-id-badge', 'route' => 'docentes', 'roles' => ['admin']], // solo admin general
            ['label' => 'Estudiantes','icon' => 'fa-users',  'route' => 'estudiantes', 'roles' => ['admin','docente','tutor','coordinador','director']],
        ]
    ],
    [
        'section' => 'Estructura',
        'items' => [
            ['label' => 'Aulas',       'icon' => 'fa-building', 'route' => 'aulas', 'roles' => ['admin','coordinador','director']],
            ['label' => 'Unidades',    'icon' => 'fa-book', 'route' => 'unidades', 'roles' => ['admin','coordinador','docente','director']],
            ['label' => 'Horarios',    'icon' => 'fa-calendar-days', 'route' => 'horarios', 'roles' => ['admin','coordinador','docente','director']],
            ['label' => 'Programas',   'icon' => 'fa-graduation-cap', 'route' => 'programas', 'roles' => ['admin','coordinador','director']],
            ['label' => 'Especialidades', 'icon' => 'fa-bookmark','route' => 'especialidades', 'roles' => ['admin']],
        ]
    ],
    [
        'section' => 'Administración',
        'items' => [
            ['label' => 'Usuarios',      'icon' => 'fa-user-gear', 'route' => 'usuarios', 'roles' => ['admin']],
            ['label' => 'Motivos',       'icon' => 'fa-tags', 'route' => 'motivos', 'roles' => ['admin']],
            ['label' => 'Roles',         'icon' => 'fa-shield-halved','route' => 'roles', 'roles' => ['admin']],
            ['label' => 'Configuración', 'icon' => 'fa-gear', 'route' => 'configuracion'], // todos pueden ver su perfil
        ]
    ],
    [
        'section' => 'Monitoreo',
        'items' => [
            ['label' => 'Trazabilidad', 'icon' => 'fa-diagram-project', 'route' => 'trazabilidad', 'roles' => ['admin','docente','tutor','psicologo','coordinador','asistenta_social','director']],
            ['label' => 'Alertas',      'icon' => 'fa-triangle-exclamation','route' => 'alertas', 'roles' => ['admin','docente','tutor','psicologo','coordinador','asistenta_social','director']],
            ['label' => 'Reportes',     'icon' => 'fa-chart-line', 'route' => 'reportes', 'roles' => ['admin','coordinador','tutor','director']],
        ]
    ],
    [
        'divider' => true,
    ],
    [
        'label' => 'Salir', 'icon' => 'fa-right-from-bracket', 'route' => 'logout', 'class' => 'logout',
    ],
];
