<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-sliders-h text-primary mr-1"></i> Apreciaci&oacute;n</h1>
</section>

<section class="content">
    <div class="callout callout-info">
        <h5><i class="fas fa-balance-scale mr-1"></i> Ajuste de Promedio</h5>
        <p class="mb-1">Ajuste el promedio semanal del estudiante entre <strong>-3.00 y +3.00 puntos</strong>.</p>
        <p class="mb-0"><small>El ajuste se aplica directamente al promedio ponderado de la semana. <strong>No se modifica la calificación original.</strong> Un ajuste por estudiante por semana. Esto <strong>afecta el promedio general, el maná y el nivel mago</strong> del estudiante. Los niveles <strong>Merlín no pueden recibir ajustes negativos</strong> pero s&iacute; pueden recibir bonificaciones.</small></p>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-book mr-1"></i> Curso</label>
                <select id="selectCurso" class="form-control" onchange="irApreciaciones()">
                    <option value="">-- Seleccione un curso --</option>
                    <?php if (!empty($cursos)): ?>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int) $curso_id === (int) $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nombre']) ?>
                                <?php if (!empty($c['periodo_nombre'])): ?>
                                    — <?= htmlspecialchars($c['periodo_nombre']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><i class="fas fa-calendar-week mr-1"></i> Semana</label>
                <?php if ($curso && !empty($semanas)): ?>
                <select id="selectSemana" class="form-control" onchange="irApreciaciones()">
                    <?php foreach ($semanas as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= (int) $semana_id === (int) $s['id'] ? 'selected' : '' ?>>
                            Semana <?= (int) $s['numero_semana'] ?>: <?= htmlspecialchars($s['nombre']) ?>
                            <?php if ((int) $s['cerrada'] === 1): ?>
                                (Cerrada)
                            <?php else: ?>
                                (Abierta)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <select id="selectSemana" class="form-control" disabled>
                    <option value="">-- Seleccione un curso primero --</option>
                </select>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($curso && !empty($semanas)): ?>
        <?php
            $semanaActual = null;
            foreach ($semanas as $s) {
                if ((int) $s['id'] === (int) $semana_id) {
                    $semanaActual = $s;
                    break;
                }
            }
            $semanaCerrada = $semanaActual && (int) $semanaActual['cerrada'] === 1;
        ?>

        <?php if ($semanaCerrada): ?>
            <div class="callout callout-warning mb-3">
                <small><i class="fas fa-lock mr-1"></i> Esta semana est&aacute; cerrada. No puede aplicar ajustes.</small>
            </div>
        <?php endif; ?>

        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-1"></i> Estudiantes — <?= htmlspecialchars($curso['nombre']) ?> — Semana <?= (int) ($semanaActual['numero_semana'] ?? 0) ?></h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" id="btnGuardarApreciaciones" <?= $semanaCerrada ? 'disabled title="Semana cerrada"' : '' ?>>
                        <i class="fas fa-save mr-1"></i> Guardar Ajustes
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-bordered table-striped table-hover table-sm mb-0 nodt">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th>Estudiante</th>
                            <th class="text-center" style="width:100px;">Prom. Semanal</th>
                            <th class="text-center" style="width:120px;">Ajuste</th>
                            <th>Motivo</th>
                            <th class="text-center" style="width:110px;">Prom. Final</th>
                            <th class="text-center" style="width:80px;">Acci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 0; foreach ($estudiantes as $est): ?>
                            <?php
                                $estId = (int) $est['id'];
                                $promedio = $promediosPorEstudiante[$estId] ?? 0;
                                $apreciacionActual = $apreciacionesPorEstudiante[$estId] ?? null;
                                $puntosActuales = $apreciacionActual ? (float) $apreciacionActual['puntos'] : 0;
                                $motivoActual = $apreciacionActual ? ($apreciacionActual['motivo'] ?? '') : '';
                                $promedioFinal = max(0, $promedio + $puntosActuales);
                                $nivelEst = $nivelesPorEstudiante[$estId] ?? null;
                                $esMerlin = $nivelEst && (int) ($nivelEst['rango'] ?? 1) >= 5;
                            ?>
                            <tr>
                                <td class="text-center"><?= ++$idx ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres']) ?></strong>
                                    <?php if ($esMerlin): ?>
                                        <br><span class="badge badge-info" style="font-size:.7rem;"><i class="fas fa-hat-wizard mr-1"></i>Merl&iacute;n</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php $promColor = $promedio < 11 ? 'danger' : ($promedio < 14 ? 'warning' : 'success'); ?>
                                    <span class="badge badge-<?= $promColor ?>"><?= number_format($promedio, 2) ?></span>
                                </td>
                                <td class="text-center">
                                    <select class="form-control form-control-sm text-center input-apreciacion"
                                            data-estudiante="<?= $estId ?>"
                                            data-promedio="<?= number_format($promedio, 2) ?>"
                                            style="max-width:110px;margin:0 auto;"
                                            <?= ($semanaCerrada || $esMerlin) ? 'disabled' : '' ?>>
                                        <option value="0" <?= abs($puntosActuales) < 0.01 ? 'selected' : '' ?>>Sin ajuste</option>
                                        <optgroup label="Bonificaci&oacute;n">
                                            <option value="3" <?= abs($puntosActuales - 3) < 0.01 ? 'selected' : '' ?>>+3.00</option>
                                            <option value="2.5" <?= abs($puntosActuales - 2.5) < 0.01 ? 'selected' : '' ?>>+2.50</option>
                                            <option value="2" <?= abs($puntosActuales - 2) < 0.01 ? 'selected' : '' ?>>+2.00</option>
                                            <option value="1.5" <?= abs($puntosActuales - 1.5) < 0.01 ? 'selected' : '' ?>>+1.50</option>
                                            <option value="1" <?= abs($puntosActuales - 1) < 0.01 ? 'selected' : '' ?>>+1.00</option>
                                            <option value="0.5" <?= abs($puntosActuales - 0.5) < 0.01 ? 'selected' : '' ?>>+0.50</option>
                                        </optgroup>
                                        <optgroup label="Ajuste negativo">
                                            <option value="-0.5" <?= abs($puntosActuales - (-0.5)) < 0.01 ? 'selected' : '' ?>>-0.50</option>
                                            <option value="-1" <?= abs($puntosActuales - (-1)) < 0.01 ? 'selected' : '' ?>>-1.00</option>
                                            <option value="-1.5" <?= abs($puntosActuales - (-1.5)) < 0.01 ? 'selected' : '' ?>>-1.50</option>
                                            <option value="-2" <?= abs($puntosActuales - (-2)) < 0.01 ? 'selected' : '' ?>>-2.00</option>
                                            <option value="-2.5" <?= abs($puntosActuales - (-2.5)) < 0.01 ? 'selected' : '' ?>>-2.50</option>
                                            <option value="-3" <?= abs($puntosActuales - (-3)) < 0.01 ? 'selected' : '' ?>>-3.00</option>
                                        </optgroup>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm input-motivo-apreciacion"
                                           data-estudiante="<?= $estId ?>"
                                           value="<?= htmlspecialchars($motivoActual) ?>"
                                           placeholder="Motivo del ajuste..."
                                           style="font-size:.85rem;"
                                           <?= ($semanaCerrada || $esMerlin) ? 'disabled' : '' ?>>
                                </td>
                                <td class="text-center">
                                    <span class="badge prom-final badge-<?php echo $promedioFinal < 11 ? 'danger' : ($promedioFinal < 14 ? 'warning' : 'success'); ?>" data-estudiante="<?= $estId ?>">
                                        <?= number_format($promedioFinal, 2) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($apreciacionActual): ?>
                                        <button type="button" class="btn btn-link btn-sm p-0 ver-historial-apreciacion" data-estudiante="<?= $estId ?>" data-nombre="<?= htmlspecialchars($est['apellidos'] . ', ' . $est['nombres'], ENT_QUOTES) ?>" style="font-size:.75rem;">
                                            <i class="fas fa-history"></i> Historial
                                        </button>
                                    <?php else: ?>
                                        <small class="text-muted">&mdash;</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$semanaCerrada): ?>
            <div class="card-footer text-right">
                <button type="button" class="btn btn-primary" id="btnGuardarApreciacionesFooter">
                    <i class="fas fa-save mr-1"></i> Guardar Ajustes
                </button>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif ($curso && empty($semanas)): ?>
        <div class="callout callout-warning">
            <h5>Sin semanas</h5>
            <p>No hay semanas creadas para este curso. Cree semanas primero.</p>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="historialApreciacionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="historialApreciacionModalLabel"><i class="fas fa-history mr-1"></i> Historial de Ajustes</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="historialApreciacionModalBody">
                <div class="text-center py-3"><i class="fas fa-spinner fa-spin fa-2x text-danger"></i></div>
            </div>
        </div>
    </div>
</div>

<script>
function irApreciaciones() {
    var cid = document.getElementById("selectCurso").value;
    var sid = document.getElementById("selectSemana").value || "";
    var url = "<?= BASE_URL ?>docente/apreciaciones";
    var params = [];
    if (cid) params.push("curso_id=" + cid);
    if (sid) params.push("semana_id=" + sid);
    if (params.length) url += "?" + params.join("&");
    location.href = url;
}
</script>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = $extraScripts ?? [];
$extraScripts[] = BASE_URL . 'js/admin_modals.js';

$cursoIdJson = (int) $curso_id;
$semanaIdJson = (int) $semana_id;
$inlineScripts = $inlineScripts ?? [];
$inlineScripts[] = '$(document).ready(function() {

    // ═══ SAVE ═══
    function guardarApreciaciones() {
        var datos = {};
        var hayDatos = false;

        $(".input-apreciacion").each(function() {
            var estId = $(this).data("estudiante");
            var puntos = parseFloat($(this).val()) || 0;
            var motivo = $(\'input.input-motivo-apreciacion[data-estudiante="\' + estId + \'"]\').val() || "";
            if (puntos !== 0) {
                datos[estId] = { puntos: puntos, motivo: motivo };
                hayDatos = true;
            }
        });

        if (!hayDatos) {
            AdminModals.showToast("warning", "No hay ajustes para guardar.");
            return;
        }

        var btn = $(this);
        btn.prop("disabled", true).html("<i class=\\"fas fa-spinner fa-spin mr-1\\"></i> Guardando...");

        $.ajax({
            url: "' . BASE_URL . 'docente/apreciarEstudiante",
            type: "POST",
            data: {
                curso_id: ' . $cursoIdJson . ',
                semana_id: ' . $semanaIdJson . ',
                apreciaciones: datos,
                _csrf_token: $("meta[name=csrf-token]").attr("content")
            },
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    AdminModals.showToast("success", res.message);
                    setTimeout(function() { location.reload(); }, 1200);
                } else {
                    AdminModals.showToast("error", res.message);
                    btn.prop("disabled", false).html("<i class=\\"fas fa-save mr-1\\"></i> Guardar Ajustes");
                }
            },
            error: function() {
                AdminModals.showToast("error", "Error de conexion.");
                btn.prop("disabled", false).html("<i class=\\"fas fa-save mr-1\\"></i> Guardar Ajustes");
            }
        });
    }

    $("#btnGuardarApreciaciones, #btnGuardarApreciacionesFooter").on("click", guardarApreciaciones);

    // ═══ REAL-TIME: update promedio final when ajuste changes ═══
    $(document).on("change", ".input-apreciacion", function() {
        var estId = $(this).data("estudiante");
        var promedio = parseFloat($(this).data("promedio")) || 0;
        var ajuste = parseFloat($(this).val()) || 0;
        var nuevo = Math.max(0, promedio + ajuste);

        var badge = $(\'.prom-final[data-estudiante="\' + estId + \'"]\');
        badge.text(nuevo.toFixed(2));
        badge.removeClass("badge-success badge-warning badge-danger");
        if (nuevo < 11) badge.addClass("badge-danger");
        else if (nuevo < 14) badge.addClass("badge-warning");
        else badge.addClass("badge-success");

        if (ajuste !== 0) {
            $(this).addClass("apreciacion-active");
        } else {
            $(this).removeClass("apreciacion-active");
        }
    });
    $(".input-apreciacion").trigger("change");

    // ═══ VIEW HISTORY ═══
    $(".ver-historial-apreciacion").on("click", function() {
        var estId = $(this).data("estudiante");
        var nombre = $(this).data("nombre");
        $("#historialApreciacionModalLabel").html("<i class=\\"fas fa-history mr-1\\"></i> Historial Ajustes - " + nombre);
        $("#historialApreciacionModalBody").html("<div class=\\"text-center py-3\\"><i class=\\"fas fa-spinner fa-spin fa-2x text-info\\"></i></div>");
        $("#historialApreciacionModal").modal("show");

        $.ajax({
            url: "' . BASE_URL . 'docente/historialApreciaciones",
            type: "GET",
            data: { curso_id: ' . $cursoIdJson . ', estudiante_id: estId },
            dataType: "json",
            success: function(res) {
                if (res.success && res.historial.length > 0) {
                    var html = "<table class=\\"table table-bordered table-sm\\"><thead><tr>";
                    html += "<th>Semana</th><th>Ajuste</th><th>Motivo</th><th>Fecha</th><th style=\\"width:50px;\\"></th>";
                    html += "</tr></thead><tbody>";
                    for (var i = 0; i < res.historial.length; i++) {
                        var h = res.historial[i];
                        var fecha = new Date(h.created_at);
                        var fechaStr = fecha.toLocaleDateString("es-PE") + " " + fecha.toLocaleTimeString("es-PE", {hour:"2-digit", minute:"2-digit"});
                        var ptsColor = h.puntos < 0 ? "#dc3545" : "#28a745";
                        html += "<tr>";
                        html += "<td><span class=\\"badge badge-primary\\">Sem " + h.numero_semana + "</span> " + (h.semana_nombre || "") + "</td>";
                        html += "<td class=\\"text-center\\" style=\\"color:" + ptsColor + ";font-weight:700;\\"><strong>" + (h.puntos > 0 ? "+" : "") + h.puntos + " pts</strong></td>";
                        html += "<td>" + (h.motivo || "<em class=\\"text-muted\\">Sin motivo</em>") + "</td>";
                        html += "<td><small>" + fechaStr + "</small></td>";
                        html += "<td class=\\"text-center\\"><button type=\\"button\\" class=\\"btn btn-danger btn-sm btn-eliminar-apreciacion\\" data-apreciacion-id=\"" + h.id + "\" title=\\"Eliminar ajuste\\"><i class=\\"fas fa-trash\\"></i></button></td>";
                        html += "</tr>";
                    }
                    html += "</tbody></table>";
                    $("#historialApreciacionModalBody").html(html);
                } else {
                    $("#historialApreciacionModalBody").html("<div class=\\"text-center py-3 text-muted\\"><i class=\\"fas fa-inbox fa-2x mb-2 d-block\\"></i>Sin registros de ajustes.</div>");
                }
            },
            error: function() {
                $("#historialApreciacionModalBody").html("<div class=\\"text-center py-3 text-danger\\"><i class=\\"fas fa-exclamation-triangle fa-2x mb-2 d-block\\"></i>Error al cargar historial.</div>");
            }
        });
    });

    // ═══ DELETE (with password) ═══
    $(document).on("click", ".btn-eliminar-apreciacion", function() {
        var btn = $(this);
        var apreciacionId = btn.data("apreciacion-id");

        $("#historialApreciacionModal").modal("hide");

        setTimeout(function() {
            Swal.fire({
            title: "Eliminar ajuste?",
            html: "<p>Ingrese su contrase&ntilde;a para confirmar.<br><small>El promedio del estudiante ser&aacute; restaurado.</small></p><input id=\\"swal-password\\" type=\\"password\\" class=\\"swal2-input\\" placeholder=\\"Contrase&ntilde;a\\" autocomplete=\\"off\\">",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#28a745",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "<i class=\\"fas fa-check\\"></i> Restaurar promedio",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            preConfirm: function() {
                var pw = document.getElementById("swal-password").value;
                if (!pw) {
                    Swal.showValidationMessage("Debe ingresar su contrase&ntilde;a.");
                    return false;
                }
                return pw;
            }
        }).then(function(result) {
            if (result.isConfirmed) {
                btn.prop("disabled", true).html("<i class=\\"fas fa-spinner fa-spin\\"></i>");
                $.ajax({
                    url: "' . BASE_URL . 'docente/eliminarApreciacion",
                    type: "POST",
                    data: {
                        apreciacion_id: apreciacionId,
                        password: result.value,
                        _csrf_token: $("meta[name=csrf-token]").attr("content")
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire("Restaurado", res.message, "success");
                            setTimeout(function() { location.reload(); }, 1000);
                        } else {
                            Swal.fire("Error", res.message, "error");
                            btn.prop("disabled", false).html("<i class=\\"fas fa-trash\\"></i>");
                        }
                    },
                    error: function() {
                        Swal.fire("Error", "Error de conexion.", "error");
                        btn.prop("disabled", false).html("<i class=\\"fas fa-trash\\"></i>");
                    }
                });
            }
        });
        }, 300);
    });
});
';
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
