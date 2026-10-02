<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $beneficio ? 'Editar Beneficio' : 'Nuevo Beneficio' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarBeneficio" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($beneficio): ?>
                    <input type="hidden" name="id" value="<?= $beneficio['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre del Beneficio *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   placeholder="Ej: Punto Extra"
                                   value="<?= htmlspecialchars($beneficio['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tipo">Tipo *</label>
                            <select class="form-control" id="tipo" name="tipo" required>
                                <option value="">-- Seleccione --</option>
                                <option value="punto_extra" <?= ($beneficio['tipo'] ?? '') === 'punto_extra' ? 'selected' : '' ?>>⭐ Punto Extra en Nota</option>
                                <option value="extension_plazo" <?= ($beneficio['tipo'] ?? '') === 'extension_plazo' ? 'selected' : '' ?>>🕐 Extensión de Plazo</option>
                                <option value="examen_extra" <?= ($beneficio['tipo'] ?? '') === 'examen_extra' ? 'selected' : '' ?>>📋 Examen Extra</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                              placeholder="Describa en qué consiste este beneficio"><?= htmlspecialchars($beneficio['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="costo_gemas">Costo en Cristales (💎) *</label>
                            <input type="number" class="form-control" id="costo_gemas" name="costo_gemas" min="1" required
                                   placeholder="Ej: 1"
                                   value="<?= $beneficio['costo_gemas'] ?? 1 ?>">
                            <small class="form-text text-muted">Número de cristales que cuesta canjear este beneficio.</small>
                        </div>
                    </div>
                    <div class="col-md-4" id="puntosBonusGroup" style="<?= ($beneficio['tipo'] ?? '') === 'punto_extra' ? '' : 'display:none;' ?>">
                        <div class="form-group">
                            <label for="puntos_bonus">Puntos que suma</label>
                            <input type="number" class="form-control" id="puntos_bonus" name="puntos_bonus" min="1" max="20"
                                   placeholder="Ej: 1"
                                   value="<?= $beneficio['puntos_bonus'] ?? 1 ?>">
                            <small class="form-text text-muted">Puntos que se sumarán a la nota más baja de la semana.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="1" <?= ($beneficio['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($beneficio['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
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
                        <a href="<?= BASE_URL ?>admin/beneficios" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

<script>
document.getElementById('tipo').addEventListener('change', function() {
    document.getElementById('puntosBonusGroup').style.display = this.value === 'punto_extra' ? '' : 'none';
});
</script>

<?php if (empty($isAjax)): ?>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>
