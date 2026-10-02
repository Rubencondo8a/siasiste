<form action="<?= BASE_URL ?>admin/guardarAmbiente" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= $ambiente['id'] ?? '' ?>">

    <div class="form-group">
        <label>Nombre <span class="text-danger">*</span></label>
        <input type="text" name="nombre" class="form-control" required
               value="<?= htmlspecialchars($ambiente['nombre'] ?? '') ?>" placeholder="Ej: Lab-101">
    </div>

    <div class="form-group">
        <label>Ubicación</label>
        <input type="text" name="ubicacion" class="form-control"
               value="<?= htmlspecialchars($ambiente['ubicacion'] ?? '') ?>" placeholder="Ej: Edificio A, Piso 2">
    </div>

    <div class="form-group">
        <label>Capacidad</label>
        <input type="number" name="capacidad" class="form-control" min="0"
               value="<?= $ambiente['capacidad'] ?? 0 ?>">
    </div>

    <div class="form-group">
        <label>Estado</label>
        <select name="estado" class="form-control">
            <option value="1" <?= ($ambiente['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= ($ambiente['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
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
            <a href="<?= BASE_URL ?>admin/ambientes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
        <?php endif; ?>
    </div>
</form>
