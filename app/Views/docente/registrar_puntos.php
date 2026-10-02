<?php $extraHead = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css">'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php';?>
<link rel="stylesheet" href="<?= BASE_URL ?>css/participacion.css?v=<?= filemtime(ROOT_PATH . 'public/css/participacion.css') ?>">
<style>
#panelCola .table,
#panelCola .table td,
#panelCola .table th,
#panelCola .cola-tabla,
#panelCola .cola-tabla td,
#panelCola .cola-tabla th {
    background-color: #ffffff !important;
    color: #212529 !important;
    border-color: #dee2e6 !important;
}
#panelCola .cola-tabla thead th {
    background-color: #f8f9fa !important;
    color: #212529 !important;
}
#panelCola .cola-tabla .form-control,
#panelCola .cola-tabla select,
#panelCola .cola-tabla input {
    color: #212529 !important;
    background-color: #ffffff !important;
}
/* Input de puntos: ancho cómodo y sin flechas del type=number para que sea táctil */
#panelCola .cola-tabla .motivos-puntos {
    min-width: 86px;
    width: 96px;
    height: 34px;
    font-size: 1rem;
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: auto;
}
#panelCola .cola-tabla .motivos-puntos::-webkit-inner-spin-button,
#panelCola .cola-tabla .motivos-puntos::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
/* En móvil la celda de puntos y el input crecen para facilitar el registro */
@media (max-width: 575.98px) {
    #panelCola .cola-tabla .motivos-puntos {
        min-width: 100px;
        width: 120px;
        height: 40px;
        font-size: 1.1rem;
    }
}
</style>

<section class="content-header">
    <h1><i class="fas fa-gem text-primary mr-1"></i> Participaci&oacute;n</h1>
</section>

