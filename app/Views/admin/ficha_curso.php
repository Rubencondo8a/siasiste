<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i>Información del Curso</h5>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Código</dt>
            <dd class="col-sm-8"><code><?= sanitize($curso['codigo']) ?></code></dd>

            <dt class="col-sm-4">Nombre</dt>
            <dd class="col-sm-8"><?= sanitize($curso['nombre']) ?></dd>

            <dt class="col-sm-4">Ciclo</dt>
            <dd class="col-sm-8"><?= sanitize($curso['ciclo']) ?></dd>

            <dt class="col-sm-4">Carrera</dt>
            <dd class="col-sm-8">
                <?php if (!empty($curso['carrera_nombre'])): ?>
                    <span class="badge badge-info"><?= sanitize($curso['carrera_nombre']) ?></span>
                <?php else: ?>
                    <span class="text-muted font-italic">Sin carrera</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Plan de Estudios</dt>
            <dd class="col-sm-8">
                <span class="badge badge-secondary"><?= !empty($curso['plan_nombre']) ? 'Plan ' . sanitize($curso['plan_nombre']) : 'N/A' ?></span>
            </dd>

            <dt class="col-sm-4">Descripción</dt>
            <dd class="col-sm-8"><?= nl2br(sanitize($curso['descripcion'])) ?></dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8">
                <?php if ($curso['estado'] == 1): ?>
                    <span class="badge badge-success">Activo</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactivo</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Total Semanas</dt>
            <dd class="col-sm-8"><?= (int) $totalSemanas ?></dd>

            <dt class="col-sm-4">Fecha de registro</dt>
            <dd class="col-sm-8"><?= formatDate($curso['created_at']) ?></dd>
        </dl>
    </div>
</div>