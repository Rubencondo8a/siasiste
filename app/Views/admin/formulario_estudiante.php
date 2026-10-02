<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $student ? 'Editar Estudiante' : 'Nuevo Estudiante' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarEstudiante" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($student): ?>
                    <input type="hidden" name="id" value="<?= $student['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dni">DNI *</label>
                            <input type="text" class="form-control" id="dni" name="dni" required
                                   placeholder="Ej: 12345678"
                                   value="<?= htmlspecialchars($student['dni'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="username">Usuario <?= $student ? '' : '*' ?></label>
                            <input type="text" class="form-control" id="username" name="username"
                                   placeholder="Ej: mgarcia"
                                   value="<?= htmlspecialchars($student['username'] ?? '') ?>"
                                   <?= $student ? '' : 'required' ?>>
                            <small class="form-text text-muted">Nombre de usuario para iniciar sesión</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="1" <?= ($student['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($student['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="apellidos">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" required
                                   placeholder="Ej: García López"
                                   value="<?= htmlspecialchars($student['apellidos'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombres">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required
                                   placeholder="Ej: Juan Carlos"
                                   value="<?= htmlspecialchars($student['nombres'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                   placeholder="Ej: jgarcia@instituto.edu"
                                   value="<?= htmlspecialchars($student['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="carrera_id">Carrera</label>
                            <select class="form-control" id="carrera_id" name="carrera_id">
                                <option value="">-- Sin carrera --</option>
                                <?php if (!empty($carreras)): ?>
                                    <?php foreach ($carreras as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= ($student['carrera_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password"><?= $student ? 'Contraseña (dejar vacío para no cambiar)' : 'Contraseña *' ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                       <?= $student ? '' : 'required' ?>>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password" tabindex="-1" title="Mostrar/ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password_confirm"><?= $student ? 'Confirmar Contraseña' : 'Confirmar Contraseña *' ?></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                       <?= $student ? '' : 'required' ?>>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_confirm" tabindex="-1" title="Mostrar/ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/estudiantes" class="btn btn-secondary">
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