<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-gift mr-2"></i>Detalle del Beneficio</h5>
    </div>
    <div class="card-body">
        <?php
            $tipoLabel = $beneficio['tipo'];
            $tipoBadge = 'secondary';
            $tipoIcono = 'fa-question-circle';
            if ($beneficio['tipo'] === 'punto_extra') { $tipoLabel = 'Punto Extra en Nota'; $tipoBadge = 'warning'; $tipoIcono = 'fa-star'; }
            elseif ($beneficio['tipo'] === 'extension_plazo') { $tipoLabel = 'Extensión de Plazo (3 días)'; $tipoBadge = 'info'; $tipoIcono = 'fa-clock'; }
            elseif ($beneficio['tipo'] === 'examen_extra') { $tipoLabel = 'Examen Extra'; $tipoBadge = 'success'; $tipoIcono = 'fa-file-alt'; }
        ?>

        <div class="text-center mb-3">
            <i class="fas <?= $tipoIcono ?> fa-3x text-<?= $tipoBadge ?> mb-2"></i>
            <h4><?= htmlspecialchars($beneficio['nombre']) ?></h4>
            <span class="badge badge-<?= $tipoBadge ?>"><?= $tipoLabel ?></span>
            <?php if ($beneficio['estado']): ?>
                <span class="badge badge-success ml-1">Activo</span>
            <?php else: ?>
                <span class="badge badge-danger ml-1">Inactivo</span>
            <?php endif; ?>
        </div>

        <?php if (!empty($beneficio['descripcion'])): ?>
            <div class="callout callout-info">
                <p class="mb-0"><?= nl2br(htmlspecialchars($beneficio['descripcion'])) ?></p>
            </div>
        <?php endif; ?>

        <dl class="row mb-0">
            <dt class="col-sm-5">Tipo de beneficio</dt>
            <dd class="col-sm-7"><span class="badge badge-<?= $tipoBadge ?>"><i class="fas <?= $tipoIcono ?> mr-1"></i><?= $tipoLabel ?></span></dd>

            <dt class="col-sm-5">Costo en cristales</dt>
            <dd class="col-sm-7"><i class="fas fa-gem text-info mr-1"></i><?= (int) $beneficio['costo_gemas'] ?> cristal(es)</dd>

            <?php if ($beneficio['tipo'] === 'punto_extra'): ?>
            <dt class="col-sm-5">Puntos que suma</dt>
            <dd class="col-sm-7"><i class="fas fa-star text-warning mr-1"></i>+<?= (int) ($beneficio['puntos_bonus'] ?? 1) ?> punto(s) a la nota más baja</dd>
            <?php endif; ?>

            <dt class="col-sm-5">Total de canjes realizados</dt>
            <dd class="col-sm-7"><span class="badge badge-secondary"><?= (int) $beneficio['total_canjes'] ?></span></dd>

            <dt class="col-sm-5">Fecha de creación</dt>
            <dd class="col-sm-7"><?= formatDate($beneficio['created_at']) ?></dd>

            <?php if (!empty($beneficio['updated_at'])): ?>
                <dt class="col-sm-5">Última actualización</dt>
                <dd class="col-sm-7"><?= formatDate($beneficio['updated_at']) ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>
