<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1>Configuración de Correo</h1>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-envelope-open-text"></i> Servidor SMTP</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                Estos datos se usan para enviar los códigos de recuperación de contraseña por correo.
                Se guardan en la base de datos y el remitente se muestra en los correos enviados.
            </div>

            <form action="<?= BASE_URL ?>admin/guardarConfigCorreo" method="POST">
                <?= csrfField() ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_host">Host SMTP *</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" required
                                   placeholder="Ej: smtp.gmail.com"
                                   value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
                            <small class="form-text text-muted">Servidor de correo saliente.</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="smtp_port">Puerto *</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" required
                                   placeholder="587"
                                   value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="smtp_encryption">Cifrado</label>
                            <select class="form-control" id="smtp_encryption" name="smtp_encryption">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (recomendado)</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Sin cifrado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_user">Usuario SMTP</label>
                            <input type="text" class="form-control" id="smtp_user" name="smtp_user"
                                   placeholder="Ej: cuenta@dominio.com"
                                   value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="smtp_pass">Contraseña SMTP</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="smtp_pass" name="smtp_pass"
                                       placeholder="<?= !empty($settings['smtp_pass']) ? '•••••••• (dejar vacío para no cambiar)' : 'Contraseña de la cuenta SMTP' ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary toggle-password" tabindex="-1" title="Mostrar/ocultar contraseña">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mail_from_email">Correo remitente *</label>
                            <input type="email" class="form-control" id="mail_from_email" name="mail_from_email" required
                                   placeholder="Ej: no-reply@sigametec.com"
                                   value="<?= htmlspecialchars($settings['mail_from_email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="mail_from_name">Nombre remitente</label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name"
                                   placeholder="Ej: Sigametec"
                                   value="<?= htmlspecialchars($settings['mail_from_name'] ?? 'Sigametec') ?>">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Configuración
                    </button>
                    <a href="<?= BASE_URL ?>admin/inicio" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>js/admin_modals.js"></script>
