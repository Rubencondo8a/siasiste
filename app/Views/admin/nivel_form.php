<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $nivel ? 'Editar Nivel' : 'Nuevo Nivel de Magia' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="<?= BASE_URL ?>admin/guardarNivel" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
                <?php if ($nivel): ?>
                    <input type="hidden" name="id" value="<?= $nivel['id'] ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="nombre">Nombre del Nivel *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required
                                   placeholder="Ej: Aprendiz"
                                   value="<?= htmlspecialchars($nivel['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="clave">Clave (identificador) *</label>
                            <input type="text" class="form-control" id="clave" name="clave" required
                                   placeholder="Ej: aprendiz" style="text-transform:lowercase;"
                                   value="<?= htmlspecialchars($nivel['clave'] ?? '') ?>">
                            <small class="form-text text-muted">Solo minúsculas, números y guion bajo.</small>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="rango">Rango (orden) *</label>
                            <input type="number" class="form-control" id="rango" name="rango" min="1" required
                                   value="<?= $nivel['rango'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="min_nota">Nota mínima *</label>
                            <input type="number" class="form-control" id="min_nota" name="min_nota" min="0" max="20" step="0.01" required
                                   value="<?= $nivel['min_nota'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="max_nota">Nota máxima *</label>
                            <input type="number" class="form-control" id="max_nota" name="max_nota" min="0.01" max="20" step="0.01" required
                                   value="<?= $nivel['max_nota'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="mana">Maná *</label>
                            <input type="number" class="form-control" id="mana" name="mana" min="0" required
                                   value="<?= $nivel['mana'] ?? '' ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="icono">Icono (FontAwesome)</label>
                            <input type="text" class="form-control" id="icono" name="icono"
                                   placeholder="Ej: fas fa-book"
                                   value="<?= htmlspecialchars($nivel['icono'] ?? 'fas fa-star') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="clase">Clase de badge (Bootstrap)</label>
                            <input type="text" class="form-control" id="clase" name="clase"
                                   placeholder="Ej: badge-info"
                                   value="<?= htmlspecialchars($nivel['clase'] ?? 'badge-secondary') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="avatar">Avatar (ruta)</label>
                            <input type="text" class="form-control" id="avatar" name="avatar"
                                   placeholder="Ej: assets/img/avatar-aprendiz.svg"
                                   value="<?= htmlspecialchars($nivel['avatar'] ?? 'assets/img/avatar-iniciado.svg') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="costo_donacion">Costo de donación (fracción)</label>
                            <input type="number" class="form-control" id="costo_donacion" name="costo_donacion"
                                   min="0" step="0.0000001"
                                   placeholder="Ej: 0.015625 (1/64). Vacío = no puede donar"
                                   value="<?= $nivel['costo_donacion'] !== null && $nivel['costo_donacion'] !== '' ? rtrim(rtrim(number_format((float) $nivel['costo_donacion'], 7, '.', ''), '0'), '.') : '' ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="color_mana">Color de maná</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" style="padding:2px 6px;">
                                        <input type="color" id="color_mana_picker" value="<?= htmlspecialchars($nivel['color_mana'] ?? '#ffc107') ?>" style="width:28px;height:28px;border:none;padding:0;background:none;" oninput="document.getElementById('color_mana').value=this.value;">
                                    </span>
                                </div>
                                <input type="text" class="form-control" id="color_mana" name="color_mana"
                                       placeholder="Ej: #ffc107 (Merlín: #9b59b6)"
                                       value="<?= htmlspecialchars($nivel['color_mana'] ?? '#ffc107') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="1" <?= ($nivel['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= ($nivel['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mensaje">Mensaje del nivel</label>
                    <input type="text" class="form-control" id="mensaje" name="mensaje"
                           placeholder="Ej: ¡Vas mejorando, sigue practicando!"
                           value="<?= htmlspecialchars($nivel['mensaje'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="poderes">Poderes del nivel</label>
                    <textarea class="form-control" id="poderes" name="poderes" rows="5"
                              placeholder="Una línea por poder con el formato:&#10;icono|texto&#10;Ej:&#10;fas fa-bolt|Tienes 4 de maná&#10;fas fa-paper-plane|Envías maná a Iniciados"><?= htmlspecialchars($poderesTexto) ?></textarea>
                    <small class="form-text text-muted">Cada línea: icono de FontAwesome seguido de | y la descripción.</small>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <?php if (!empty($isAjax)): ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>admin/nivelesMago" class="btn btn-secondary">
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
