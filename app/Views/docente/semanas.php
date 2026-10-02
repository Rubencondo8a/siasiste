<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1>Mis Cursos</h1>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Curso</th>
                        <th>Código</th>
                        <th>Ciclo</th>
                        <th>Periodo</th>
                        <th>Semanas</th>
                        <th>Estudiantes</th>
                        <th>Evaluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso): ?>
                        <tr>
                            <td><?= $curso['id'] ?></td>
                            <td><?= htmlspecialchars($curso['nombre']) ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($curso['codigo']) ?></span></td>
                            <td><?= htmlspecialchars($curso['ciclo']) ?></td>
                            <td><?= htmlspecialchars($curso['periodo_nombre']) ?></td>
                            <td><span class="badge badge-secondary"><?= $curso['total_semanas'] ?></span></td>
                            <td><span class="badge badge-primary"><?= $curso['total_estudiantes'] ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $curso['id'] ?>" class="btn btn-sm btn-info" title="Ver Semanas">
                                    <i class="fas fa-calendar-week"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
