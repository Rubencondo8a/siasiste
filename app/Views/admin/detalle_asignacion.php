<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i>Curso</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre</dt>
                    <dd class="col-sm-7"><?= sanitize($asignacion['nombre']) ?></dd>

                    <dt class="col-sm-5">Código</dt>
                    <dd class="col-sm-7"><code><?= sanitize($asignacion['codigo']) ?></code></dd>

                    <dt class="col-sm-5">Ciclo</dt>
                    <dd class="col-sm-7"><?= sanitize($asignacion['ciclo']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-calendar-alt mr-2"></i>Periodo</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Nombre</dt>
                    <dd class="col-sm-7"><?= sanitize($asignacion['periodo_nombre']) ?></dd>

                    <dt class="col-sm-5">Inicio</dt>
                    <dd class="col-sm-7"><?= formatDate($asignacion['periodo_inicio']) ?></dd>

                    <dt class="col-sm-5">Fin</dt>
                    <dd class="col-sm-7"><?= formatDate($asignacion['periodo_fin']) ?></dd>

                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">
                        <?php if ($asignacion['periodo_estado'] === 'activo'): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php elseif ($asignacion['periodo_estado'] === 'inactivo'): ?>
                            <span class="badge badge-secondary">Inactivo</span>
                        <?php else: ?>
                            <span class="badge badge-info"><?= sanitize($asignacion['periodo_estado']) ?></span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chalkboard-teacher mr-2"></i>Docente</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($asignacion['docente_apellidos'])): ?>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Apellidos</dt>
                        <dd class="col-sm-7"><?= sanitize($asignacion['docente_apellidos']) ?></dd>

                        <dt class="col-sm-5">Nombres</dt>
                        <dd class="col-sm-7"><?= sanitize($asignacion['docente_nombres']) ?></dd>

                        <dt class="col-sm-5">Email</dt>
                        <dd class="col-sm-7"><?= sanitize($asignacion['docente_email']) ?></dd>
                    </dl>
                <?php else: ?>
                    <div class="text-center text-muted py-2">
                        <i class="fas fa-user-slash fa-lg mb-2"></i>
                        <p class="mb-0 font-italic">Sin asignar</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-users mr-2"></i>Estudiantes Inscritos
            <span class="badge badge-primary ml-2"><?= count($estudiantes) ?></span>
        </h5>
    </div>
    <div class="card-body">
        <?php if (!empty($estudiantes)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0 nodt">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Apellidos</th>
                            <th>Nombres</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estudiantes as $i => $est): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= sanitize($est['apellidos']) ?></td>
                                <td><?= sanitize($est['nombres']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">No hay estudiantes inscritos</p>
            </div>
        <?php endif; ?>
    </div>
</div>
