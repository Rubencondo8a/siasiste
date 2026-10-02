<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Información del Periodo</h5>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Nombre</dt>
            <dd class="col-sm-8"><?= sanitize($periodo['nombre']) ?></dd>

            <dt class="col-sm-4">Fecha Inicio</dt>
            <dd class="col-sm-8"><?= formatDate($periodo['fecha_inicio']) ?></dd>

            <dt class="col-sm-4">Fecha Fin</dt>
            <dd class="col-sm-8"><?= formatDate($periodo['fecha_fin']) ?></dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8">
                <?php if ($periodo['estado'] == 1): ?>
                    <span class="badge badge-success">Activo</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactivo</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Fecha de registro</dt>
            <dd class="col-sm-8"><?= formatDate($periodo['created_at']) ?></dd>
        </dl>
    </div>
</div>