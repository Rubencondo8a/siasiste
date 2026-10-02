<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Agregar Estudiantes - <?= htmlspecialchars($curso['nombre']) ?> (<?= htmlspecialchars($curso['codigo']) ?>)</h1>
        <a href="<?= BASE_URL ?>docente/estudiantesCurso?curso_id=<?= $curso['id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</section>

<section class="content">
    <?php if (!empty($disponibles)): ?>
        <form action="<?= BASE_URL ?>docente/guardarEstudiantes" method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-plus"></i> Estudiantes Disponibles (<?= count($disponibles) ?>)</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" id="btnSelectAll">
                            <i class="fas fa-check-double"></i> Seleccionar Todos
                        </button>
                        <button type="button" class="btn btn-warning btn-sm" id="btnDeselectAll">
                            <i class="fas fa-times"></i> Deseleccionar Todos
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 40px;">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th>Apellidos</th>
                                <th>Nombres</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Carrera</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($disponibles as $est): ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="estudiantes[]" value="<?= $est['id'] ?>" class="student-check">
                                    </td>
                                    <td><?= htmlspecialchars($est['apellidos']) ?></td>
                                    <td><?= htmlspecialchars($est['nombres']) ?></td>
                                    <td><?= htmlspecialchars($est['username']) ?></td>
                                    <td><?= htmlspecialchars($est['email']) ?></td>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($est['carrera_nombre'] ?? 'N/A') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Asignar Seleccionados
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="callout callout-info">
            <h5>No hay estudiantes disponibles</h5>
            <p>Todos los estudiantes activos ya están asignados a este curso, o no hay estudiantes registrados en el sistema.</p>
        </div>
    <?php endif; ?>
</section>

<script>
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = this.checked);
});
document.getElementById('btnSelectAll')?.addEventListener('click', function() {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = true);
    document.getElementById('checkAll').checked = true;
});
document.getElementById('btnDeselectAll')?.addEventListener('click', function() {
    document.querySelectorAll('.student-check').forEach(cb => cb.checked = false);
    document.getElementById('checkAll').checked = false;
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
