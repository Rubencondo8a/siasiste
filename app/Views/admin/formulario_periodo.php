<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $periodo ? 'Editar Periodo' : 'Nuevo Periodo Académico' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarPeriodo" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($periodo): ?>
                    <input type="hidden" name="id" value="<?= $periodo['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nombre">Nombre del Periodo *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" required
                           placeholder="Ej: Periodo I - 2026"
                           value="<?= htmlspecialchars($periodo['nombre'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha Inicio *</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required
                                   value="<?= $periodo['fecha_inicio'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_fin">Fecha Fin *</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required
                                   value="<?= $periodo['fecha_fin'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="1" <?= ($periodo['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($periodo['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/periodos" class="btn btn-secondary">
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
