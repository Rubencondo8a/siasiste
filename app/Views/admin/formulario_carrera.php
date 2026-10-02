<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $carrera ? 'Editar Carrera' : 'Nueva Carrera' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarCarrera" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($carrera): ?>
                    <input type="hidden" name="id" value="<?= $carrera['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="nombre">Nombre de la Carrera *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   placeholder="Ej: Ingeniería en Sistemas Computacionales"
                                   value="<?= htmlspecialchars($carrera['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="codigo">Código *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" required
                                   placeholder="Ej: ISC"
                                   value="<?= htmlspecialchars($carrera['codigo'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                              placeholder="Descripción de la carrera..."><?= htmlspecialchars($carrera['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="total_ciclos">Total de Ciclos *</label>
                            <input type="number" class="form-control" id="total_ciclos" name="total_ciclos" required
                                   min="1" max="20" placeholder="Ej: 6"
                                   value="<?= htmlspecialchars($carrera['total_ciclos'] ?? 6) ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="1" <?= ($carrera['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activa</option>
                                <option value="0" <?= ($carrera['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                    </div>
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
                        <a href="<?= BASE_URL ?>admin/carreras" class="btn btn-secondary">
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
