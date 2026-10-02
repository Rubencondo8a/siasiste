<?php
declare(strict_types=1);
/** Formulario de motivo (crear/editar) — cargado en modal */
$isAjaxView = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
if (!$isAjaxView):
    $pageTitle = $pageTitle ?? 'Motivo de Puntos';
    require_once __DIR__ . '/../layouts/header.php';
endif;
?>

<form id="frmMotivo" onsubmit="return Motivos.guardar(event);">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) ($motivo['id'] ?? 0) ?>">
    <div class="form-group">
        <label>Nombre del motivo *</label>
        <input type="text" name="nombre" class="form-control" maxlength="100" required
               value="<?= htmlspecialchars($motivo['nombre'] ?? '') ?>"
               placeholder="Ej: Participación en clase">
        <small class="text-muted">Máximo 100 caracteres.</small>
    </div>
    <div class="form-group">
        <label>Categoría</label>
        <select name="categoria_id" class="form-control">
            <option value="">— Sin categoría —</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= isset($motivo['categoria_id']) && (int) $motivo['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars(($cat['icono'] ? $cat['icono'] . ' ' : '') . $cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Descripción (razón / cuándo usarlo)</label>
        <textarea name="descripcion" class="form-control" rows="3" maxlength="500"
                  placeholder="Ej: Relaciona conceptos, conecta el tema actual con contenidos anteriores..."><?= htmlspecialchars($motivo['descripcion'] ?? '') ?></textarea>
        <small class="text-muted">Máximo 500 caracteres.</small>
    </div>
    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
    </div>
</form>

<?php if (!$isAjaxView): require_once __DIR__ . '/../layouts/footer.php'; endif; ?>
