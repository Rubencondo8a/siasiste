<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Promedio por Estudiante</h1>
        <a href="<?= BASE_URL ?>docente/reportes" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
    <small>Promedio general de cada estudiante en todas las semanas calificadas</small>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Apellidos</th>
                        <th>Nombres</th>
                        <th>Username</th>
                        <th class="text-center">Calificaciones</th>
                        <th class="text-center">Promedio</th>
                        <th class="text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($reportData as $id => $data): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($data['apellidos']) ?></td>
                                <td><?= htmlspecialchars($data['nombres']) ?></td>
                                <td><?= htmlspecialchars($data['username']) ?></td>
                                <td class="text-center"><?= $data['total_calificaciones'] ?></td>
                                <td class="text-center">
                                    <?php
                                    $prom = $data['promedio'];
                                    $color = $prom >= 10 ? 'success' : ($prom > 0 ? 'danger' : 'secondary');
                                    ?>
                                    <span class="badge badge-<?= $color ?> badge-lg"><?= number_format($prom, 0) ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if ($prom >= 10): ?>
                                        <span class="badge badge-success">Aprobado</span>
                                    <?php elseif ($prom > 0): ?>
                                        <span class="badge badge-danger">Reprobado</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Sin calificaciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
