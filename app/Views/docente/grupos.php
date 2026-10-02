<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php
$cerrada = (int) ($semana['cerrada'] ?? 0);
$existenGrupos = !empty($grupos);
$nombreRubro = $criterioGrupal['nombre'] ?? 'Trabajo Grupal';
$porcentajeRubro = $criterioGrupal ? (float) $criterioGrupal['porcentaje'] : '';
?>

<?php if ($cerrada): ?>
<div class="alert alert-danger">
    <i class="fas fa-lock"></i> Esta semana está cerrada. No se pueden generar ni modificar equipos.
</div>
<?php endif; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Equipos - Semana <?= $semana['numero_semana'] ?>: <?= htmlspecialchars($semana['nombre']) ?></h1>
        <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $semana['curso_id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Semanas
        </a>
    </div>
</section>

<section class="content">
    <?php if ($existenGrupos): ?>
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-users text-info mr-2"></i>
                            <strong>Distribución actual:</strong>
                        </div>
                        <p class="mb-1">
                            <span class="badge badge-info"><?= count($grupos) ?> grupos</span>
                            <span class="badge badge-secondary"><?= count($estudiantes) ?> estudiantes</span>
                            <?php if ($criterioGrupal): ?>
                                <span class="badge badge-success">
                                    Rubro: <?= htmlspecialchars($criterioGrupal['nombre']) ?> (<?= number_format((float) $criterioGrupal['porcentaje'], 0) ?>%)
                                </span>
                            <?php endif; ?>
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Re-generar mezcla los grupos aleatoriamente y <strong>conserva las notas grupales</strong> ya registradas.
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <?php if (!$cerrada): ?>
                <div class="btn-group btn-block">
                    <button type="button" class="btn btn-warning btn-block" id="btnRegenerar">
                        <i class="fas fa-random"></i> Re-generar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnEliminarGrupos" title="Eliminar grupos y el rubro grupal">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <form id="formRegenerar" action="<?= BASE_URL ?>docente/regenerarGrupos" method="POST" class="d-none">
                    <?= csrfField() ?>
                    <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
                </form>
                <form id="formEliminarGrupos" action="<?= BASE_URL ?>docente/eliminarGrupos" method="POST" class="d-none">
                    <?= csrfField() ?>
                    <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
                </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$cerrada): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-cog"></i> <?= $existenGrupos ? 'Configurar y re-generar grupos' : 'Generar grupos aleatoriamente' ?></h3>
        </div>
        <div class="card-body">
            <form action="<?= BASE_URL ?>docente/generarGrupos" method="POST" id="formGenerarGrupos">
                <?= csrfField() ?>
                <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">

                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="num_grupos">Número de grupos *</label>
                            <input type="number" class="form-control" id="num_grupos" name="num_grupos" required
                                   min="1" max="<?= max(1, count($estudiantes)) ?>"
                                   step="1"
                                   value="<?= $existenGrupos ? count($grupos) : '' ?>"
                                   placeholder="Ej: 4">
                            <small class="text-muted">Máximo: <?= count($estudiantes) ?> (estudiantes del curso)</small>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="porcentaje">% del rubro grupal *</label>
                            <input type="number" class="form-control" id="porcentaje" name="porcentaje" required
                                   step="0.01" min="0.01" max="<?= max(0.01, $porcentajeDisponible) ?>"
                                   value="<?= $porcentajeRubro !== '' ? number_format((float) $porcentajeRubro, 2, '.', '') : '' ?>"
                                   placeholder="Ej: 30">
                            <small class="text-muted">Disponible: <strong id="dispPct"><?= number_format($porcentajeDisponible, 0) ?>%</strong></small>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="nombre_rubro">Nombre del rubro grupal</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="nombre_rubro" name="nombre_rubro"
                                       maxlength="255" value="<?= htmlspecialchars($nombreRubro) ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-success" id="btnActualizarNombre" title="Actualizar nombre"><i class="fas fa-check"></i></button>
                                    <button type="button" class="btn btn-secondary" id="btnRestaurarNombre" title="Restaurar nombre original"><i class="fas fa-undo"></i></button>
                                </div>
                            </div>
                            <small class="text-muted">
                                Se crea (o actualiza) automáticamente como rubro de calificación en esta semana.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <?php if ($porcentajeDisponible <= 0 && !$criterioGrupal): ?>
                            <div class="alert alert-danger py-2">
                                <i class="fas fa-exclamation-triangle"></i> La semana ya está completa al 100%. Reduzca el porcentaje de otro criterio para habilitar el rubro grupal.
                            </div>
                        <?php elseif ($existenGrupos): ?>
                            <div class="alert alert-warning py-2 mb-2">
                                <i class="fas fa-exclamation-triangle"></i> Al guardar se <strong>re-generará</strong> la distribución de los grupos (las notas grupales se conservan).
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info py-2 mb-2">
                                <i class="fas fa-info-circle"></i> Los estudiantes del curso se repartirán de forma aleatoria y balanceada en los <?= count($estudiantes) ?> estudiantes.
                            </div>
                        <?php endif; ?>
                        <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center">
                            <?php $puedeCopiar = $porcentajeDisponible > 0 || $criterioGrupal; ?>
                            <button type="button" class="btn btn-info mb-2 mb-sm-0 mr-sm-2" id="btnCopiarGrupos"
                                    title="<?= $puedeCopiar ? 'Copiar grupos de otra semana' : 'No hay porcentaje disponible para el rubro grupal' ?>"
                                    <?= $puedeCopiar ? '' : 'disabled' ?>>
                                <i class="fas fa-copy"></i> Copiar desde otra semana
                            </button>
                            <button type="submit" class="btn btn-warning" <?= ($porcentajeDisponible <= 0 && !$criterioGrupal) ? 'disabled' : '' ?>>
                                <i class="fas fa-users"></i> <?= $existenGrupos ? 'Re-generar y guardar' : 'Generar grupos aleatorios' ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($existenGrupos): ?>
    <div class="row">
        <?php foreach ($grupos as $grupo): ?>
        <div class="col-md-6 col-xl-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center bg-light">
                    <strong><i class="fas fa-users mr-1"></i> <?= htmlspecialchars($grupo['nombre']) ?></strong>
                    <span class="badge badge-pill badge-secondary"><?= count($grupo['integrantes']) ?> integrantes</span>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($grupo['integrantes'] as $integrante): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-1 small">
                            <span><?= htmlspecialchars($integrante['apellidos'] . ', ' . $integrante['nombres']) ?></span>
                            <?php if (!$cerrada): ?>
                            <form action="<?= BASE_URL ?>docente/moverEstudiante" method="POST" class="m-0 form-mover">
                                <?= csrfField() ?>
                                <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
                                <input type="hidden" name="estudiante_id" value="<?= $integrante['id'] ?>">
                                <div class="input-group input-group-sm">
                                    <select name="grupo_id" class="custom-select custom-select-sm" style="width:90px;">
                                        <?php foreach ($grupos as $destino): ?>
                                            <option value="<?= $destino['id'] ?>" <?= $destino['id'] == $grupo['id'] ? 'selected' : '' ?>>
                                                <?= $destino['numero'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit" title="Mover de grupo">
                                            <i class="fas fa-arrows-alt-h"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($grupo['integrantes'])): ?>
                        <li class="list-group-item text-muted small">Sin integrantes</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($sinGrupo)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-plus text-warning"></i> Estudiantes sin grupo (<?= count($sinGrupo) ?>)</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0 nodt">
                <tbody>
                    <?php foreach ($sinGrupo as $est): ?>
                    <tr>
                        <td class="align-middle">
                            <?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?>
                        </td>
                        <td class="text-right">
                            <?php if (!$cerrada && !empty($grupos)): ?>
                            <form action="<?= BASE_URL ?>docente/moverEstudiante" method="POST" class="d-inline">
                                <?= csrfField() ?>
                                <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
                                <input type="hidden" name="estudiante_id" value="<?= $est['id'] ?>">
                                <div class="input-group input-group-sm" style="max-width:220px;float:right;">
                                    <select name="grupo_id" class="custom-select custom-select-sm" required>
                                        <option value="">Asignar a grupo...</option>
                                        <?php foreach ($grupos as $destino): ?>
                                            <option value="<?= $destino['id'] ?>"><?= $destino['numero'] ?> - <?= htmlspecialchars($destino['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-warning" type="submit"><i class="fas fa-arrow-right"></i></button>
                                    </div>
                                </div>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small">Sin grupos generados</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif (!$cerrada): ?>
        <div class="callout callout-info">
            <h5>Aún no hay grupos generados</h5>
            <p>Configure el número de grupos y el porcentaje del rubro grupal arriba y haga clic en "Generar grupos aleatorios".</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="copiarGruposModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-copy mr-1"></i> Copiar Grupos</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="callout callout-info mb-3">
                    <small><i class="fas fa-info-circle mr-1"></i> Se copiarán los grupos de la semana seleccionada a la <strong>Semana <?= (int) $semana['numero_semana'] ?></strong>. Si ya existen grupos, serán reemplazados.</small>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Copiar grupos desde:</label>
                    <select id="copiarOrigen" class="form-control" required>
                        <option value="">-- Seleccione semana origen --</option>
                        <?php foreach ($semanasConGrupos as $s): ?>
                            <option value="<?= (int) $s['id'] ?>">
                                S<?= (int) $s['numero_semana'] ?>: <?= htmlspecialchars($s['nombre']) ?> (<?= (int) $s['total_grupos'] ?> grupos)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info" id="btnConfirmarCopia">
                    <i class="fas fa-copy mr-1"></i> Copiar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
$inlineScripts = ['$(document).ready(function() {
    $("#btnRegenerar").on("click", function() {
        Swal.fire({
            title: "¿Re-generar los grupos?",
            text: "Los estudiantes se volverán a mezclar aleatoriamente. Las notas grupales ya registradas se conservan.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ffc107",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class=\'fas fa-random\'></i> Re-generar",
            cancelButtonText: "Cancelar"
        }).then(function(result) {
            if (result.isConfirmed) {
                $("#formRegenerar").submit();
            }
        });
    });

    $("#btnEliminarGrupos").on("click", function() {
        Swal.fire({
            title: "¿Eliminar los grupos?",
            text: "Se eliminarán los grupos, el rubro grupal y sus calificaciones de esta semana.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dc3545",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class=\'fas fa-trash\'></i> Eliminar",
            cancelButtonText: "Cancelar"
        }).then(function(result) {
            if (result.isConfirmed) {
                $("#formEliminarGrupos").submit();
            }
        });
    });
    var nombreOriginal = $("#nombre_rubro").val();
    $("#btnActualizarNombre").on("click", function() {
        var nombre = $("#nombre_rubro").val().trim();
        if (!nombre) { nombre = "Trabajo Grupal"; }
        var btn = $(this);
        btn.prop("disabled", true).html("<i class=\\"fas fa-spinner fa-spin\\"></i>");
        $.ajax({
            url: "' . BASE_URL . 'docente/actualizarNombreRubro",
            type: "POST",
            data: {
                _csrf_token: $("input[name=_csrf_token]").val(),
                semana_id: ' . (int) $semana['id'] . ',
                criterio_id: ' . (int) ($criterioGrupal['id'] ?? 0) . ',
                nombre_rubro: nombre
            },
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    nombreOriginal = nombre;
                    AdminModals.showToast("success", res.message);
                } else {
                    AdminModals.showToast("error", res.message);
                }
                btn.prop("disabled", false).html("<i class=\\"fas fa-check\\"></i>");
            },
            error: function() {
                AdminModals.showToast("error", "Error de conexión.");
                btn.prop("disabled", false).html("<i class=\\"fas fa-check\\"></i>");
            }
        });
    });
    $("#btnRestaurarNombre").on("click", function() {
        $("#nombre_rubro").val(nombreOriginal);
    });
    $("#btnCopiarGrupos").on("click", function() {
        $("#copiarOrigen").val("");
        $("#copiarGruposModal").modal("show");
    });
    $("#btnConfirmarCopia").on("click", function() {
        var origen = $("#copiarOrigen").val();
        if (!origen) { AdminModals.showToast("warning", "Seleccione la semana origen."); return; }
        var btn = $(this);
        btn.prop("disabled", true).html("<i class=\\"fas fa-spinner fa-spin\\"></i> Copiando...");
        $.ajax({
            url: "' . BASE_URL . 'docente/copiarGrupos",
            type: "POST",
            data: {
                semana_origen: origen,
                semana_destino: ' . (int) $semana['id'] . ',
                _csrf_token: $("meta[name=csrf-token]").attr("content")
            },
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    AdminModals.showToast("success", res.message);
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    AdminModals.showToast("error", res.message);
                    btn.prop("disabled", false).html("<i class=\\"fas fa-copy mr-1\\"></i> Copiar");
                }
            },
            error: function() {
                AdminModals.showToast("error", "Error de conexión.");
                btn.prop("disabled", false).html("<i class=\\"fas fa-copy mr-1\\"></i> Copiar");
            }
        });
    });
});'];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
