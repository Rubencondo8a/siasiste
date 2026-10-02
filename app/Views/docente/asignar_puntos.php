<?php
$pageTitle = 'Asignar Puntos — ' . htmlspecialchars($curso['nombre'] ?? '');
$extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">' . ($extraHead ?? '');
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../partials/messages.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/participacion.css?v=<?= filemtime(ROOT_PATH . 'public/css/participacion.css') ?>">

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-gem text-primary mr-1"></i> Asignar Puntos</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $curso_id ?>" class="btn btn-secondary mb-3">
            <i class="fas fa-arrow-left"></i> Volver a Semanas
        </a>

        <div class="callout callout-success mb-3">
            <small><i class="fas fa-calendar-week mr-1"></i> Semana: <strong><?= htmlspecialchars($semana['nombre'] ?? '') ?></strong>
                (S<?= (int) ($semana['numero_semana'] ?? 0) ?>) — Curso: <?= htmlspecialchars($curso['nombre'] ?? '') ?></small>
            <p class="mb-0 mt-1"><small>Cada participación se registra por separado con su motivo. Los puntos se acumulan entre participaciones y semanas.</small></p>
        </div>

        <div class="card">
            <div class="card-body table-responsive p-0">
                <table id="tablaEstudiantesPuntos" class="table table-bordered table-striped table-hover table-sm mb-0 nodt">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th>Estudiante</th>
                            <th class="text-center" style="width:100px;">Asistencia</th>
                            <th class="text-center" style="width:200px;">Acumulado</th>
                            <th style="width:140px;">Participaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $i => $est): ?>
                            <?php
                                $estId = (int) $est['id'];
                                $resumen = $resumenPorEstudiante[$estId] ?? ['total_puntos' => 0, 'cristales' => 0, 'sobrante' => 0];
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars(formatApellidos($est['apellidos'] ?? '') . ' ' . formatNombres($est['nombres'] ?? '')) ?></td>
                                <td class="text-center" id="asist-<?= $estId ?>">
                                    <span class="badge badge-secondary" title="Cargando...">—</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light">⭐ <?= (int) $resumen['total_puntos'] ?> pts</span>
                                    <span class="badge badge-info">💎 <?= (int) $resumen['cristales'] ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-abrir-modal"
                                            data-estudiante="<?= $estId ?>"
                                            data-nombre="<?= htmlspecialchars(trim(($est['nombres'] ?? '') . ' ' . ($est['apellidos'] ?? ''))) ?>"
                                            disabled>
                                        <i class="fas fa-plus mr-1"></i> Registrar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($estudiantes)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Sin estudiantes en este curso.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="participacionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="participacionModalLabel"><i class="fas fa-gem mr-1"></i> Participaciones</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="card card-outline card-success mb-3">
                    <div class="card-header py-2">
                        <h6 class="card-title mb-0"><i class="fas fa-plus-circle mr-1"></i> Nueva Participación</h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-12 col-sm-3 form-group mb-1">
                                <label class="small font-weight-bold">Puntos</label>
                                <input type="number" id="modalPuntos" class="form-control input-puntos text-center" value="0" min="-100" max="100">
                            </div>
                            <div class="col-12 col-sm-9 form-group mb-1">
                                <label class="small font-weight-bold">Motivo</label>
                                <select id="modalMotivo" class="form-control"><option value=""></option></select>
                                </div>
                        </div>
                        <button type="button" class="btn btn-success btn-block btn-sm" id="btnRegistrarParticipacion">
                            <i class="fas fa-save mr-1"></i> Registrar
                        </button>
                    </div>
                </div>

                <div id="listaParticipaciones" class="mb-2"></div>

                <div class="text-right mt-2">
                    <small class="text-muted" id="modalResumen"></small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = $extraScripts ?? [];
$extraScripts[] = BASE_URL . 'js/admin_modals.js';
$extraScripts[] = 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js';
$inlineScripts = $inlineScripts ?? [];
$inlineScripts[] = '
var cursoId = ' . (int) $curso_id . ';
var semanaId = ' . (int) $semana_id . ';
var currentModalEstId = 0;
var modalDataTable = null;
var motivosCache = [];

var tablaHtml = "<div id=\"cargaSpinner\" class=\"text-center py-3\"><i class=\"fas fa-spinner fa-spin fa-2x text-primary\"></i></div>" +
    "<table id=\"tablaParticipaciones\" class=\"table table-sm table-bordered mb-0\" style=\"width:100%\"><thead class=\"thead-light\"><tr>" +
    "<th class=\"text-center\" style=\"width:70px;\">Puntos</th><th>Motivo</th><th style=\"width:110px;\">Fecha</th>" +
    "</tr></thead><tbody></tbody></table>";

function semanaEfectivaId() {
    return semanaId;
}

function calcularResumen(historial) {
    var total = 0;
    for (var i = 0; i < historial.length; i++) total += parseInt(historial[i].puntos) || 0;
    var cristales = Math.floor(total / 16);
    var sobrante = total % 16;
    var resumen = "\u2B50 " + total + " pts | \uD83D\uDC8E " + cristales + " cristales";
    if (sobrante > 0) resumen += " (faltan " + (16 - sobrante) + ")";
    return resumen;
}

