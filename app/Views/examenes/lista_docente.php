<?php $pageTitle = $pageTitle ?? 'Mis Exámenes'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-file-signature mr-2"></i>Mis Exámenes</h1>
        <a href="#" class="btn btn-primary"
           onclick="AdminModals.openModal('<?= BASE_URL ?>examen/formulario', 'Nuevo Examen', 'lg'); return false;">
            <i class="fas fa-plus"></i> Nuevo Examen
        </a>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-body table-responsive">
            <?php if (empty($examenes)): ?>
                <p class="text-muted text-center py-4 mb-0">Aún no ha creado exámenes.</p>
            <?php else: ?>
                <table class="table table-bordered table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th>Semana</th>
                            <th>Criterio</th>
                            <th>Título</th>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th>Duración</th>
                            <th>Preguntas</th>
                            <th>Rendidos</th>
                            <th style="width:280px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php foreach ($examenes as $e): ?>
                    <?php
                    $esBorrador = (int) $e['estado'] === Examen::ESTADO_BORRADOR;
                    $esInmediato = $e['tipo'] === 'personal' && $e['modo_disponibilidad'] !== Examen::MODO_PROGRAMADO;
                    $ventanaDesde = !empty($e['disponible_desde']) ? strtotime($e['disponible_desde']) : null;
                    $ventanaHasta = !empty($e['disponible_hasta']) ? strtotime($e['disponible_hasta']) : null;
                    $faltaDesde = $e['tipo'] === 'personal' && !$esInmediato && $ventanaDesde === null;
                    if ($e['tipo'] !== 'personal') {
                        $textoActivar = 'Los estudiantes lo verán, pero solo podrán rendirlo cuando usted lance la sesión en vivo.';
                    } elseif ($esInmediato) {
                        $textoActivar = 'Quedará visible y disponible de inmediato, sin fecha de apertura ni de cierre. Cada estudiante tendrá '
                            . (int) $e['duracion_min'] . ' minutos desde que lo abra.';
                    } else {
                        $textoActivar = 'Quedará visible y disponible desde el '
                            . ($ventanaDesde ? date('d/m/Y \a\t H:i', $ventanaDesde) : '(sin fecha de inicio)')
                            . ($ventanaHasta ? ' hasta el ' . date('d/m/Y \a\t H:i', $ventanaHasta) : ' y sin fecha límite') . '.';
                    }
                    ?>
                    <tr class="<?= $esBorrador ? 'table-warning' : '' ?>">
                        <td><?= htmlspecialchars($e['curso_nombre']) ?></td>
                        <td>S<?= (int) $e['numero_semana'] ?></td>
                        <td><?= htmlspecialchars($e['criterio_nombre']) ?></td>
                        <td><strong><?= htmlspecialchars($e['titulo']) ?></strong></td>
                        <td>
                            <?php if ($esBorrador): ?>
                                <span class="badge badge-warning"><i class="fas fa-pen"></i> Borrador</span>
                                <br><small class="text-muted">No visible para el estudiante</small>
                            <?php else: ?>
                                <span class="badge badge-success"><i class="fas fa-eye"></i> Activo</span>
                            <?php endif; ?>
                            <br>
                            <?php if ($e['tipo'] === 'en_vivo'): ?>
                                <small class="text-muted"><i class="fas fa-broadcast-tower"></i> Al lanzar la sesión</small>
                            <?php elseif ($esInmediato): ?>
                                <small class="text-muted"><i class="fas fa-bolt"></i> Inmediato, sin fechas</small>
                            <?php elseif ($faltaDesde): ?>
                                <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Falta fecha de inicio</small>
                            <?php else: ?>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= date('d/m H:i', $ventanaDesde) ?><?= $ventanaHasta ? ' → ' . date('d/m H:i', $ventanaHasta) : ' → ∞' ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($e['tipo'] === 'en_vivo'): ?>
                                <span class="badge badge-danger"><i class="fas fa-broadcast-tower"></i> En vivo</span>
                            <?php else: ?>
                                <span class="badge badge-info"><i class="fas fa-user-clock"></i> Personal</span>
                            <?php endif; ?>
                            <?php if (!empty($e['sesion_activa_fin']) && $e['sesion_activa_fin'] > date('Y-m-d H:i:s')): ?>
                                <br><small class="text-success"><i class="fas fa-circle" style="font-size:.6rem;"></i> Activo hasta <?= date('H:i', strtotime($e['sesion_activa_fin'])) ?></small>
                            <?php elseif ($e['tipo'] === 'en_vivo' && !$esBorrador): ?>
                                <br><a href="#" onclick="ExamenDocente.iniciarEnVivo(<?= (int)$e['id'] ?>, <?= (int)$e['duracion_min'] ?>); return false;"><small>Lanzar ahora</small></a>
                            <?php elseif ($e['tipo'] === 'en_vivo'): ?>
                                <br><small class="text-muted">Active para lanzar</small>
                            <?php endif; ?>
                        </td>
                        <td><?= (int) $e['duracion_min'] ?> min</td>
                        <td><?= (int) $e['total_preguntas'] ?></td>
                        <td><?= (int) $e['intentos_finalizados'] ?></td>
                        <td>
                            <?php if ($esBorrador): ?>
                                <button type="button" class="btn btn-sm btn-success"
                                        onclick="ExamenDocente.cambiarEstado(<?= (int)$e['id'] ?>, 'activar', this)"
                                        data-texto="<?= htmlspecialchars($textoActivar) ?>"
                                        title="<?= $faltaDesde ? 'Defina la fecha de inicio en el examen' : 'Activar' ?>"
                                        <?= (int) $e['total_preguntas'] === 0 ? 'disabled' : '' ?>>
                                    <i class="fas fa-play"></i>
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        onclick="ExamenDocente.cambiarEstado(<?= (int)$e['id'] ?>, 'desactivar', this)"
                                        title="Desactivar (volver a borrador)">
                                    <i class="fas fa-pause"></i>
                                </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>examen/preguntas?examen_id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-primary" title="Preguntas">
                                <i class="fas fa-question-circle"></i>
                            </a>
                            <a href="<?= BASE_URL ?>examen/resultados?examen_id=<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-success" title="Resultados">
                                <i class="fas fa-poll"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-info"
                               onclick="AdminModals.openModal('<?= BASE_URL ?>examen/formulario?id=<?= (int)$e['id'] ?>', 'Editar Examen', 'lg'); return false;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="ExamenDocente.eliminar(<?= (int)$e['id'] ?>)" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<form id="frmEliminarExamen" method="post" action="<?= BASE_URL ?>examen/eliminar" class="d-none">
    <?= csrfField() ?>
    <input type="hidden" name="id" id="eliminar_examen_id">
