<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-graduation-cap mr-2"></i>Información de la Carrera</h5>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Código</dt>
            <dd class="col-sm-8"><code><?= sanitize($carrera['codigo']) ?></code></dd>

            <dt class="col-sm-4">Nombre</dt>
            <dd class="col-sm-8"><?= sanitize($carrera['nombre']) ?></dd>

            <dt class="col-sm-4">Planes de Estudios</dt>
            <dd class="col-sm-8">
                <?php if (empty($planes)): ?>
                    <span class="text-muted font-italic">Sin planes</span>
                <?php else: ?>
                    <?php foreach ($planes as $plan): ?>
                        <span class="badge badge-secondary mr-1">Plan <?= sanitize($plan['nombre']) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Total de Ciclos</dt>
            <dd class="col-sm-8"><?= (int) ($carrera['total_ciclos'] ?? 6) ?> ciclos</dd>

            <dt class="col-sm-4">Descripción</dt>
            <dd class="col-sm-8"><?= nl2br(sanitize($carrera['descripcion'])) ?></dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8">
                <?php if ($carrera['estado'] == 1): ?>
                    <span class="badge badge-success">Activo</span>
                <?php else: ?>
                    <span class="badge badge-secondary">Inactivo</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Fecha de registro</dt>
            <dd class="col-sm-8"><?= formatDate($carrera['created_at']) ?></dd>
        </dl>
    </div>
</div>