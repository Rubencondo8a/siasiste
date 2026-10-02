<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>
<section class="content-header">
    <h1><?= $criterio ? 'Editar Criterio' : 'Nuevo Criterio' ?> - Semana <?= $semana['numero_semana'] ?></h1>
</section>
<section class="content"><div class="row"><div class="col-md-8"><div class="card"><div class="card-body">
<?php endif; ?>

    <form action="<?= BASE_URL ?>docente/guardarCriterio" method="POST" data-ajax="true" data-disponible="<?= number_format($porcentajeDisponible, 2, '.', '') ?>" data-es-creacion="<?= $criterio ? 'false' : 'true' ?>">
        <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
        <?php if ($criterio): ?>
            <input type="hidden" name="id" value="<?= $criterio['id'] ?>">
        <?php endif; ?>

    <div class="form-group">
        <label for="nombre">Nombre del Criterio *</label>
        <div class="input-group">
            <input type="text" class="form-control" id="nombre" name="nombre" required
                   placeholder="Ej: Examen Parcial, Tarea, Proyecto..."
                   maxlength="255"
                   value="<?= htmlspecialchars($criterio['nombre'] ?? '') ?>">
            <div class="input-group-append">
                <span id="nombreCount" class="input-group-text text-muted" style="min-width:70px;">0 / 255</span>
            </div>
        </div>
        <small class="form-text text-muted">Máximo 255 caracteres. El nombre debe ser único por semana.</small>
    </div>

        <div class="form-group">
            <label for="porcentaje">Porcentaje (%) *</label>
            <input type="number" class="form-control" id="porcentaje" name="porcentaje" required
                   step="0.01" min="0.01" max="<?= number_format($porcentajeDisponible, 2, '.', '') ?>"
                   placeholder="Ej: 25"
                   value="<?= $criterio['porcentaje'] ?? '' ?>">
            <small class="text-muted">
                Porcentaje disponible: <strong id="dispPct"><?= number_format($porcentajeDisponible, 0) ?>%</strong>
                <?php if ($porcentajeDisponible <= 0 && !$criterio): ?>
                    <span id="avisoFull" class="badge badge-danger ml-2">Semana completa al 100% (reduzca un criterio para agregar uno nuevo)</span>
                <?php else: ?>
                    <span id="avisoFull" class="badge badge-danger ml-2" style="display:none;"></span>
                <?php endif; ?>
            </small>
        </div>

        <div class="form-group">
            <label for="tipo">Tipo de Dominio de Aprendizaje *</label>
            <select class="form-control" id="tipo" name="tipo" required>
                <option value="conceptual" <?= ($criterio['tipo'] ?? 'conceptual') === 'conceptual' ? 'selected' : '' ?>>Saber — Conceptual</option>
                <option value="procedimental" <?= ($criterio['tipo'] ?? '') === 'procedimental' ? 'selected' : '' ?>>Saber Hacer — Procedimental</option>
                <option value="actitudinal" <?= ($criterio['tipo'] ?? '') === 'actitudinal' ? 'selected' : '' ?>>Saber Ser — Actitudinal</option>
            </select>
            <small class="form-text text-muted">Clasifique el criterio según el dominio de aprendizaje.</small>
        </div>

        <div class="mt-3">
            <button type="submit" id="submitBtn" class="btn btn-info" <?= ($porcentajeDisponible <= 0 && !$criterio) ? 'disabled' : '' ?>>
                <i class="fas fa-save"></i> Guardar
            </button>
        <?php if (empty($isAjax)): ?>
        <a href="<?= BASE_URL ?>docente/criterios?semana_id=<?= $semana['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Cancelar
        </a>
        <?php endif; ?>
    </div>
    <script>
    (function () {
        var $n = $('#nombre');
        if (!$n.length) return;
        function upd() {
            var v = $n.val().length;
            var $c = $('#nombreCount');
            $c.text(v + ' / 255')
                .removeClass('text-muted text-warning text-danger')
                .addClass(v >= 240 ? 'text-danger' : (v >= 200 ? 'text-warning' : 'text-muted'));
        }
        $n.off('input').on('input', upd);
        upd();
    })();
    </script>
</form>

<?php if (empty($isAjax)): ?>
</div></div>
<div class="col-md-4">
    <div class="card card-info card-outline">
        <div class="card-header"><h3 class="card-title">Información</h3></div>
        <div class="card-body">
            <p>El porcentaje total de todos los criterios no puede exceder <strong>100%</strong>.</p>
            <p>Porcentaje disponible: <span class="badge badge-success"><?= number_format($porcentajeDisponible, 0) ?>%</span></p>
            <?php if ($criterio): ?>
                <p>Porcentaje actual: <span class="badge badge-warning"><?= number_format((float)$criterio['porcentaje'], 0) ?>%</span></p>
            <?php endif; ?>
        </div>
    </div>
</div>
</div></section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>
