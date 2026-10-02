<?php
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/participacion.css?v=' . filemtime(ROOT_PATH . 'public/css/participacion.css') . '">';
require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Solicitudes de Examen</h1>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-hat-wizard mr-1 text-warning"></i> Oportunidades de examen por maná de compañeros
            </h3>
        </div>
        <div class="card-body table-responsive">
            <?php if (!empty($solicitudes)): ?>
                <table class="table table-bordered table-striped table-hover nodt">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Semana</th>
                            <th>Maná</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $s): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($s['apellidos'] . ', ' . $s['nombres']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($s['username']) ?></small>
                                    <br><a href="#" onclick="verDonantes('<?= (int)$s['estudiante_id'] ?>:<?= (int)$s['curso_id'] ?>:<?= (int)($s['semana_id'] ?? 0) ?>'); return false;" class="small"><i class="fas fa-users mr-1"></i>Ver donantes</a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($s['curso_nombre']) ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($s['curso_codigo']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($s['semana_id'])): ?>
                                        <span class="badge badge-secondary">Semana <?= (int) $s['numero_semana'] ?></span>
                                        <small class="d-block text-muted"><?= htmlspecialchars($s['semana_nombre']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="fas fa-bolt text-warning mr-1"></i>
                                    <?= number_format((float) $s['mana_obtenida'], 0) ?>
                                    / <?= number_format((float) $s['mana_objetivo'], 0) ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="registrarNotaOportunidad(<?= $s['id'] ?>, <?= (int) $s['curso_id'] ?>, <?= (int) ($s['semana_id'] ?? 0) ?>)" title="Registrar la nueva nota (funciona aunque la semana esté cerrada)">
                                        <i class="fas fa-pen mr-1"></i> Registrar nota
                                    </button>
                                    <form method="post" action="<?= BASE_URL ?>docente/atenderSolicitud" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="solicitud_id" value="<?= $s['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Marcar como atendida">
                                            <i class="fas fa-check mr-1"></i> Atendida
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="rechazarSolicitud(<?= $s['id'] ?>)" title="Rechazar con motivo">
                                        <i class="fas fa-times mr-1"></i> Rechazar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="callout callout-success">
                    <h5>Sin solicitudes pendientes</h5>
                    <p>No hay oportunidades de examen pendientes por maná de compañeros.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-hand-holding-heart mr-1 text-primary"></i> Maná de compañerismo recibidas
            </h3>
        </div>
        <div class="card-body table-responsive">
            <?php if (!empty($manaRecibido)): ?>
                <p class="text-muted small">
                    <i class="fas fa-info-circle mr-1"></i>Progreso de maná por estudiante, curso y semana. Al llegar a la meta se genera la oportunidad de examen en la tabla superior.
                </p>
                <table class="table table-bordered table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Semana</th>
                            <th>Maná</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manaRecibido as $cr): ?>
                            <?php
                                $crAcumulado = (float) $cr['acumulado'];
                                $crObjetivo = (float) $cr['objetivo'];
                                $crPct = $crObjetivo > 0 ? min(100, (int) round($crAcumulado / $crObjetivo * 100)) : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($cr['apellidos'] . ', ' . $cr['nombres']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($cr['username']) ?></small>
                                    <a href="#" onclick="verDonantes('<?= (int)$cr['receptor_id'] ?>:<?= (int)$cr['curso_id'] ?>:<?= (int)($cr['semana_id'] ?? 0) ?>'); return false;" class="small"><i class="fas fa-users mr-1"></i>Ver donantes</a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($cr['curso_nombre']) ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($cr['curso_codigo']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($cr['semana_id'])): ?>
                                        <span class="badge badge-secondary">Semana <?= (int) $cr['numero_semana'] ?></span>
                                        <small class="d-block text-muted"><?= htmlspecialchars($cr['semana_nombre']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">&mdash;</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="small">
                                            <i class="fas fa-bolt text-warning mr-1"></i>
                                            <?= number_format($crAcumulado, 1) ?> / <?= number_format($crObjetivo, 0) ?>
                                        </span>
                                        <span class="badge badge-secondary"><?= $crPct ?>%</span>
                                    </div>
                                    <div class="progress" style="height:7px;">
                                        <div class="progress-bar <?= $crPct >= 100 ? 'bg-success' : 'bg-warning' ?>" style="width:<?= $crPct ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($cr['tiene_pendiente'])): ?>
                                        <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Oportunidad generada</span>
                                    <?php elseif ($crPct >= 100): ?>
                                        <span class="badge badge-primary">Meta alcanzada</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">En progreso</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="callout callout-info">
                    <h5>Sin maná recibido</h5>
                    <p>Aún no hay maná de compañerismo registrado en tus cursos.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="modal fade" id="modalDonantes" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title"><i class="fas fa-users mr-1"></i> Donantes de maná</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-striped mb-0 nodt">
                    <thead>
                        <tr>
                            <th>Compañero</th>
                            <th class="text-center">Maná</th>
                            <th class="text-center">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="modalDonantesBody">
                        <tr><td colspan="3" class="text-center text-muted py-3">—</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$inlineScripts = ['
var semanasPorCurso = ' . json_encode($semanasPorCurso, JSON_UNESCAPED_UNICODE) . ';
var donantesMap = ' . json_encode($donantesMap, JSON_UNESCAPED_UNICODE) . ';

function verDonantes(clave) {
    var lista = donantesMap[clave] || [];
    var tbody = $("#modalDonantesBody");
    tbody.empty();
    if (!lista.length) {
        tbody.append("<tr><td colspan=\\"3\\" class=\\"text-center text-muted py-3\\">Sin donantes registrados todavía.</td></tr>");
    } else {
        for (var i = 0; i < lista.length; i++) {
            var d = lista[i];
            var f = new Date(d.created_at.replace(" ", "T"));
            var fs = f.toLocaleDateString("es-PE");
            tbody.append(
                "<tr>" +
                "<td>" + d.apellidos + ", " + d.nombres + "<br><small class=\\"text-muted\\">@" + d.username + "</small></td>" +
                "<td class=\\"text-center\\"><i class=\\"fas fa-bolt text-warning mr-1\\"></i>" + parseFloat(d.cantidad).toFixed(1) + "</td>" +
                "<td class=\\"text-center\\">" + fs + "</td>" +
                "</tr>"
            );
        }
    }
    $("#modalDonantes").modal("show");
}

function rechazarSolicitud(solicitudId) {
    Swal.fire({
        title: "Rechazar solicitud",
        input: "textarea",
        inputPlaceholder: "Motivo del rechazo (obligatorio)...",
        showCancelButton: true,
        confirmButtonText: "Rechazar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#dc3545",
        preConfirm: function(motivo) {
            motivo = (motivo || "").trim();
            if (!motivo) {
                Swal.showValidationMessage("Debe indicar el motivo del rechazo.");
                return false;
            }
            return $.ajax({
                url: "' . BASE_URL . 'docente/rechazarSolicitud",
                type: "POST",
                data: {
                    solicitud_id: solicitudId,
                    motivo: motivo,
                    _csrf_token: $("meta[name=csrf-token]").attr("content")
                },
                dataType: "json"
            }).then(function(res) {
                if (!res.success) {
                    Swal.showValidationMessage(res.message || "Error al rechazar.");
                    return false;
                }
                return res;
            }, function() {
                Swal.showValidationMessage("Error de conexión.");
                return false;
            });
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({ icon: "success", title: "Solicitud rechazada", timer: 1200, showConfirmButton: false });
            setTimeout(function() { location.reload(); }, 1200);
        }
    });
}

function registrarNotaOportunidad(solicitudId, cursoId, semanaId) {
    var semanas = semanasPorCurso[cursoId] || [];
    if (semanas.length === 0) {
        Swal.fire({
            icon: "warning",
            title: "Sin semanas",
            text: "Este curso aún no tiene semanas registradas."
        });
        return;
    }

    var optionsHtml = "";
    for (var i = 0; i < semanas.length; i++) {
        var w = semanas[i];
        var estado = w.cerrada ? "Cerrada" : "Abierta";
        var selected = w.id === semanaId ? " selected" : "";
        optionsHtml += "<option value=\"" + w.id + "\"" + selected + ">Semana " + w.numero_semana + " - " + w.nombre + " (" + estado + ")</option>";
    }

    Swal.fire({
        title: "Registrar nota de oportunidad",
        html:
            "<div class=\"text-left\">" +
                "<label class=\"d-block font-weight-bold mb-1\">Semana del examen</label>" +
                "<select id=\"swal-semana\" class=\"form-control\">" + optionsHtml + "</select>" +
                "<label class=\"d-block font-weight-bold mb-1 mt-3\">Nueva nota (0 - 20)</label>" +
                "<input id=\"swal-nota\" type=\"number\" class=\"form-control\" min=\"0\" max=\"20\" step=\"0.01\" placeholder=\"Ej: 15.5\">" +
            "</div>",
        showCancelButton: true,
        confirmButtonText: "Registrar nota",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#17a2b8",
        preConfirm: function() {
            var semanaId = parseInt($("#swal-semana").val(), 10);
            var nota = parseFloat($("#swal-nota").val());

            if (!semanaId) {
                Swal.showValidationMessage("Seleccione la semana del examen.");
                return false;
            }
            if (isNaN(nota) || nota < 0 || nota > 20) {
                Swal.showValidationMessage("Ingrese una nota válida entre 0 y 20.");
                return false;
            }

            return $.ajax({
                url: "' . BASE_URL . 'docente/registrarNotaOportunidad",
                type: "POST",
                data: {
                    solicitud_id: solicitudId,
                    semana_id: semanaId,
                    nota: nota,
                    _csrf_token: $("meta[name=csrf-token]").attr("content")
                },
                dataType: "json"
            }).then(function(res) {
                if (!res.success) {
                    Swal.showValidationMessage(res.message || "Error al registrar la nota.");
                    return false;
                }
                return res;
            }, function() {
                Swal.showValidationMessage("Error de conexión al registrar la nota.");
                return false;
            });
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            Swal.fire({
                icon: "success",
                title: "Nota registrada",
                text: result.value.message
            });
            setTimeout(function() { location.reload(); }, 1500);
        }
    });
}
'];
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
