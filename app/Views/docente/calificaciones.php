<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/calificaciones.css?v=<?= filemtime(ROOT_PATH . 'public/css/calificaciones.css') ?>">

<section class="content-header">
    <h1>Calificaciones - Semana <?= $semana['numero_semana'] ?>: <?= htmlspecialchars($semana['nombre']) ?></h1>
</section>

<section class="content">
    <?php $cerrada = (int) ($semana['cerrada'] ?? 0); ?>

    <?php if ($cerrada): ?>
    <div class="alert alert-danger">
        <i class="fas fa-lock"></i> Esta semana está cerrada. No se pueden modificar las calificaciones.
    </div>
    <?php endif; ?>

    <?php if (empty($criterios)): ?>
        <div class="callout callout-warning">
            <h5>No hay criterios definidos</h5>
            <p>Debe crear al menos un criterio de evaluación antes de registrar calificaciones.</p>
            <a href="<?= BASE_URL ?>docente/criterios?semana_id=<?= $semana['id'] ?>" class="btn btn-warning btn-sm">
                <i class="fas fa-clipboard-list"></i> Crear Criterios
            </a>
        </div>
    <?php elseif (empty($estudiantes)): ?>
        <div class="callout callout-info">
            <h5>No hay estudiantes</h5>
            <p>No hay estudiantes activos registrados en el sistema.</p>
        </div>
    <?php else: ?>
        <form action="<?= BASE_URL ?>docente/guardarNotas" method="POST" id="gradesForm">
            <?= csrfField() ?>
            <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">

            <?php if ($criterioGrupal && !empty($grupos)): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users mr-1"></i> Calificación grupal: <?= htmlspecialchars($criterioGrupal['nombre']) ?> (<?= number_format((float) $criterioGrupal['porcentaje'], 0) ?>%)</h3>
                    <div class="card-tools">
                        <a href="<?= BASE_URL ?>docente/grupos?semana_id=<?= $semana['id'] ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-users"></i> Gestionar Grupos
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover mb-0 nodt">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:80px;">Grupo</th>
                                <th>Integrantes</th>
                <th class="text-center" style="max-width:220px;">
                    <span class="criterion-title" style="max-width:200px;" title="<?= htmlspecialchars($criterioGrupal['nombre']) ?>"><?= htmlspecialchars($criterioGrupal['nombre']) ?></span><br>
                    <small class="text-muted">(<?= number_format((float) $criterioGrupal['porcentaje'], 0) ?>%)</small>
                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($grupos as $grupo): ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <span class="badge badge-info"><?= $grupo['numero'] ?></span>
                                </td>
                                <td class="small">
                                    <?php
                                    $nombresGrupo = array_map(function ($mi) {
                                        return $mi['apellidos'] . ', ' . $mi['nombres'];
                                    }, $grupo['integrantes']);
                                    echo htmlspecialchars(implode('; ', $nombresGrupo));
                                    ?>
                                </td>
                                <td class="text-center">
                                    <?php
                                    $notaGrupo = '';
                                    if (!empty($grupo['integrantes'])) {
                                        $primerId = (int) $grupo['integrantes'][0]['id'];
                                        $notaGrupo = $calificaciones[$primerId][$criterioGrupal['id']] ?? '';
                                    }
                                    ?>
                                    <input type="text"
                                           inputmode="decimal"
                                           pattern="[0-9]*\.?[0-9]{0,2}"
                                           autocomplete="off"
                                           name="grupos[<?= $grupo['id'] ?>][<?= $criterioGrupal['id'] ?>]"
                                           class="form-control grade-input grade-grupo-input mx-auto"
                                           onkeydown="return blockNonNumericKey(event)"
                                           oninput="sanitizeGradeInput(this)"
                                           value="<?= htmlspecialchars($notaGrupo !== '' ? number_format((float) $notaGrupo, 2, '.', '') : '') ?>"
                                           data-grupo-id="<?= $grupo['id'] ?>"
                                           data-criterio-id="<?= $criterioGrupal['id'] ?>"
                                           data-porcentaje="<?= $criterioGrupal['porcentaje'] ?>"
                                           <?= $cerrada ? 'readonly' : '' ?>>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-pen-square mr-1"></i> Ingrese las calificaciones</h3>
                    <div class="float-right">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCalculateAll">
                            <i class="fas fa-calculator"></i> Promedios
                        </button>
                    </div>
                </div>

                <!-- ========== ESCRITORIO ========== -->
                <div class="card-body table-responsive grades-desktop">
                    <table class="table table-bordered table-hover nodt" id="gradesTable">
                        <thead>
                            <tr>
                                <th rowspan="2" class="align-middle">#</th>
                                <th rowspan="2" class="align-middle">Estudiante</th>
                                <?php foreach ($criterios as $criterio): ?>
                                    <?php $esGrupalCol = (int) ($criterio['es_grupal'] ?? 0); ?>
                                    <th class="text-center" style="max-width:140px;">
                                        <span class="criterion-title" title="Ver detalle del criterio: <?= htmlspecialchars($criterio['nombre']) ?> (<?= number_format((float)$criterio['porcentaje'], 0) ?>%)"><?= htmlspecialchars($criterio['nombre']) ?></span><br>
                                        <small class="text-muted">(<?= number_format((float)$criterio['porcentaje'], 0) ?>%)</small><br>
                                        <?php if ($esGrupalCol): ?>
                                            <small class="text-info"><i class="fas fa-users"></i> grupal</small>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-xs btn-outline-warning mt-1 toggle-column d-none"
                                                    data-criterio="<?= $criterio['id'] ?>" title="Editar notas de este criterio">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                                <th rowspan="2" class="align-middle text-center">Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $idx = 1; foreach ($estudiantes as $estudiante): ?>
                                <tr>
                                    <td class="text-center align-middle"><?= $idx++ ?></td>
                                    <td><strong><?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?></strong></td>
                                    <?php foreach ($criterios as $criterio): ?>
                                        <?php
                                        $esGrupalCol = (int) ($criterio['es_grupal'] ?? 0);
                                        $notaValue = '';
                                        if (isset($calificaciones[$estudiante['id']][$criterio['id']])) {
                                            $notaValue = $calificaciones[$estudiante['id']][$criterio['id']];
                                        }
                                        ?>
                                         <td class="text-center">
                                             <?php if ($esGrupalCol): ?>
                                                 <?php $grupoDeEstudiante = $gruposPorEstudiante[$estudiante['id']]['grupo_id'] ?? null; ?>
                                                 <input type="text"
                                                        class="form-control grade-input grade-grupo-celda mx-auto"
                                                        readonly
                                                        tabindex="-1"
                                                        value="<?= htmlspecialchars($notaValue !== '' ? number_format((float)$notaValue, 2, '.', '') : '') ?>"
                                                        data-grupo-id="<?= $grupoDeEstudiante ?? '' ?>"
                                                        data-criterio-id="<?= $criterio['id'] ?>"
                                                        data-porcentaje="<?= $criterio['porcentaje'] ?>"
                                                        data-student-id="<?= $estudiante['id'] ?>">
                                             <?php else: ?>
                                                 <input type="text"
                                                        inputmode="decimal"
                                                        pattern="[0-9]*\.?[0-9]{0,2}"
                                                        autocomplete="off"
                                                        name="grades[<?= $estudiante['id'] ?>][<?= $criterio['id'] ?>]"
                                                        class="form-control grade-input mx-auto"
                                                        onkeydown="return blockNonNumericKey(event)"
                                                        oninput="sanitizeGradeInput(this)"
                                                        value="<?= htmlspecialchars($notaValue !== '' ? number_format((float)$notaValue, 2, '.', '') : '') ?>"
                                                        data-porcentaje="<?= $criterio['porcentaje'] ?>"
                                                        data-criterio-id="<?= $criterio['id'] ?>"
                                                        data-student-id="<?= $estudiante['id'] ?>"
                                                        data-bloqueado="<?= (int)$criterio['bloqueado'] ?>">
                                             <?php endif; ?>
                                         </td>
                                    <?php endforeach; ?>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-lg promedio-badge" id="promedio-desktop-<?= $estudiante['id'] ?>">--</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ========== MOVIL ========== -->
                <div class="card-body grades-mobile">
                    <?php foreach ($estudiantes as $estudiante): ?>
                        <div class="card mb-3 student-card shadow-sm">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <strong class="student-name-mobile">
                                    <?= htmlspecialchars($estudiante['apellidos'] . ', ' . $estudiante['nombres']) ?>
                                </strong>
                                <span class="badge badge-pill badge-secondary promedio-badge-mobile" id="promedio-mobile-<?= $estudiante['id'] ?>">--</span>
                            </div>
                            <div class="card-body p-2">
                                <?php foreach ($criterios as $criterio):
                                    $esGrupalCol = (int) ($criterio['es_grupal'] ?? 0);
                                    $notaValue = '';
                                    if (isset($calificaciones[$estudiante['id']][$criterio['id']])) {
                                        $notaValue = $calificaciones[$estudiante['id']][$criterio['id']];
                                    }
                                ?>
                                    <div class="mobile-criterio-block">
                                        <label class="mobile-criterio-label">
                                            <?= htmlspecialchars($criterio['nombre']) ?>
                                            <span class="text-muted">(<?= number_format((float)$criterio['porcentaje'], 0) ?>%)</span>
                                            <?php if ($esGrupalCol): ?>
                                                <small class="text-info"><i class="fas fa-users"></i></small>
                                            <?php endif; ?>
                                        </label>
                                        <div class="mobile-criterio-input">
                                            <?php if ($esGrupalCol): ?>
                                                <?php $grupoDeEstudiante = $gruposPorEstudiante[$estudiante['id']]['grupo_id'] ?? null; ?>
                                                <input type="text"
                                                       class="form-control grade-input grade-grupo-celda"
                                                       readonly
                                                       tabindex="-1"
                                                       value="<?= htmlspecialchars($notaValue !== '' ? number_format((float)$notaValue, 2, '.', '') : '') ?>"
                                                       data-grupo-id="<?= $grupoDeEstudiante ?? '' ?>"
                                                       data-criterio-id="<?= $criterio['id'] ?>"
                                                       data-porcentaje="<?= $criterio['porcentaje'] ?>"
                                                       data-student-id="<?= $estudiante['id'] ?>">
                                            <?php else: ?>
                                                <input type="text"
                                                       inputmode="decimal"
                                                       pattern="[0-9]*\.?[0-9]{0,2}"
                                                       autocomplete="off"
                                                       name="grades[<?= $estudiante['id'] ?>][<?= $criterio['id'] ?>]"
                                                       class="form-control grade-input"
                                                       onkeydown="return blockNonNumericKey(event)"
                                                       oninput="sanitizeGradeInput(this)"
                                                       value="<?= htmlspecialchars($notaValue !== '' ? number_format((float)$notaValue, 2, '.', '') : '') ?>"
                                                       data-porcentaje="<?= $criterio['porcentaje'] ?>"
                                                       data-criterio-id="<?= $criterio['id'] ?>"
                                                       data-student-id="<?= $estudiante['id'] ?>"
                                                       data-bloqueado="<?= (int)$criterio['bloqueado'] ?>">
                                                <button type="button" class="btn btn-xs btn-outline-warning toggle-column-mobile d-none"
                                                        data-criterio="<?= $criterio['id'] ?>" title="Editar notas de este criterio">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block btn-lg">
                        <i class="fas fa-save mr-1"></i> Guardar Calificaciones
                    </button>
                    <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $semana['curso_id'] ?>" class="btn btn-secondary btn-block mt-2">
                        <i class="fas fa-arrow-left mr-1"></i> Volver
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
$inlineScripts = ['$(document).ready(function() {
    var $form = $("#gradesForm");
    var toggleUrl = "' . BASE_URL . 'docente/alternarBloqueoCriterio";

    window.blockNonNumericKey = function(e) {
        var allowedCodes = [8, 9, 13, 27, 36, 35, 37, 38, 39, 40, 46];
        if (allowedCodes.indexOf(e.keyCode) !== -1) return true;
        if (e.ctrlKey || e.metaKey || e.altKey) return true;
        var key = e.key;
        if (key.length !== 1) return true;
        if (/[0-9]/.test(key)) return true;
        if (key === "." && e.target.value.indexOf(".") === -1) return true;
        e.preventDefault();
        return false;
    };

    window.sanitizeGradeInput = function(el) {
        var v = el.value;
        v = v.replace(/[^0-9.]/g, "");
        var parts = v.split(".");
        if (parts.length > 2) v = parts[0] + "." + parts.slice(1).join("");
        if (v.indexOf(".") === 0) v = "0" + v;
        if (v.indexOf(".") !== -1) v = v.slice(0, v.indexOf(".") + 3);
        if (v !== "" && v !== "." && parseFloat(v) > 20) v = "20";
        el.value = v;
    };

    window.formatGradeInput = function(el) {
        var v = el.value;
        if (v === "" || v === ".") { el.value = ""; return; }
        var num = parseFloat(v);
        el.value = (isNaN(num) || num < 0) ? "" : num.toFixed(2);
    };

    function isDesktop() { return $(window).width() > 768; }

    function $visibleInputs(criterioId) {
        var scope = isDesktop() ? ".grades-desktop" : ".grades-mobile";
        return $(scope + " input[data-criterio-id=\"" + criterioId + "\"]").not(".grade-grupo-celda");
    }

    function $allInputs(criterioId) {
        return $("input[data-criterio-id=\"" + criterioId + "\"]").not(".grade-grupo-celda");
    }

    function $visibleInputsByStudent(studentId) {
        var scope = isDesktop() ? ".grades-desktop" : ".grades-mobile";
        return $(scope + " input[data-student-id=\"" + studentId + "\"]");
    }

    function calculateStudentAverage(studentId) {
        var totalWeighted = 0;
        var totalPercentage = 0;
        var hasAny = false;

        $visibleInputsByStudent(studentId).each(function() {
            var val = parseFloat($(this).val());
            var porcentaje = parseFloat($(this).data("porcentaje"));
            if (!isNaN(val) && val >= 0) {
                totalWeighted += val * (porcentaje / 100);
                totalPercentage += porcentaje;
                hasAny = true;
            }
        });

        var $el = $("#promedio-desktop-" + studentId + ", #promedio-mobile-" + studentId);
        if (hasAny && totalPercentage > 0) {
            var avg = (totalWeighted / (totalPercentage / 100));
            $el.text(avg.toFixed(2));
            var color = avg < 13 ? "#dc3545" : "#0d6efd";
            $el.css("color", color);
        } else {
            $el.text("--").css("color", "");
        }
    }

    function isCriterionLockedFromDB(criterioId) {
        var $sample = $("input[data-criterio-id=\"" + criterioId + "\"]").first();
        return parseInt($sample.data("bloqueado")) === 1;
    }

    var unlockedCols = {};

    function setCriterionBloqueado(criterioId, val) {
        $allInputs(criterioId).data("bloqueado", val).attr("data-bloqueado", val);
    }

    var initialFull = {};

    function checkColumnComplete(criterioId, userInitiated) {
        var $inputs = $visibleInputs(criterioId);
        var total = $inputs.length;
        var filled = 0;
        $inputs.each(function() {
            if ($(this).val() !== "" && $(this).val() !== null) filled++;
        });

        var allFilled = (total > 0 && filled >= total);
        var dbLocked = isCriterionLockedFromDB(criterioId);
        var justUnlocked = unlockedCols[criterioId] === true;

        var autoLock = allFilled && !justUnlocked && userInitiated && !initialFull[criterioId];
        var locked = dbLocked || autoLock;

        var $btnDesktop = $("button.toggle-column[data-criterio=\"" + criterioId + "\"]");
        var $btnMobile  = $("button.toggle-column-mobile[data-criterio=\"" + criterioId + "\"]");

        if (locked) {
            $inputs.prop("readonly", true).addClass("bg-light");
            $btnDesktop.removeClass("d-none btn-outline-warning").addClass("btn-success").html("<i class=\"fas fa-lock\"></i>");
            $btnMobile.removeClass("d-none btn-outline-warning").addClass("btn-success").html("<i class=\"fas fa-lock\"></i>");
        } else {
            $inputs.prop("readonly", false).removeClass("bg-light");
            $btnDesktop.removeClass("d-none btn-success").addClass("btn-outline-warning").html("<i class=\"fas fa-lock-open\"></i>");
            $btnMobile.removeClass("d-none btn-success").addClass("btn-outline-warning").html("<i class=\"fas fa-lock-open\"></i>");
        }
    }

    function checkAllColumns(userInitiated) {
        $("button.toggle-column, button.toggle-column-mobile").each(function() {
            checkColumnComplete($(this).data("criterio"), userInitiated || false);
        });
    }

    $(".grade-input").each(function() {
        var cid = $(this).data("criterio-id");
        if (!initialFull[cid]) initialFull[cid] = { total: 0, filled: 0 };
        initialFull[cid].total++;
        if ($(this).val() !== "" && $(this).val() !== null) initialFull[cid].filled++;
    });
    Object.keys(initialFull).forEach(function(k) {
        initialFull[k] = initialFull[k].filled >= initialFull[k].total;
    });

    $(document).on("click", "button.toggle-column, button.toggle-column-mobile", function() {
        var criterioId = $(this).data("criterio");
        var $btn = $(this);
        $btn.prop("disabled", true);

        $.post(toggleUrl, {
            criterio_id: criterioId,
            _csrf_token: $("input[name=_csrf_token]").val()
        }, function(res) {
            if (res.success) {
                setCriterionBloqueado(criterioId, res.bloqueado);
                if (res.bloqueado === 0) {
                    unlockedCols[criterioId] = true;
                } else {
                    delete unlockedCols[criterioId];
                }
                checkColumnComplete(criterioId, true);
                $visibleInputs(criterioId).first().focus();
            } else {
                AdminModals.showToast("error", res.message || "Error al cambiar estado.");
            }
            $btn.prop("disabled", false);
        }, "json").fail(function() {
            $btn.prop("disabled", false);
            AdminModals.showToast("error", "Error al cambiar estado del criterio.");
        });
    });

    function colorGradeInput($input) {
        var v = parseFloat($input.val());
        $input.removeClass("grade-bajo grade-alto");
        if (!isNaN(v) && v >= 0) {
            $input.addClass(v < 13 ? "grade-bajo" : "grade-alto");
        }
    }

    $(".grade-input").on("input", function() {
        colorGradeInput($(this));
        calculateStudentAverage($(this).data("student-id"));
    });

    $(".grade-input").each(function() { colorGradeInput($(this)); });

    function syncGrupoNota($input) {
        var gid = $input.data("grupo-id");
        var val = $input.val();
        if (gid === undefined || gid === null || gid === "") return;

        var $celdas = $(".grade-grupo-celda[data-grupo-id=\"" + gid + "\"]");
        $celdas.val(val);
        $celdas.each(function() {
            colorGradeInput($(this));
            calculateStudentAverage($(this).data("student-id"));
        });
    }

    $(document).on("input blur", ".grade-grupo-input", function() {
        syncGrupoNota($(this));
    });

    $(".grade-input").on("blur", function() {
        formatGradeInput(this);
        checkColumnComplete($(this).data("criterio-id"), true);
    });

    $form.on("submit", function() {
        if ($(window).width() > 768) {
            $(".grades-mobile input.grade-input").prop("disabled", true);
        } else {
            $(".grades-desktop input.grade-input").prop("disabled", true);
        }
    });

    $("#btnCalculateAll").on("click", function() {
        var students = [];
        $("input[data-student-id]").each(function() {
            var sid = $(this).data("student-id");
            if (students.indexOf(sid) === -1) students.push(sid);
        });
        students.forEach(function(id) { calculateStudentAverage(id); });
        checkAllColumns(false);
    });

    $("#btnCalculateAll").trigger("click");
' . ($cerrada ? '
    $(".grade-input").prop("readonly", true);
    $("#gradesForm .btn-info").hide();
' : '') . '
});'];
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