function cargarMotivos() {
    return $.ajax({
        url: "' . BASE_URL . 'docente/motivosPuntos",
        type: "GET",
        dataType: "json"
    }).then(function(res) {
        if (!res.success) return;
        motivosCache = res.motivos;
        var catNombre = {};
        (res.categorias || []).forEach(function(c) { catNombre[c.id] = (c.icono ? c.icono + " " : "") + c.nombre; });
        var grupos = {}, sueltos = [];
        motivosCache.forEach(function(m) {
            if (m.categoria_id && catNombre[m.categoria_id]) {
                if (!grupos[catNombre[m.categoria_id]]) grupos[catNombre[m.categoria_id]] = [];
                grupos[catNombre[m.categoria_id]].push(m);
            } else {
                sueltos.push(m);
            }
        });
        function opt(m) {
            var desc = (m.descripcion || "").replace(/"/g, "&quot;");
            return "<option value=\"" + m.id + "\" title=\"" + desc + "\">" + m.nombre + "</option>";
        }
        var html = "<option value=\"\">Seleccione motivo</option>";
        sueltos.forEach(function(m) { html += opt(m); });
        Object.keys(grupos).forEach(function(cat) {
            html += "<optgroup label=\"" + cat + "\">";
            grupos[cat].forEach(function(m) { html += opt(m); });
            html += "</optgroup>";
        });
        $("#modalMotivo").html(html);
        var $sel = $("#modalMotivo");
        if ($sel.hasClass("select2-hidden-accessible")) $sel.select2("destroy");
        $sel.html(html);
        $sel.select2({
            theme: "bootstrap4",
            width: "100%",
            dropdownParent: $("#participacionModal"),
            placeholder: "Buscar motivo...",
            language: {
                searching: function() { return "Buscando..."; },
                noResults: function() { return "Sin resultados"; }
            },
            templateResult: function(m) {
                if (!m.id) return m.text;
                var d = $(m.element).attr("title") || "";
                return $("<span><strong>" + m.text + "</strong>" + (d ? "<br><small class=\"text-muted\">" + d + "</small>" : "") + "</span>");
            },
            escapeMarkup: function(mk) { return mk; }
        });
    });
}


function abrirModal(estId, nombre) {
    currentModalEstId = estId;
    if (modalDataTable) { modalDataTable.destroy(); modalDataTable = null; }
    $("#participacionModalLabel").html("<i class=\"fas fa-gem mr-1\"></i> " + nombre);
    $("#modalPuntos").val(0).trigger("input");
    $("#listaParticipaciones").html(tablaHtml);
    $("#modalResumen").text("");
    $("#participacionModal").modal("show");
    cargarMotivos();

    $.ajax({
        url: "' . BASE_URL . 'docente/historialPuntos",
        type: "GET",
        data: { curso_id: cursoId, estudiante_id: estId },
        dataType: "json",
        success: function(res) {
            if (!res.success) {
                $("#cargaSpinner").hide();
                $("#listaParticipaciones").append("<div class=\"text-center text-muted py-3\">Sin datos.</div>");
                return;
            }
            var rows = [];
            for (var i = 0; i < res.historial.length; i++) {
                var p = res.historial[i];
                if (parseInt(p.semana_id) !== semanaId) continue;
                var fecha = new Date(p.created_at.replace(" ", "T"));
                var fechaStr = fecha.toLocaleDateString("es-PE") + " " + fecha.toLocaleTimeString("es-PE", { hour: "2-digit", minute: "2-digit" });
                var ptsClass = p.puntos < 0 ? "text-danger" : (p.puntos > 0 ? "text-success" : "");
                var ptsStr = (p.puntos > 0 ? "+" : "") + p.puntos;
                var motivo = p.motivo || "<em class=\"text-muted\">Sin motivo</em>";
                rows.push(["<strong class=\"" + ptsClass + "\">" + ptsStr + "</strong>", motivo, "<small class=\"text-muted\">" + fechaStr + "</small>"]);
            }
            $("#cargaSpinner").hide();
            modalDataTable = $("#tablaParticipaciones").DataTable({
                data: rows,
                columns: [{ title: "Puntos" }, { title: "Motivo" }, { title: "Fecha" }],
                language: {
                    search: "Buscar:",
                    lengthMenu: "Mostrar _MENU_ registros",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Sin participaciones esta semana",
                    zeroRecords: "Sin participaciones esta semana",
                    emptyTable: "Sin participaciones esta semana",
                    paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
                },
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                order: [[2, "desc"]],
                responsive: true
            });
            $("#modalResumen").text(calcularResumen(res.historial));
        },
        error: function() {
            $("#listaParticipaciones").html("<div class=\"text-center text-danger py-3\">Error al cargar.</div>");
        }
    });
}

$(document).on("click", ".btn-abrir-modal", function() {
    abrirModal($(this).data("estudiante"), $(this).data("nombre"));
});

$("#btnRegistrarParticipacion").on("click", function() {
    var estId = currentModalEstId;
    var puntos = parseInt($("#modalPuntos").val()) || 0;
    var motivoId = $("#modalMotivo").val();

    if (!estId) { AdminModals.showToast("warning", "Seleccione un estudiante."); return; }
    if (puntos === 0) { AdminModals.showToast("warning", "Ingrese puntos diferentes de 0."); return; }
    if (!motivoId) { AdminModals.showToast("warning", "Seleccione un motivo."); return; }

    var btn = $(this);
    btn.prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin\"></i> Registrando...");

    var datos = {};
    datos[estId] = { puntos: puntos, motivo_id: motivoId };

    $.ajax({
        url: "' . BASE_URL . 'docente/guardarPuntosDirecto",
        type: "POST",
        data: {
            curso_id: cursoId,
            semana_id: semanaEfectivaId(),
            puntos: datos,
            _csrf_token: $("meta[name=csrf-token]").attr("content")
        },
        dataType: "json",
        success: function(res) {
            if (res.success) {
                AdminModals.showToast("success", "Participación registrada.");
                setTimeout(function() { location.reload(); }, 1000);
            } else {
                AdminModals.showToast("error", res.message);
                btn.prop("disabled", false).html("<i class=\"fas fa-save mr-1\"></i> Registrar");
            }
        },
        error: function() {
            AdminModals.showToast("error", "Error de conexión.");
            btn.prop("disabled", false).html("<i class=\"fas fa-save mr-1\"></i> Registrar");
        }
    });
});