<section class="content">
    <div class="callout callout-info">
        <h5><i class="fas fa-info-circle mr-1"></i> Sistema de Puntos y Cristales</h5>
        <p class="mb-1">Registre participaciones de cada estudiante (-100 a 100 puntos c/u). <strong>16 puntos = 1 💎 cristal</strong> automático.</p>
        <p class="mb-0"><small>Cada participación se registra por separado con su motivo. Use <strong>valores negativos</strong> para descontar puntos.</small></p>
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book mr-1"></i> Unidad Didáctica y Semana de Registro</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6 form-group mb-md-0">
                    <label class="small font-weight-bold text-muted">Unidad didáctica (curso)</label>
                    <select id="selectCurso" class="form-control" onchange="if(this.value){location.href='<?= BASE_URL ?>docente/registrarPuntos?curso_id='+this.value;}else{location.href='<?= BASE_URL ?>docente/registrarPuntos';}">
                        <option value="">-- Seleccione un curso --</option>
                        <?php if (!empty($cursos)): ?>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= isset($curso_id) && (int) $curso_id === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['nombre']) ?>
                                    <?php if (!empty($c['periodo_nombre'])): ?>
                                        — <?= htmlspecialchars($c['periodo_nombre']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <?php if (!empty($curso) && !empty($semanasAbiertasCurso)): ?>
                <div class="col-12 col-md-6 form-group mb-0">
                    <label class="small font-weight-bold text-muted">Registrar participaciones en</label>
                    <select id="selSemanaRegistro" class="form-control"
                            onchange="if(this.value){location.href='<?= BASE_URL ?>docente/registrarPuntos?curso_id=<?= (int) ($curso_id ?? 0) ?>&semana_id='+this.value;}else{location.href='<?= BASE_URL ?>docente/registrarPuntos?curso_id=<?= (int) ($curso_id ?? 0) ?>';}">
                        <option value="">Semana activa (recomendado)</option>
                        <?php foreach ($semanasAbiertasCurso as $s): ?>
                            <?php $esSeleccion = !empty($semanaSelect) && (int) $s['id'] === (int) $semanaSelect; ?>
                            <option value="<?= (int) $s['id'] ?>" <?= $esSeleccion ? 'selected' : '' ?>>
                                S<?= (int) $s['numero_semana'] ?>: <?= htmlspecialchars($s['nombre']) ?><?= $esSeleccion ? ' ✓ SELECCIONADA' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($semanaAbierta)): ?>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-calendar-check mr-1"></i> Activa: Semana <?= (int) $semanaAbierta['numero_semana'] ?> — si no seleccionas, se usará esta.
                        </small>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($curso)): ?>
            <hr>
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <h5 class="mb-1"><i class="fas fa-comment-dots text-warning mr-1"></i> Participación en vivo</h5>
                    <p class="text-muted small mb-2">
                        Activa la participación para que los estudiantes presionen "Quiero participar" (se anotan en orden).
                        Luego marca qui&eacute;nes participaron y asigna motivo/puntos. Al cerrar, los no participaron se eliminan.
                    </p>
                    <div id="estadoParticipacion" class="d-none">
                        <span class="badge badge-success" id="estadoBadge">
                            <i class="fas fa-circle text-success mr-1"></i> Participación activa
                        </span>
                        <span class="text-muted small ml-2" id="estadoConteo"></span>
                    </div>
                </div>
                <div class="col-12 col-md-4 text-md-right mt-2 mt-md-0">
                    <button type="button" class="btn btn-success" id="btnActivarParticipacion">
                        <i class="fas fa-play mr-1"></i> Activar participación
                    </button>
                    <button type="button" class="btn btn-danger d-none" id="btnCerrarParticipacion" data-pid="0">
                        <i class="fas fa-stop mr-1"></i> Cerrar participación
                    </button>
                </div>
            </div>
            <div id="panelCola" class="mt-3 d-none"></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($curso) && !empty($estudiantes)): ?>
        <?php if (!empty($semanaAbierta)): ?>
            <div class="callout callout-success mb-3">
                <small><i class="fas fa-calendar-check mr-1"></i> Las participaciones se guardarán en la semana seleccionada arriba; si no eligió ninguna, se usará la <strong>semana activa (Semana <?= (int) $semanaAbierta['numero_semana'] ?></strong>: <?= htmlspecialchars($semanaAbierta['nombre']) ?>).</small>
            </div>
        <?php else: ?>
            <div class="callout callout-warning mb-3">
                <small><i class="fas fa-exclamation-triangle mr-1"></i> No hay semanas abiertas. No podrá registrar participaciones hasta que abra una semana.</small>
            </div>
        <?php endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-1"></i> <?= htmlspecialchars($curso['nombre']) ?></h3>
            </div>
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
                        <tr><td colspan="4" class="text-center text-muted py-4">Sin estudiantes en este curso.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
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
var cursoId = ' . (int) ($curso_id ?? 0) . ';
var semanaId = ' . (int) ($semanaSelect ?? 0) . ';
var currentModalEstId = 0;
var modalDataTable = null;
var motivosCache = [];

var tablaHtml = "<div id=\"cargaSpinner\" class=\"text-center py-3\"><i class=\"fas fa-spinner fa-spin fa-2x text-primary\"></i></div>" +
    "<table id=\"tablaParticipaciones\" class=\"table table-sm table-bordered mb-0\" style=\"width:100%\"><thead class=\"thead-light\"><tr>" +
    "<th class=\"text-center\" style=\"width:70px;\">Puntos</th><th>Motivo</th><th style=\"width:110px;\">Fecha</th>" +
    "</tr></thead><tbody></tbody></table>";

