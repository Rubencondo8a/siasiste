<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $plan ? 'Editar Plan de Estudios' : 'Nuevo Plan de Estudios' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarPlan" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($plan): ?>
                    <input type="hidden" name="id" value="<?= $plan['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="carrera_id">Carrera *</label>
                            <select class="form-control" id="carrera_id" name="carrera_id" required>
                                <option value="">-- Seleccione la carrera --</option>
                                <?php foreach ($carreras as $carrera): ?>
                                    <option value="<?= $carrera['id'] ?>" <?= ($plan['carrera_id'] ?? null) == $carrera['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($carrera['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="nombre">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   placeholder="Ej: 2025"
                                   value="<?= htmlspecialchars($plan['nombre'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                              placeholder="Descripción del plan de estudios..."><?= htmlspecialchars($plan['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="1" <?= ($plan['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($plan['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/planes" class="btn btn-secondary">
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