$(document).on("input", "#modalPuntos", function() {
    var val = parseInt($(this).val()) || 0;
    if (val < 0) {
        $(this).addClass("neg").removeClass("pos").css({ "border-color": "#dc3545" });
    } else if (val > 0) {
        $(this).removeClass("neg").addClass("pos").css({ "border-color": "#28a745" });
    } else {
        $(this).removeClass("neg pos").css({ "border-color": "" });
    }
});

var asistenciaHoy = {};

function aplicarAsistencia() {
    $(".btn-abrir-modal").each(function() {
        var estId = $(this).data("estudiante");
        var $badge = $("#asist-" + estId);
        var estado = asistenciaHoy[estId];
        if (estado === "P") {
            $badge.html("<span class=\"badge badge-success\" title=\"Presente\"><i class=\"fas fa-check\"></i> P</span>");
            $(this).prop("disabled", false).html("<i class=\"fas fa-plus mr-1\"></i> Registrar");
        } else if (estado === "T") {
            $badge.html("<span class=\"badge badge-warning\" title=\"Tardanza\"><i class=\"fas fa-clock\"></i> T</span>");
            $(this).prop("disabled", false).html("<i class=\"fas fa-plus mr-1\"></i> Registrar");
        } else if (estado === "J") {
            $badge.html("<span class=\"badge badge-info\" title=\"Justificado\"><i class=\"fas fa-file-medical\"></i> J</span>");
            $(this).prop("disabled", false).html("<i class=\"fas fa-plus mr-1\"></i> Registrar");
        } else if (estado === "A") {
            $badge.html("<span class=\"badge badge-danger\" title=\"Ausente\"><i class=\"fas fa-times\"></i> A</span>");
            $(this).prop("disabled", true).html("<i class=\"fas fa-ban mr-1\"></i> Sin asistencia");
        } else {
            $badge.html("<span class=\"badge badge-secondary\" title=\"Sin registro\"><i class=\"fas fa-minus\"></i> —</span>");
            $(this).prop("disabled", true).html("<i class=\"fas fa-ban mr-1\"></i> Sin registro");
        }
    });
}

function cargarAsistenciaHoy() {
    if (!cursoId) return;
    $.ajax({
        url: "' . BASE_URL . 'docente/asistenciaHoy",
        type: "GET",
        data: { curso_id: cursoId },
        dataType: "json",
        success: function(res) {
            if (!res.success) return;
            asistenciaHoy = res.asistencia || {};
            aplicarAsistencia();
        }
    });
}

$(document).ready(function() {
    if (window.$.fn.DataTable) {
        if ($.fn.DataTable.isDataTable("#tablaEstudiantesPuntos")) {
            $("#tablaEstudiantesPuntos").DataTable().destroy();
        }
        $("#tablaEstudiantesPuntos").DataTable({
            pageLength: -1,
            lengthMenu: [[-1, 20, 50], ["Todos", 20, 50]],
            language: {
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "No hay registros disponibles",
                infoFiltered: "(filtrado de _MAX_ registros totales)",
                zeroRecords: "No se encontraron resultados",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
            },
            order: [],
            responsive: true,
            initComplete: function() {
                $("#tablaEstudiantesPuntos_filter input").attr("placeholder", "Buscar estudiante...");
                $("#tablaEstudiantesPuntos_length select option").each(function() {
                    if ($(this).val() === "-1") $(this).text("Todos");
                });
            }
        });
    }
    $(document).on("draw.dt", aplicarAsistencia);
});

cargarAsistenciaHoy();
';
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
