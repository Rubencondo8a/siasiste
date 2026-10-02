<?php $pageTitle = $pageTitle ?? 'Mis Exámenes'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><i class="fas fa-file-signature mr-2"></i>Mis Exámenes</h1>
</section>

<section class="content">
    <?php if (empty($examenes)): ?>
        <div class="card"><div class="card-body text-center text-muted py-5">No tiene exámenes asignados.</div></div>
    <?php else: ?>
    <div class="row">
        <?php foreach ($examenes as $e): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card <?= $e['disponible_ahora'] && !$e['agoto_intentos'] ? 'border-primary' : '' ?> h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge <?= $e['tipo'] === 'en_vivo' ? 'badge-danger' : 'badge-info' ?>">
                            <i class="fas <?= $e['tipo'] === 'en_vivo' ? 'fa-broadcast-tower' : 'fa-user-clock' ?>"></i>
                            <?= $e['tipo'] === 'en_vivo' ? 'En vivo' : 'Personal' ?>
                        </span>
                        <?php if ($e['mi_nota'] !== null): ?>
                            <span class="badge badge-success">Nota: <?= number_format((float)$e['mi_nota'], 1) ?>/20</span>
                        <?php endif; ?>
                    </div>

                    <h5 class="mb-1"><?= htmlspecialchars($e['titulo']) ?></h5>
                    <small class="text-muted mb-1">
                        <i class="fas fa-book"></i> <?= htmlspecialchars($e['curso_nombre']) ?>
                        · S<?= (int)$e['numero_semana'] ?>
                    </small>
                    <small class="text-muted mb-2">
                        Criterio: <?= htmlspecialchars($e['criterio_nombre']) ?> ·
                        <?= (int)$e['total_preguntas'] ?> preguntas ·
                        <?= (int)$e['duracion_min'] ?> min
                    </small>

                    <?php if ($e['descripcion']): ?>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars(mb_strimwidth($e['descripcion'], 0, 120, '…'))) ?></p>
                    <?php endif; ?>

                    <div class="mt-auto">
                        <?php if ($e['tipo'] === 'en_vivo'): ?>
                            <?php if ($e['disponible_ahora']): ?>
                                <div class="alert alert-danger py-1 px-2 small mb-2">
                                    <i class="fas fa-circle" style="font-size:.55rem;"></i> ¡Está activo! Cierra:
                                    <?= date('H:i', strtotime($e['sesion_activa_fin'])) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-secondary small mb-2"><i class="fas fa-hourglass-half"></i> El docente aún no lo ha lanzado.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (!$e['disponible_ahora']): ?>
                                <p class="text-secondary small mb-2"><i class="fas fa-lock"></i> Fuera de la ventana de disponibilidad.</p>
                            <?php endif; ?>
                            <?php if (!empty($e['disponible_hasta'])): ?>
                                <small class="text-muted d-block">Hasta: <?= date('d/m/Y H:i', strtotime($e['disponible_hasta'])) ?></small>
                            <?php endif; ?>
                        <?php endif; ?>

                        <small class="text-muted d-block mb-2">
                            Intentos usados: <?= (int)$e['mis_intentos'] ?>/<?= (int)($e['limite_intentos'] ?? $e['intentos_permitidos']) ?>
                            <?php if (!empty($e['intentos_extra'])): ?>
                                <span class="badge badge-info ml-1" title="El docente te otorgó intentos adicionales">
                                    <i class="fas fa-gift"></i> +<?= (int)$e['intentos_extra'] ?>
                                </span>
                            <?php endif; ?>
                        </small>

                        <?php if ((int)$e['total_preguntas'] === 0): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Sin preguntas a&uacute;n</button>
                        <?php elseif (!$e['disponible_ahora']): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>No disponible</button>
                        <?php elseif (!empty($e['tiene_intento_en_curso'])): ?>
                            <a href="<?= BASE_URL ?>examen/tomar?examen_id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i> Continuar
                            </a>
                        <?php elseif (!$e['agoto_intentos']): ?>
                            <a href="<?= BASE_URL ?>examen/tomar?examen_id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-play"></i> <?= $e['mi_nota'] !== null ? 'Reintentar' : 'Iniciar' ?>
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Rendido</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
