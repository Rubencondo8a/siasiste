<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-store mr-1 text-primary"></i> Beneficios de Participaci&oacute;n</h1>
</section>

<section class="content">
    <?php
        $tieneCristales = false;
        if (!empty($resumenesPorCurso)) {
            foreach ($resumenesPorCurso as $res) {
                if ($res['cristales'] > 0) { $tieneCristales = true; break; }
            }
        }
    ?>
    <div class="row mb-3">
        <?php if (!empty($resumenesPorCurso)): ?>
            <?php foreach ($resumenesPorCurso as $cursoId => $res): ?>
                <div class="col-lg-4 col-md-6 mb-3 d-flex flex-column">
                    <div class="card card-outline card-primary flex-grow-1">
                        <div class="card-body text-center py-3">
                            <h6 class="text-muted mb-2"><?= htmlspecialchars($res['curso_nombre']) ?> <small>(<?= htmlspecialchars($res['curso_codigo']) ?>)</small></h6>
                            <div class="d-flex justify-content-center align-items-center" style="gap:16px;">
                                <div>
                                    <div class="text-warning" style="font-size:1.6rem;font-weight:700;">
                                        <i class="fas fa-star"></i> <?= $res['total_puntos'] ?>
                                    </div>
                                    <small class="text-muted">Puntos</small>
                                </div>
                                <div style="font-size:1.5rem;color:#dee2e6;">|</div>
                                <div>
                                    <div class="text-info" style="font-size:1.6rem;font-weight:700;">
                                        <i class="fas fa-gem"></i> <?= $res['cristales'] ?>
                                    </div>
                                    <small class="text-muted">Cristales</small>
                                </div>
                            </div>
                            <?php if ($res['sobrante'] > 0): ?>
                                <div class="mt-1">
                                    <small class="text-muted">Faltan <strong><?= 16 - $res['sobrante'] ?></strong> puntos para 1 💎</small>
                                    <div class="progress mt-1" style="height:5px;">
                                        <div class="progress-bar bg-info" style="width:<?= round($res['sobrante'] / 16 * 100) ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (isset($tieneCristales) && !$tieneCristales): ?>
                <div class="col-lg-4 col-md-6 mb-3 d-flex flex-column">
                    <div class="card card-outline card-warning flex-grow-1">
                        <div class="card-body text-center d-flex flex-column justify-content-center py-3">
                            <i class="fas fa-info-circle fa-2x text-warning mb-2"></i>
                            <h6 class="font-weight-bold">Sin cristales disponibles</h6>
                            <p class="text-muted mb-0 small">Aún no tiene cristales 💎 para canjear. Los cristales se obtienen cuando el docente le asigna <strong>16 puntos de participación</strong> en una unidad didáctica.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="callout callout-info">
                    <h5>Sin cursos asignados</h5>
                    <p>Aún no tiene cursos con puntos de participación.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($beneficiosActivos)): ?>
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gift mr-1"></i> Beneficios Disponibles</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($beneficiosActivos as $ben): ?>
                        <?php
                            $icono = 'fa-question-circle';
                            $color = 'primary';
                            if ($ben['tipo'] === 'punto_extra') { $icono = 'fa-star'; $color = 'warning'; }
                            elseif ($ben['tipo'] === 'extension_plazo') { $icono = 'fa-clock'; $color = 'info'; }
                            elseif ($ben['tipo'] === 'examen_extra') { $icono = 'fa-file-alt'; $color = 'success'; }
                        ?>
                        <div class="col-lg-4 col-md-6 mb-3 d-flex flex-column">
                            <div class="card card-outline card-<?= $color ?> flex-grow-1">
                                <div class="card-body text-center d-flex flex-column">
                                    <i class="fas <?= $icono ?> fa-3x text-<?= $color ?> mb-3"></i>
                                    <h5 class="font-weight-bold"><?= htmlspecialchars($ben['nombre']) ?></h5>
                                    <p class="text-muted mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($ben['descripcion'] ?? '') ?>"><?= htmlspecialchars($ben['descripcion'] ?? '') ?></p>
                                    <div class="mb-3">
                                        <span class="badge badge-<?= $color ?>" style="font-size:1rem;padding:6px 14px;">
                                            <i class="fas fa-gem mr-1"></i> <?= (int) $ben['costo_gemas'] ?> cristal(es)
                                        </span>
                                    </div>
                                    <div class="mt-auto">
                                        <?php if (!empty($resumenesPorCurso)): ?>
                                            <div class="form-group">
                                                <label class="small text-muted">Seleccionar curso:</label>
                                                <select class="form-control form-control-sm select-curso" data-beneficio="<?= $ben['id'] ?>" data-costo="<?= (int) $ben['costo_gemas'] ?>">
                                                    <?php foreach ($resumenesPorCurso as $cursoId => $res): ?>
                                                        <option value="<?= $cursoId ?>" <?= $res['cristales'] >= (int) $ben['costo_gemas'] ? '' : 'disabled' ?>>
                                                            <?= htmlspecialchars($res['curso_nombre']) ?> (💎 <?= $res['cristales'] ?>)
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button class="btn btn-<?= $color ?> btn-canjear"
                                                    data-beneficio="<?= $ben['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($ben['nombre']) ?>"
                                                    data-costo="<?= (int) $ben['costo_gemas'] ?>"
                                                    data-tipo="<?= htmlspecialchars($ben['tipo']) ?>"
                                                    data-puntos-bonus="<?= (int) ($ben['puntos_bonus'] ?? 1) ?>">
                                                <i class="fas fa-exchange-alt mr-1"></i> Canjear
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary" disabled>Sin cursos</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($historialCanjes)): ?>
        <div class="card card-outline card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-1"></i> Mis Canjes</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Beneficio</th>
                            <th class="text-center">Cristales</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historialCanjes as $cj): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($cj['created_at'])) ?></td>
                                <td><?= htmlspecialchars($cj['beneficio_nombre']) ?></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><i class="fas fa-gem mr-1"></i><?= (int) $cj['gemas_gastadas'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>



<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script>
$(document).ready(function() {
    $(document).on('click', '.btn-canjear', function() {
        var btn = $(this);
        var beneficioId = btn.data('beneficio');
        var nombreBeneficio = btn.data('nombre');
        var costo = btn.data('costo');
        var tipo = btn.data('tipo');
        var puntosBonus = btn.data('puntos-bonus') || 1;
        var cursoId = $('.select-curso[data-beneficio="' + beneficioId + '"]').val();

        if (!cursoId) {
            AdminModals.showToast('warning', 'Seleccione una Unidad Didáctica.');
            return;
        }

        if (tipo === 'punto_extra') {
            cargarSemanasPuntoExtra(beneficioId, nombreBeneficio, costo, cursoId, puntosBonus, btn);
            return;
        }

        Swal.fire({
            title: '¿Canjear "' + nombreBeneficio + '"?',
            text: 'Se gastarán ' + costo + ' cristal(es) de este curso.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-gem mr-1"></i> Sí, canjear',
            cancelButtonText: 'Cancelar'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            ejecutarCanje(beneficioId, cursoId, 0, btn, nombreBeneficio, costo);
        });
    });

    function cargarSemanasPuntoExtra(beneficioId, nombreBeneficio, costo, cursoId, puntosBonus, btn) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Cargando...');

        $.ajax({
            url: '<?= BASE_URL ?>estudiante/semanasParaPuntoExtra',
            type: 'GET',
            data: { curso_id: cursoId },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-1"></i> Canjear');

                if (!res.success || !res.semanas || res.semanas.length === 0) {
                    AdminModals.showToast('warning', 'No hay semanas disponibles en este curso.');
                    return;
                }

                var filas = '';
                var hayDisponibles = false;
                for (var i = 0; i < res.semanas.length; i++) {
                    var s = res.semanas[i];
                    var disponible = s.tiene_notas && !s.ya_canjeo;
                    if (disponible) hayDisponibles = true;
                    var clase = disponible ? '' : ' style="opacity:.5;"';
                    var accion = disponible
                        ? '<button class="btn btn-sm btn-success btn-seleccionar-semana" data-semana="' + s.semana_id + '" data-criterio="' + (s.criterio_mas_baja ? s.criterio_mas_baja.nombre : '-') + '" data-nota="' + (s.nota_mas_baja !== null ? s.nota_mas_baja : '-') + '"><i class="fas fa-check"></i> Seleccionar</button>'
                        : '<span class="text-muted small">' + (s.ya_canjeo ? '<i class="fas fa-check-circle text-success"></i> Ya canjeado' : '<i class="fas fa-ban text-secondary"></i> Sin notas') + '</span>';
                    filas += '<tr' + clase + '>'
                        + '<td class="text-center">' + s.numero + '</td>'
                        + '<td>' + s.nombre + '</td>'
                        + '<td class="text-center">' + (s.nota_mas_baja !== null ? s.nota_mas_baja.toFixed(1) : '-') + '</td>'
                        + '<td class="text-center">' + (s.criterio_mas_baja ? s.criterio_mas_baja.nombre : '-') + '</td>'
                        + '<td class="text-center">' + (disponible ? '+<strong>' + puntosBonus + '</strong> → ' + (s.nota_mas_baja !== null ? Math.min(20, s.nota_mas_baja + puntosBonus).toFixed(1) : '-') : '') + '</td>'
                        + '<td class="text-center">' + accion + '</td>'
                        + '</tr>';
                }

                if (!hayDisponibles) {
                    AdminModals.showToast('info', 'No hay semanas disponibles para aplicar punto extra.');
                    return;
                }

                var html = '<div class="table-responsive">'
                    + '<table class="table table-bordered table-sm mb-0">'
                    + '<thead class="thead-light"><tr>'
                    + '<th>#</th><th>Semana</th><th class="text-center">Nota más baja</th><th>Criterio</th><th class="text-center">Nuevo valor</th><th class="text-center">Acción</th>'
                    + '</tr></thead><tbody>' + filas + '</tbody></table></div>';

                Swal.fire({
                    title: '<i class="fas fa-star text-warning mr-1"></i> ' + nombreBeneficio,
                    html: '<p class="mb-2">Se sumarán <strong>+' + puntosBonus + ' punto(s)</strong> a la nota más baja de la semana seleccionada.</p><p class="mb-2 small text-muted">Costo: ' + costo + ' 💎 cristal(es)</p>' + html,
                    width: 700,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cerrar',
                    cancelButtonColor: '#6c757d',
                    customClass: { htmlContainer: 'swal-extra-wide' },
                    didOpen: function() {
                        $(document).off('click', '.btn-seleccionar-semana').on('click', '.btn-seleccionar-semana', function() {
                            var semanaId = $(this).data('semana');
                            var criterioNombre = $(this).data('criterio');
                            var notaActual = $(this).data('nota');

                            Swal.close();

                            Swal.fire({
                                title: '¿Confirmar punto extra?',
                                html: 'Se sumarán <strong>+' + puntosBonus + ' punto(s)</strong> al criterio <strong>"' + criterioNombre + '"</strong> de la semana seleccionada.<br><br>'
                                    + 'Nota actual: <strong>' + notaActual + '</strong> → Nuevo: <strong>' + Math.min(20, parseFloat(notaActual) + puntosBonus).toFixed(1) + '</strong>',
                                icon: 'question',
                                showCancelButton: true,
                                confirmButtonColor: '#ffc107',
                                cancelButtonColor: '#6c757d',
                                confirmButtonText: '<i class="fas fa-star mr-1"></i> Aplicar punto extra',
                                cancelButtonText: 'Cancelar'
                            }).then(function(result) {
                                if (result.isConfirmed) {
                                    ejecutarCanje(beneficioId, cursoId, semanaId, btn, nombreBeneficio, costo);
                                }
                            });
                        });
                    }
                });
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-1"></i> Canjear');
                AdminModals.showToast('error', 'Error al cargar semanas.');
            }
        });
    }

    function ejecutarCanje(beneficioId, cursoId, semanaId, btn, nombreBeneficio, costo) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...');

        $.ajax({
            url: '<?= BASE_URL ?>estudiante/canjearBeneficio',
            type: 'POST',
            data: {
                beneficio_id: beneficioId,
                curso_id: cursoId,
                semana_id: semanaId,
                _csrf_token: $('meta[name=csrf-token]').attr('content')
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        title: '¡Canje exitoso!',
                        text: res.message,
                        icon: 'success',
                        confirmButtonText: '¡Genial!'
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    AdminModals.showToast('error', res.message);
                    btn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-1"></i> Canjear');
                }
            },
            error: function() {
                AdminModals.showToast('error', 'Error de conexión.');
                btn.prop('disabled', false).html('<i class="fas fa-exchange-alt mr-1"></i> Canjear');
            }
        });
    }
});
</script>
