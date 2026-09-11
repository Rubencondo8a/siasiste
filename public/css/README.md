# SIASISTE - Arquitectura CSS Modular

Estructura escalable basada en **ITCSS 7-1** simplificada.

```
public/css/
├── appAdmin.css              # ENTRY (solo @imports, no reglas)
├── appAdmin.legacy.css       # backup monolito
├── base/
│   ├── variables.css         # :root tokens (colores, radios, sombras, --sidebar-w)
│   └── reset.css             # *, body
├── layout/
│   ├── sidebar.css           # #sidebar, .sidebar-nav, .sidebar-footer (Salir abajo), collapsed, overlay
│   ├── topbar.css            # #topbar, .user-menu
│   └── main.css              # #main, .content-area
├── components/
│   ├── cards.css             # .dashboard-card, .stat-card, .chart-card, .report-card, .config-card
│   ├── tables.css            # .dataTables_wrapper, table.dataTable
│   ├── forms.css             # .form-control, .select2
│   ├── buttons.css           # .btn, .btn-accion-asist, .btn-login
│   ├── badges.css            # .badge-status
│   ├── calendar.css          # .horario-calendar, .cal-grid
│   ├── modals.css            # .modal-content
│   └── timeline.css          # .timeline + login legacy fallback
├── utilities/
│   ├── helpers.css           # .color-swatch, scrollbar, .blue/.green
│   ├── responsive.css        # @media 991/768/575/1400
│   └── print.css             # @media print
├── vendors/
│   └── sweetalert.css        # overrides Swal2
├── login-siasiste.css        # login moderno aislado (no importado en appAdmin)
└── README.md
```

## Reglas para escalar
1. **Variables**: editar solo `base/variables.css`
2. **Nuevo componente**: crear `components/nuevo.css` y agregar `@import url('./components/nuevo.css');` en `appAdmin.css`
3. **No agregar reglas en `appAdmin.css`** - solo imports
4. Login usa su propio CSS independiente para no contaminar admin

## Build opcional (producción)
Concatenar en un solo archivo minificado si se desea:
```bash
cat base/*.css layout/*.css components/*.css utilities/*.css vendors/*.css > appAdmin.bundle.css
```
