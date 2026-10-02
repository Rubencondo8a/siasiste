<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-graduate mr-2"></i>Información Personal</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">DNI</dt>
                    <dd class="col-sm-7"><code><?= sanitize($student['dni'] ?? '') ?></code></dd>

                    <dt class="col-sm-5">Apellidos</dt>
                    <dd class="col-sm-7"><?= sanitize($student['apellidos']) ?></dd>

                    <dt class="col-sm-5">Nombres</dt>
                    <dd class="col-sm-7"><?= sanitize($student['nombres']) ?></dd>

                    <dt class="col-sm-5">Email</dt>
                    <dd class="col-sm-7"><?= sanitize($student['email']) ?></dd>

                    <dt class="col-sm-5">Unidad Didáctica</dt>
                    <dd class="col-sm-7" style="overflow-wrap:break-word;">
                        <?php if (!empty($student['carrera_nombre'])): ?>
                            <span class="badge badge-info" style="white-space:normal;"><?= sanitize($student['carrera_nombre']) ?></span>
                        <?php else: ?>
                            <span class="text-muted font-italic">Sin unidad didáctica</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Estado</dt>
                    <dd class="col-sm-7">
                        <?php if ($student['estado'] == 1): ?>
                            <span class="badge badge-success">Activo</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Inactivo</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-5">Fecha de registro</dt>
                    <dd class="col-sm-7"><?= formatDate($student['created_at']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book-open mr-2"></i>Cursos Inscritos</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($cursos)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0 nodt">
                            <thead class="thead-light">
                                <tr>
                                    <th>Curso</th>
                                    <th>Código</th>
                                    <th>Fecha de Asignación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cursos as $curso): ?>
                                    <tr>
                                        <td><?= sanitize($curso['nombre']) ?></td>
                                        <td><code><?= sanitize($curso['codigo']) ?></code></td>
                                        <td><?= formatDate($curso['fecha_asignacion']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No está inscrito en ningún curso</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>