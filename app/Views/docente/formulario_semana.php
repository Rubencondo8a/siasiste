<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>
<section class="content-header">
    <h1><?= $semana ? 'Editar Semana' : 'Nueva Semana' ?></h1>
</section>
<section class="content"><div class="card"><div class="card-body">
<?php endif; ?>

<?php $cerrada = $semana ? (int) ($semana['cerrada'] ?? 0) : 0; ?>
<?php if ($cerrada): ?>
<div class="alert alert-danger">
    <i class="fas fa-lock"></i> Esta semana está cerrada. No se puede modificar. Solicite al administrador que la reabra si necesita cambios.
</div>
<?php endif; ?>

<form action="<?= BASE_URL ?>docente/guardarSemana" method="POST" data-ajax="true">
    <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">
    <?php if ($semana): ?>
        <input type="hidden" name="id" value="<?= $semana['id'] ?>">
    <?php endif; ?>

    <div class="row">
        <div class="col-md-2">
            <div class="form-group">
                <label for="numero_semana">N° Semana *</label>
                <input type="number" class="form-control" id="numero_semana" name="numero_semana" required min="1"
                       value="<?= $semana['numero_semana'] ?? '' ?>" <?= $cerrada ? 'disabled' : '' ?>>
            </div>
        </div>
        <div class="col-md-10">
            <div class="form-group">
                <label for="nombre">Título *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required maxlength="100"
                       placeholder="Ej: Introducción"
                       value="<?= htmlspecialchars($semana['nombre'] ?? '') ?>" <?= $cerrada ? 'disabled' : '' ?>>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" maxlength="200" rows="2"
                          placeholder="Descripción opcional (máx. 200 caracteres)" <?= $cerrada ? 'disabled' : '' ?>><?= htmlspecialchars($semana['descripcion'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="fecha_inicio">Fecha Inicio *</label>
                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required
                       value="<?= $semana['fecha_inicio'] ?? '' ?>"
                       min="<?= isset($periodo) ? htmlspecialchars($periodo['periodo_inicio']) : '' ?>"
                       max="<?= isset($periodo) ? htmlspecialchars($periodo['periodo_fin']) : '' ?>"
                       <?= $cerrada ? 'disabled' : '' ?>>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="fecha_fin">Fecha Fin *</label>
                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" required
                       value="<?= $semana['fecha_fin'] ?? '' ?>"
                       min="<?= isset($periodo) ? htmlspecialchars($periodo['periodo_inicio']) : '' ?>"
                       max="<?= isset($periodo) ? htmlspecialchars($periodo['periodo_fin']) : '' ?>"
                       <?= $cerrada ? 'disabled' : '' ?>>
            </div>
        </div>
    </div>

    <?php if (isset($periodo) && $periodo['periodo_inicio'] && $periodo['periodo_fin']): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Rango académico:</strong> La semana debe estar contenida entre el <strong><?= formatDate($periodo['periodo_inicio']) ?></strong>
        y el <strong><?= formatDate($periodo['periodo_fin']) ?></strong> (periodo <em><?= htmlspecialchars($periodo['periodo_nombre']) ?></em>).
        No se pueden registrar fechas fuera de este rango.
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <?php if (!$cerrada): ?>
        <button type="submit" class="btn btn-info">
            <i class="fas fa-save"></i> Guardar
        </button>
        <?php endif; ?>
        <?php if (empty($isAjax)): ?>
        <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $curso['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Cancelar
        </a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($isAjax)): ?>
</div></div></section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>