</form>

<?php require_once __DIR__ . '/../partials/admin_modal.php'; ?>
<?php
$extraScripts = [BASE_URL . 'js/admin_modals.js'];
$inlineScripts = [];
?>
<script>
const ExamenDocente = {
    eliminar(id) {
        Swal.fire({
            title: '¿Eliminar examen?',
            text: 'Se ocultará junto con sus preguntas e intentos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then(r => {
            if (r.isConfirmed) {
                document.getElementById('eliminar_examen_id').value = id;
                document.getElementById('frmEliminarExamen').submit();
            }
        });
    },
    cambiarEstado(id, accion, btn) {
        const esActivar = accion === 'activar';
        Swal.fire({
            title: esActivar ? '¿Activar el examen?' : '¿Desactivar el examen?',
            text: esActivar
                ? (btn && btn.dataset.texto ? btn.dataset.texto : 'Los estudiantes matriculados podrán verlo y rendirlo.')
                : 'Volverá a estado borrador y dejará de ser visible para los estudiantes.',
            icon: esActivar ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: esActivar ? 'Sí, activar' : 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then(r => {
            if (!r.isConfirmed) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('accion', accion);
            fd.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            fetch(BASE_URL + 'examen/cambiarEstado', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 1200, showConfirmButton: false });
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            }).catch(() => Swal.fire('Error', 'No se pudo cambiar el estado.', 'error'));
        });
    },
    iniciarEnVivo(examenId, duracionSugerida) {
        Swal.fire({
            title: 'Lanzar examen en vivo',
            input: 'number',
            inputValue: duracionSugerida,
            inputAttributes: { min: 1, max: 180 },
            text: 'Duración en minutos:',
            showCancelButton: true,
            confirmButtonText: 'Iniciar',
            cancelButtonText: 'Cancelar'
        }).then(r => {
            if (!r.isConfirmed) return;
            const fd = new FormData();
            fd.append('examen_id', examenId);
            fd.append('duracion_min', r.value || duracionSugerida);
            fd.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
            fetch(BASE_URL + 'examen/iniciarEnVivo', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        });
    }
};
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