function semanaEfectivaId() {
    var sel = document.getElementById("selSemanaRegistro");
    if (sel && sel.value) return parseInt(sel.value, 10);
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
            var semFiltro = semanaEfectivaId();
            var rows = [];
            for (var i = 0; i < res.historial.length; i++) {
                var p = res.historial[i];
                if (parseInt(p.semana_id) !== semFiltro) continue;
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

$(document).on("click", ".btn-abrir-modal", function(e) {
    e.stopPropagation();
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

// Cargar asistencia del dia y habilitar/deshabilitar botones
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
        data: { curso_id: cursoId, semana_id: semanaEfectivaId() },
        dataType: "json",
        cache: false,
        success: function(res) {
            console.log("asistenciaHoy res", res);
            if (!res || !res.success) {
                console.warn("asistenciaHoy sin success", res);
                return;
            }
            asistenciaHoy = res.asistencia || {};
            console.log("asistenciaHoy map", asistenciaHoy);
            aplicarAsistencia();
        },
        error: function(xhr, status, err) {
            console.error("asistenciaHoy error", status, err, xhr.responseText);
        }
    });
}

$(document).ready(function() {
    // DataTable con pageLength -1 => muestra TODOS los estudiantes de una vez (sin paginacion).
    // El buscador sigue funcionando. Asi no importa cuantos alumnos tenga el curso.
    if (window.$.fn.DataTable) {
        // Evitar doble inicializacion: el footer global tambien convierte tablas .table en DataTable
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
    // Re-aplicar asistencia en cada dibujo (busqueda) y al desplegar fila responsive
    $(document).on("draw.dt", aplicarAsistencia);
    $("#tablaEstudiantesPuntos").on("responsive-display.dt", function() { setTimeout(aplicarAsistencia, 10); });
});

cargarAsistenciaHoy();
cargarMotivos();

// ===== Participación en vivo =====
var participacionActiva = null;
var ultimosEnCola = -1;

function cargarMotivosParticipacion() {
    if (motivosCache.length > 0) {
        return $.Deferred().resolve().promise();
    }
    return cargarMotivos();
}

function renderSelectMotivos(estId, seleccionado) {
    var html = "<select class=\"form-control form-control-sm sel-motivo\" data-eid=\"" + estId + "\" disabled><option value=\"\">Seleccione motivo</option>";
    motivosCache.forEach(function(m) {
        html += "<option value=\"" + m.id + "\"" + (String(seleccionado) === String(m.id) ? " selected" : "") + ">" + m.nombre + "</option>";
    });
    html += "</select>";
    return html;
}

function renderFilaCola(c, pid) {
    var hora = c.created_at ? new Date(c.created_at.replace(" ", "T")).toLocaleTimeString("es-PE", { hour: "2-digit", minute: "2-digit" }) : "";
    var marcado = parseInt(c.participo) === 1;
    var html = "<tr data-pid=\"" + pid + "\" data-eid=\"" + c.estudiante_id + "\" data-orden=\"" + c.orden + "\">";
    html += "<td class=\"text-center font-weight-bold\">" + c.orden + "</td>";
    html += "<td>" + (c.apellidos ? c.apellidos + ", " : "") + c.nombres + "</td>";
    html += "<td class=\"text-center text-muted small\">" + hora + "</td>";
    html += "<td class=\"text-center\"><input type=\"checkbox\" class=\"chk-participa\" " + (marcado ? "checked" : "") + "></td>";
    html += "<td>" + renderSelectMotivos(c.estudiante_id, c.motivo_id ? c.motivo_id : "") + "</td>";
    html += "<td class=\"text-center\"><input type=\"number\" class=\"form-control form-control-sm input-puntos text-center motivos-puntos\" min=\"-100\" max=\"100\" value=\"" + (parseInt(c.puntos) || 0) + "\" data-eid=\"" + c.estudiante_id + "\" " + (marcado ? "" : "disabled") + "></td>";
    html += "</tr>";
    return html;
}

function actualizarColaIncremental(cola) {
    var $panel = $("#panelCola");
    var pid = participacionActiva ? participacionActiva.id : 0;
    if (!pid || !cola) return;

    // Crear la estructura base la primera vez
    if (!$panel.find("table.cola-tabla").length) {
        $panel.html("<hr><h6 class=\"card-title d-block\"><i class=\"fas fa-list-ol mr-1\"></i> Cola de participaci&oacute;n</h6>" +
            "<div class=\"table-responsive\">" +
            "<table class=\"table table-sm table-bordered table-hover mb-0 cola-tabla nodt\"><thead class=\"thead-light\"><tr>" +
            "<th class=\"text-center\" style=\"width:40px;\">#</th>" +
            "<th>Estudiante</th>" +
            "<th class=\"text-center\" style=\"width:90px;\">Hora</th>" +
            "<th class=\"text-center\" style=\"width:70px;\">Particip&oacute;</th>" +
            "<th style=\"min-width:220px;\">Motivo</th>" +
            "<th class=\"text-center\" style=\"width:80px;\">Puntos</th>" +
            "</tr></thead><tbody></tbody></table>" +
            "</div>" +
            "<div class=\"text-right mt-2\"><button type=\"button\" class=\"btn btn-danger btn-sm\" id=\"btnCerrarCola\" data-pid=\"" + pid + "\"><i class=\"fas fa-stop mr-1\"></i> Cerrar participaci&oacute;n</button></div>")
            .removeClass("d-none");
    }

    var $tbody = $panel.find("table.cola-tabla tbody");

    // Materializar la lista de estudiantes ya presentes en la tabla
    var presentes = {};
    $tbody.find("tr").each(function() {
        presentes[$(this).data("eid")] = true;
    });

    // Filas pendientes que aun no estan en la tabla -> se agregan (no se tocan las existentes)
    var nuevas = (cola || []).filter(function(c) {
        return c.estado === "pendiente" && !presentes[String(c.estudiante_id)];
    });
    nuevas.forEach(function(c) {
        var $fila = $(renderFilaCola(c, pid));
        var participa = $fila.find(".chk-participa").prop("checked");
        $fila.find(".sel-motivo").prop("disabled", !participa);
        $fila.find(".input-puntos").prop("disabled", !participa);
        $tbody.append($fila);
    });

    // Si aun no hay ninguna fila pendiente, mostrar mensaje
    var hayPendientes = (cola || []).some(function(c) { return c.estado === "pendiente"; });
    if (!hayPendientes) {
        if (!$tbody.find("tr").length) {
            $tbody.html("<tr><td colspan=\"6\" class=\"text-center text-muted py-3\"><i class=\"fas fa-users mr-1\"></i> A&uacute;n nadie se ha registrado. Esperando a los estudiantes...</td></tr>");
        }
    }else{
        // Solo poblar los combos de motivo que aun no tienen opciones (primera carga).
        // NO reemplazar los que ya estan poblados para no interferir la seleccion del docente.
        $tbody.find(".sel-motivo").each(function() {
            var $sel = $(this);
            if ($sel.find("option").length <= 1) {
                var conserva = $sel.val();
                var html = "<option value=\"\">Seleccione motivo</option>";
                motivosCache.forEach(function(m) {
                    html += "<option value=\"" + m.id + "\"" + (String(conserva) === String(m.id) ? " selected" : "") + ">" + m.nombre + "</option>";
                });
                $sel.html(html).val(conserva);
            }
        });
        // Re-aplicar estado de habilitado a checkbox/puntos
        aplicarFiltrosCola();
    }
}

function aplicarFiltrosCola(cola) {
    // Solo los que están en pendiente se gestionan; habilitar los marcados
    $("#panelCola tr").each(function() {
        var $tr = $(this);
        var participa = $tr.find(".chk-participa").prop("checked");
        $tr.find(".sel-motivo").prop("disabled", !participa);
        $tr.find(".motivos-puntos").prop("disabled", !participa);
    });
}

function refrescarEstadoParticipacion() {
    if (!cursoId) return;
    $.ajax({
        url: "' . BASE_URL . 'docente/estadoParticipacion",
        type: "GET",
        data: { curso_id: cursoId, semana_id: semanaEfectivaId() },
        dataType: "json",
        success: function(res) {
            if (!res.success) return;
            if (res.activa) {
                participacionActiva = res.activa;
                $("#btnActivarParticipacion").addClass("d-none");
                $("#btnCerrarParticipacion").removeClass("d-none").attr("data-pid", res.activa.id);
                $("#estadoParticipacion").removeClass("d-none");
                $("#estadoConteo").text(res.cola.length + " en la cola");
                // Actualizar la cola de forma incremental: agrega solo los estudiantes nuevos
                // sin re-renderizar las filas existentes (no pierde lo que el docente va marcando).
                actualizarColaIncremental(res.cola);
            } else {
                participacionActiva = null;
                ultimosEnCola = -1;
                $("#btnActivarParticipacion").removeClass("d-none");
                $("#btnCerrarParticipacion").addClass("d-none");
                $("#estadoParticipacion").addClass("d-none");
                $("#panelCola").addClass("d-none").html("");
            }
        }
    });
}

$(document).on("click", "#btnActivarParticipacion", function() {
    if (!cursoId) { Swal.fire("Aviso", "Seleccione un curso.", "warning"); return; }
    if (!semanaEfectivaId()) { Swal.fire("Aviso", "No hay semana activa.", "warning"); return; }
    var btn = $(this);
    btn.prop("disabled", true);
    $.ajax({
        url: "' . BASE_URL . 'docente/activarParticipacion",
        type: "POST",
        data: { curso_id: cursoId, semana_id: semanaEfectivaId(), _csrf_token: $("meta[name=csrf-token]").attr("content") },
        dataType: "json",
        success: function(res) {
            btn.prop("disabled", false);
            if (res.success) {
                Swal.fire({ icon: "success", title: "Participación activada", text: res.message, timer: 2000, showConfirmButton: false });
                refrescarEstadoParticipacion();
            } else {
                Swal.fire({ icon: "error", title: "Error", text: res.message });
            }
        },
        error: function() {
            btn.prop("disabled", false);
            Swal.fire({ icon: "error", title: "Error", text: "Error de conexión." });
        }
    });
});

$(document).on("click", "#btnCerrarParticipacion, #btnCerrarCola", function() {
    var pid = parseInt($(this).data("pid") || participacionActiva.id || 0, 10);
    if (!pid) {
        Swal.fire({ icon: "warning", title: "Sin participación activa", text: "No hay ninguna participación abierta para cerrar." });
        return;
    }
    Swal.fire({
        title: "Cerrar participación",
        text: "Se eliminarán los que no participaron y se guardarán los puntos de los marcados con motivo. ¿Continuar?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, cerrar",
        cancelButtonText: "Cancelar"
    }).then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "' . BASE_URL . 'docente/cerrarParticipacion",
            type: "POST",
            data: { participacion_id: pid, _csrf_token: $("meta[name=csrf-token]").attr("content") },
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    Swal.fire({ icon: "success", title: "Cerrada", text: res.message, timer: 2500, showConfirmButton: false });
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: "error", title: "Error", text: res.message || "No se pudo cerrar la participación." });
                }
            },
            error: function(xhr) {
                var msg = "Error de conexión.";
                try { var j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; } catch (e) {}
                Swal.fire({ icon: "error", title: "Error", text: msg });
            }
        });
    });
});

