<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de Estudiantes</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-graduate"></i> Lista de Estudiantes</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importModal">
                    <i class="fas fa-file-upload"></i> Importar Excel
                </button>
                <button type="button" class="btn btn-success" onclick="AdminModals.openForm('admin', 'formularioEstudiante', null, 'Nuevo Estudiante', 'xl')">
                    <i class="fas fa-plus"></i> Nuevo Estudiante
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>DNI</th>
                        <th>Usuario</th>
                        <th>Apellidos</th>
                        <th>Nombres</th>
                        <th>Carrera</th>
                        <th>Ciclo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $i => $student): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><code><?= htmlspecialchars($student['dni'] ?? '') ?></code></td>
                            <td><code style="color:#007bff;font-weight:600" title="Usuario de acceso"><?= htmlspecialchars($student['username'] ?? '') ?></code></td>
                            <td><?= htmlspecialchars($student['apellidos']) ?></td>
                            <td><?= htmlspecialchars($student['nombres']) ?></td>
                            <td><span class="badge badge-secondary" title="<?= htmlspecialchars($student['carrera_nombre'] ?? 'Sin carrera') ?>" style="cursor:help"><?= htmlspecialchars($student['carrera_codigo'] ?? 'Sin carrera') ?></span></td>
                            <td class="text-center"><?= $student['ciclo'] ? '<span class="badge badge-info">' . htmlspecialchars($student['ciclo']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?php if ($student['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                                <?php if ((int) ($student['login_attempts'] ?? 0) >= 5): ?>
                                    <span class="badge badge-danger" title="<?= (int) $student['login_attempts'] ?> intentos fallidos - Bloqueado">
                                        <i class="fas fa-ban"></i> Bloqueado
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($student['debe_cambiar_password'])): ?>
                                    <span class="badge badge-warning" title="Debe cambiar su contraseña en el próximo ingreso">
                                        <i class="fas fa-key"></i> Cambio pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-success btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleEstudiante', <?= $student['id'] ?>, 'Detalle Estudiante')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioEstudiante', <?= $student['id'] ?>, 'Editar Estudiante', 'xl')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" title="Restablecer contraseña" onclick="AdminModals.resetPassword('<?= BASE_URL ?>admin/restablecerPassword?id=<?= $student['id'] ?>&origen=estudiantes', '<?= htmlspecialchars($student['nombres'] . ' ' . $student['apellidos'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($student['estado']): ?>
                                    <button class="btn btn-warning btn-sm" title="Bloquear cuenta" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/toggleEstadoUsuario?id=<?= $student['id'] ?>&origen=estudiantes', '¿Está seguro de bloquear la cuenta de <?= htmlspecialchars(addslashes($student['nombres'] . ' ' . $student['apellidos']), ENT_QUOTES) ?>? No podrá iniciar sesión hasta que la desbloquee.')">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm" title="Desbloquear cuenta" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/toggleEstadoUsuario?id=<?= $student['id'] ?>&origen=estudiantes', '¿Está seguro de desbloquear la cuenta de <?= htmlspecialchars(addslashes($student['nombres'] . ' ' . $student['apellidos']), ENT_QUOTES) ?>?')">
                                        <i class="fas fa-lock-open"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ((int) ($student['login_attempts'] ?? 0) >= 5): ?>
                                    <button class="btn btn-danger btn-sm" title="Desbloquear intentos fallidos (<?= (int) $student['login_attempts'] ?> intentos)" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/desbloquearIntentos?id=<?= $student['id'] ?>&origen=estudiantes', 'El estudiante tiene <?= (int) $student['login_attempts'] ?> intentos fallidos. ¿Desea limpiar los intentos para que pueda iniciar sesión?')">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-info btn-sm" title="Registrar rostro" onclick="AdminModals.openModal('<?= BASE_URL ?>admin/formularioRostro?estudiante_id=<?= $student['id'] ?>', 'Registrar Rostro - <?= htmlspecialchars($student['nombres'] . ' ' . $student['apellidos'], ENT_QUOTES) ?>', 'lg')">
                                    <i class="fas fa-user-circle"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarEstudiante?id=<?= $student['id'] ?>', '¿Está seguro de eliminar este estudiante?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="importForm" action="<?= BASE_URL ?>admin/importarEstudiantes" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="importModalLabel"><i class="fas fa-file-upload"></i> Importar Estudiantes</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <a href="<?= BASE_URL ?>admin/descargarPlantillaEstudiantes" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-download"></i> Descargar plantilla .xlsx
                        </a>
                    </div>
                    <div class="form-group">
                        <label>Archivo Excel (.xlsx o .xls) *</label>
                        <input type="file" name="archivo" class="form-control-file" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">Columnas: DNI | Apellidos | Nombres | Email (opcional: Carrera por código). La primera fila debe ser encabezado.</small>
                    </div>
                    <div class="form-group">
                        <label>Carrera (opcional para todos)</label>
                        <select name="carrera_id" class="form-control">
                            <option value="">-- Sin carrera --</option>
                            <?php if (!empty($carreras)): ?>
                                <?php foreach ($carreras as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <small class="form-text text-muted">Si el archivo trae la columna "Carrera (código)", esa tiene prioridad.</small>
                    </div>
                    <div id="importResult" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info" id="importBtn">
                        <i class="fas fa-file-upload"></i> Importar
                    </button>
                    <button type="button" class="btn btn-success" id="importOkBtn" style="display:none;">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>js/admin_modals.js"></script>
<script>
var importResultHtml = null;

document.getElementById('importOkBtn')?.addEventListener('click', function() {
    $('#importModal').modal('hide');
    location.reload();
});

$('#importModal').on('hidden.bs.modal', function() {
    var form = document.getElementById('importForm');
    if (form) form.reset();
    var result = document.getElementById('importResult');
    if (result) result.innerHTML = '';
    var okBtn = document.getElementById('importOkBtn');
    var btn = document.getElementById('importBtn');
    if (okBtn) okBtn.style.display = 'none';
    if (btn) { btn.style.display = ''; btn.disabled = false; }
});

document.getElementById('importForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('importBtn');
    var okBtn = document.getElementById('importOkBtn');
    var result = document.getElementById('importResult');
    result.innerHTML = '<div class="text-center py-2"><i class="fas fa-spinner fa-spin text-info"></i> Procesando archivo...</div>';
    btn.disabled = true;
    okBtn.style.display = 'none';
    var data = new FormData(form);
    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(function(r) { return r.json().catch(function() { return { success: false, message: 'Respuesta inválida del servidor.' }; }); })
    .then(function(res) {
        if (res.success) {
            var html = '<div class="alert alert-success mb-0">' +
                '<i class="fas fa-check-circle"></i> ' + (res.message || 'Importación completada.') + '</div>';
            if (res.errores && res.errores.length) {
                html += '<div class="alert alert-warning mt-2 mb-0" style="max-height:180px;overflow:auto;"><strong>Detalle:</strong><ul class="mb-0">';
                res.errores.forEach(function(er) { html += '<li>' + er + '</li>'; });
                html += '</ul></div>';
            }
            result.innerHTML = html;
            importResultHtml = html;
            okBtn.style.display = '';
            btn.style.display = 'none';
            btn.disabled = false;
        } else {
            result.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> ' + (res.message || 'Error al importar.') + '</div>';
            btn.disabled = false;
        }
    })
    .catch(function() {
        result.innerHTML = '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle"></i> Error de conexión al importar.</div>';
        btn.disabled = false;
    });
});
</script>