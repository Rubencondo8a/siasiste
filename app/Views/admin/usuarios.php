<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Gestión de Usuarios</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users-cog"></i> Lista de Usuarios</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-info" onclick="AdminModals.openForm('admin', 'formularioUsuario', null, 'Nuevo Usuario', 'xl')">
                    <i class="fas fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Foto</th>
                        <th>Apellidos</th>
                        <th>Nombres</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $user): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <img src="<?= profilePhoto($user['foto'] ?? null) ?>" alt="Foto"
                                     class="img-circle" style="width:35px;height:35px;object-fit:cover;">
                            </td>
                            <td><?= htmlspecialchars($user['apellidos']) ?></td>
                            <td><?= htmlspecialchars($user['nombres']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <?php if ($user['rol'] === 'admin'): ?>
                                    <span class="badge badge-danger">Administrador</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Docente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['estado']): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Inactivo</span>
                                <?php endif; ?>
                                <?php if ((int) ($user['login_attempts'] ?? 0) >= 5): ?>
                                    <span class="badge badge-danger" title="<?= (int) $user['login_attempts'] ?> intentos fallidos - Bloqueado">
                                        <i class="fas fa-ban"></i> Bloqueado
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($user['debe_cambiar_password'])): ?>
                                    <span class="badge badge-warning" title="Debe cambiar su contraseña en el próximo ingreso">
                                        <i class="fas fa-key"></i> Cambio pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-info btn-sm" title="Ver Detalle" onclick="AdminModals.openDetail('admin', 'detalleUsuario', <?= $user['id'] ?>, 'Detalle Usuario')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-warning btn-sm" title="Editar" onclick="AdminModals.openForm('admin', 'formularioUsuario', <?= $user['id'] ?>, 'Editar Usuario', 'xl')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-secondary btn-sm" title="Restablecer contraseña" onclick="AdminModals.resetPassword('<?= BASE_URL ?>admin/restablecerPassword?id=<?= $user['id'] ?>', '<?= htmlspecialchars($user['nombres'] . ' ' . $user['apellidos'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php if ($user['estado']): ?>
                                    <button class="btn btn-warning btn-sm" title="Bloquear cuenta" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/toggleEstadoUsuario?id=<?= $user['id'] ?>', '¿Está seguro de bloquear la cuenta de <?= htmlspecialchars(addslashes($user['nombres'] . ' ' . $user['apellidos']), ENT_QUOTES) ?>? No podrá iniciar sesión hasta que la desbloquee.')">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm" title="Desbloquear cuenta" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/toggleEstadoUsuario?id=<?= $user['id'] ?>', '¿Está seguro de desbloquear la cuenta de <?= htmlspecialchars(addslashes($user['nombres'] . ' ' . $user['apellidos']), ENT_QUOTES) ?>?')">
                                        <i class="fas fa-lock-open"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ((int) ($user['login_attempts'] ?? 0) >= 5): ?>
                                    <button class="btn btn-danger btn-sm" title="Desbloquear intentos fallidos (<?= (int) $user['login_attempts'] ?> intentos)" onclick="AdminModals.confirmAction('<?= BASE_URL ?>admin/desbloquearIntentos?id=<?= $user['id'] ?>', 'El usuario tiene <?= (int) $user['login_attempts'] ?> intentos fallidos. ¿Desea limpiar los intentos para que pueda iniciar sesión?')">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm" title="Eliminar" onclick="AdminModals.confirmDelete('<?= BASE_URL ?>admin/eliminarUsuario?id=<?= $user['id'] ?>', '¿Está seguro de eliminar este usuario?')">
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

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = $extraScripts ?? [];
$extraScripts[] = BASE_URL . 'js/admin_modals.js';
?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>