$(document).on("change", ".chk-participa", function() {
    var $tr = $(this).closest("tr");
    var pid = $tr.data("pid");
    var eid = $tr.data("eid");
    var participa = $(this).prop("checked") ? 1 : 0;
    $tr.find(".sel-motivo").prop("disabled", !participa);
    $tr.find(".motivos-puntos").prop("disabled", !participa);
    $.ajax({
        url: "' . BASE_URL . 'docente/marcarParticipacion",
        type: "POST",
        data: { participacion_id: pid, estudiante_id: eid, participa: participa, _csrf_token: $("meta[name=csrf-token]").attr("content") },
        dataType: "json"
    });
});

$(document).on("change", ".sel-motivo", function() {
    var $tr = $(this).closest("tr");
    hmMarcarCola($tr, "motivo");
});

$(document).on("change", ".motivos-puntos", function() {
    var $tr = $(this).closest("tr");
    hmMarcarCola($tr, "puntos");
});

function hmMarcarCola($tr, tipo) {
    var pid = $tr.data("pid");
    var eid = $tr.data("eid");
    var motivoId = $tr.find(".sel-motivo").val();
    var puntos = parseInt($tr.find(".motivos-puntos").val()) || 0;
    $.ajax({
        url: "' . BASE_URL . 'docente/asignarMotivoParticipacion",
        type: "POST",
        data: { participacion_id: pid, estudiante_id: eid, motivo_id: motivoId, puntos: puntos, _csrf_token: $("meta[name=csrf-token]").attr("content") },
        dataType: "json"
    });
}

refrescarEstadoParticipacion();
setInterval(refrescarEstadoParticipacion, 2000);
';
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
