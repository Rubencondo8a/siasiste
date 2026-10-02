<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-tie mr-2"></i>Información del Usuario</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <img src="<?= profilePhoto($user['foto'] ?? null) ?>" alt="Foto"
                         class="img-circle elevation-2" style="width:100px;height:100px;object-fit:cover;">
                </div>
                <dl class="row mb-0">
                    <dt class="col-sm-5">Apellidos</dt>
                    <dd class="col-sm-7"><?= sanitize($user['apellidos']) ?></dd>

                    <dt class="col-sm-5">Nombres</dt>
                    <dd class="col-sm-7"><?= sanitize($user['nombres']) ?></dd>

                    <dt class="col-sm-5">Username</dt>
                    <dd class="col-sm-7"><?= sanitize($user['username']) ?></dd>

                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7"><?= sanitize($user['email']) ?></dd>

                    <dt class="col-sm-5">Rol</dt>
                    <dd class="col-sm-7">
                        <?php if ($user['rol'] === 'admin'): ?>
                            <span class="badge badge-danger">Administrador</span>
                        <?php else: ?>
                            <span class="badge badge-info">Docente</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">
                        <?php if ($user['estado'] == 1): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Cambio de contraseña</dt>
                    <dd class="col-sm-7">
                        <?php if (!empty($user['debe_cambiar_password'])): ?>
                            <span class="badge badge-warning"><i class="fas fa-key"></i> Pendiente en próximo ingreso</span>
                        <?php else: ?>
                            <span class="badge badge-success">Actualizada</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Fecha de registro</dt>
                    <dd class="col-sm-7"><?= formatDate($user['created_at']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <?php if ($user['rol'] === 'docente'): ?>
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book mr-2"></i>Cursos Asignados</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cursos)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 nodt">
                            <thead class="thead-light">
                                <tr>
                                    <th>Curso</th>
                                    <th>Código</th>
                                    <th>Periodo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><?= sanitize($curso['nombre']) ?></td>
                                        <td><code><?= sanitize($curso['codigo']) ?></code></td>
                                        <td><?= sanitize($curso['periodo_nombre']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Sin cursos asignados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>