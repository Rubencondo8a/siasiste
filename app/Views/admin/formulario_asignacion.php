<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $asignacion ? 'Editar Asignación' : 'Nueva Asignación' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarAsignacion" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="curso_id">Curso *</label>
                            <select class="form-control" id="curso_id" name="curso_id" required <?= $asignacion ? 'disabled' : '' ?>>
                                <option value="">-- Seleccionar Curso --</option>
                                <?php if (!empty($cursos)): ?>
                                    <?php foreach ($cursos as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($asignacion['id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if ($asignacion): ?>
                                <input type="hidden" name="curso_id" value="<?= $asignacion['id'] ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="periodo_id">Periodo Académico *</label>
                            <select class="form-control" id="periodo_id" name="periodo_id" required>
                                <option value="">-- Seleccionar Periodo --</option>
                                <?php if (!empty($periodos)): ?>
                                    <?php foreach ($periodos as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($asignacion['periodo_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nombre']) ?>
                                            <?= $p['estado'] ? '' : ' (Inactivo)' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="docente_id">Docente Asignado</label>
                            <select class="form-control" id="docente_id" name="docente_id">
                                <option value="">-- Sin asignar --</option>
                                <?php if (!empty($docentes)): ?>
                                    <?php foreach ($docentes as $d): ?>
                                        <option value="<?= $d['id'] ?>" <?= ($asignacion['docente_id'] ?? '') == $d['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['apellidos'] . ', ' . $d['nombres']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="callout callout-info">
                    <h5>Información</h5>
                    <p class="mb-0">Seleccione el curso, el periodo académico y el docente que será responsable. El docente solo verá los cursos asignados en periodos <strong>activos</strong>.</p>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Asignación
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/asignaciones" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

<?php if (empty($isAjax)): ?>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>
