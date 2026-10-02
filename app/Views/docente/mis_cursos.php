<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Mis Cursos Asignados</h1>
</section>

<section class="content">
    <?php if (empty($cursos)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <p class="mb-0 font-weight-bold">No tiene cursos asignados en el periodo activo.</p>
            </div>
        </div>
    <?php else: ?>
        <?php
        $iconColors = [
            '#4E37E6',
            '#E63777',
            '#0FA97A',
            '#9CB51F',
            '#2563EB',
            '#2E218A',
        ];
        $i = 0;
        ?>
        <div class="row">
            <?php foreach ($cursos as $curso): ?>
                <?php $color = $iconColors[$i % count($iconColors)]; $i++; ?>
                <div class="col-md-6 col-xl-4 mb-4 d-flex">
                    <div class="course-card w-100">
                        <div class="course-card-top">
                            <div class="course-icon" style="background: <?= $color ?>;">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div class="course-head">
                                <span class="course-code"><?= htmlspecialchars($curso['codigo']) ?></span>
                                <h3 class="course-name" title="<?= htmlspecialchars($curso['nombre']) ?>">
                                    <?= htmlspecialchars($curso['nombre']) ?>
                                </h3>
                            </div>
                        </div>
                        <div class="course-body">
                            <div class="course-meta">
                                <i class="fas fa-layer-group"></i>
                                <span>Ciclo <?= htmlspecialchars($curso['ciclo']) ?></span>
                            </div>
                            <div class="course-meta">
                                <i class="fas fa-calendar-check"></i>
                                <span><?= htmlspecialchars($curso['periodo_nombre']) ?></span>
                            </div>
                            <div class="course-stats">
                                <div class="course-stat">
                                    <i class="fas fa-users"></i>
                                    <span class="stat-num"><?= (int) $curso['total_estudiantes'] ?></span>
                                    <span class="stat-label">Estudiantes</span>
                                </div>
                                <div class="course-stat">
                                    <i class="fas fa-calendar-week"></i>
                                    <span class="stat-num"><?= (int) $curso['total_semanas'] ?></span>
                                    <span class="stat-label">Semanas</span>
                                </div>
                            </div>
                        </div>
                        <div class="course-card-footer">
                            <a href="<?= BASE_URL ?>docente/estudiantesCurso?curso_id=<?= $curso['id'] ?>"
                               class="btn-course" title="Ver / Asignar estudiantes">
                                <i class="fas fa-users"></i> Ver Estudiantes
                            </a>
                            <div class="sillabus-bar">
                                <?php if (!empty($curso['sillabus_pdf'])): ?>
                                    <a href="<?= BASE_URL ?>docente/verSillabus?curso_id=<?= $curso['id'] ?>"
                                       class="btn-syllabus btn-view" target="_blank" title="Ver sílabo">
                                        <i class="fas fa-file-pdf"></i><span>Ver</span>
                                    </a>
                                    <a href="#"
                                       class="btn-syllabus btn-del" title="Eliminar sílabo"
                                       onclick="eliminarSillabus(<?= $curso['id'] ?>); return false;">
                                        <i class="fas fa-trash-alt"></i><span>Borrar</span>
                                    </a>
                                <?php endif; ?>
                                <a href="#"
                                   class="btn-syllabus btn-up" title="Subir o reemplazar el sílabo en PDF"
                                   onclick="openSillabusModal(<?= $curso['id'] ?>, '<?= htmlspecialchars($curso['nombre']) ?>'); return false;">
                                    <i class="fas fa-upload"></i><span>Subir</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<div class="modal fade" id="sillabusModal" tabindex="-1" role="dialog" aria-labelledby="sillabusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="<?= BASE_URL ?>docente/subirSillabus" method="POST" enctype="multipart/form-data">
                <?= csrfField() ?>
                <input type="hidden" name="curso_id" id="sillabusCursoId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="sillabusModalLabel">Subir Sílabo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Curso: <strong id="sillabusCursoNombre" class="text-info"></strong></p>
                    <div class="form-group">
                        <label for="sillabusFile">Archivo PDF del sílabo *</label>
                        <input type="file" class="form-control-file" id="sillabusFile" name="sillabus"
                               accept="application/pdf,.pdf" required>
                        <small class="text-muted">Solo PDF, máximo 5 MB. Si ya existe un sílabo, será reemplazado.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-upload"></i> Subir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$inlineScripts = ['
function openSillabusModal(cursoId, nombre) {
    document.getElementById("sillabusCursoId").value = cursoId;
    document.getElementById("sillabusCursoNombre").textContent = nombre;
    $("#sillabusModal").modal("show");
}

function eliminarSillabus(cursoId) {
    Swal.fire({
        title: "¿Eliminar sílabo?",
        text: "El archivo PDF se eliminará del curso.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#dc3545",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar"
    }).then(function(result) {
        if (result.isConfirmed) {
            window.location.href = window.BASE_URL + "docente/eliminarSillabus?curso_id=" + cursoId;
        }
    });
}
'];
?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
