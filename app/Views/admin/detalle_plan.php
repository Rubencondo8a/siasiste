<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2"></i>Información del Plan de Estudios</h5>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Carrera</dt>
            <dd class="col-sm-8"><?= sanitize($plan['carrera_nombre']) ?></dd>

            <dt class="col-sm-4">Nombre</dt>
            <dd class="col-sm-8"><span class="badge badge-info">Plan <?= sanitize($plan['nombre']) ?></span></dd>

            <dt class="col-sm-4">Descripción</dt>
            <dd class="col-sm-8"><?= nl2br(sanitize($plan['descripcion'] ?? '')) ?></dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8">
                <?php if ($plan['estado'] == 1): ?>
                    <span class="badge badge-success">Activo</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactivo</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Cursos asociados</dt>
            <dd class="col-sm-8"><?= (int) $totalCursos ?></dd>
        </dl>
    </div>
</div>
