<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $user ? 'Editar Usuario' : 'Nuevo Usuario' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (!$user): ?>
            <div class="alert alert-info">
                <i class="fas fa-key mr-2"></i>
                <strong>Contraseña temporal: <span style="font-size:1.2rem;letter-spacing:2px;">12345678</span></strong>
                <br>El usuario nuevo tendrá <strong>30 minutos</strong> para cambiarla al primer ingreso.
            </div>
            <?php endif; ?>
            <form action="<?= BASE_URL ?>admin/guardarUsuario" method="POST" enctype="multipart/form-data" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($user): ?>
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center mb-3">
                            <img id="fotoPreview" src="<?= profilePhoto($user['foto'] ?? null) ?>" alt="Foto"
                                 class="img-circle elevation-2" style="width:120px;height:120px;object-fit:cover;">
                            <div class="mt-2">
                                <label for="foto" class="btn btn-sm btn-outline-secondary" style="cursor:pointer;">
                                    <i class="fas fa-camera"></i> Seleccionar foto
                                </label>
                                <input type="file" id="foto" name="foto" accept="image/*" style="display:none;"
                                       onchange="document.getElementById('fotoPreview').src=window.URL.createObjectURL(this.files[0])">
                            </div>
                            <small class="text-muted">JPG, PNG o WebP. Máx 2MB.</small>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellidos">Apellidos *</label>
                                    <input type="text" class="form-control" id="apellidos" name="apellidos" required
                                           placeholder="Ej: Pérez Gómez"
                                           value="<?= htmlspecialchars($user['apellidos'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombres">Nombres *</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres" required
                                           placeholder="Ej: María Elena"
                                           value="<?= htmlspecialchars($user['nombres'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" required
                                           placeholder="Ej: mperez"
                                           value="<?= htmlspecialchars($user['username'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           placeholder="Ej: mperez@instituto.edu"
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rol">Rol *</label>
                                    <select class="form-control" id="rol" name="rol" required>
                                        <option value="docente" <?= ($user['rol'] ?? '') === 'docente' ? 'selected' : '' ?>>Docente</option>
                                        <option value="admin" <?= ($user['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="estado">Estado</label>
                                    <select class="form-control" id="estado" name="estado">
                                        <option value="1" <?= ($user['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                        <option value="0" <?= ($user['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password"><?= $user ? 'Contraseña (dejar vacío para no cambiar)' : 'Contraseña' ?></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password"
                                               <?= $user ? '' : 'disabled placeholder="La contraseña temporal será 12345678"' ?>>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password" tabindex="-1" title="Mostrar/ocultar contraseña">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted">
                                        <?= $user ? 'Si la cambias a otro usuario, se asignará la temporal 12345678 con 30 min de plazo.' : 'El usuario nuevo tendrá la contraseña temporal 12345678 y 30 minutos para cambiarla.' ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password_confirm">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                               <?= $user ? '' : 'disabled placeholder="No requerida"' ?>>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirm" tabindex="-1" title="Mostrar/ocultar contraseña">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/usuarios" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

<?php if (empty($isAjax)): ?>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>