<?php $pageTitle = $pageTitle ?? 'Resultados'; ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php
$ventanaCerrada = !empty($examen['disponible_hasta'])
    && strtotime($examen['disponible_hasta']) < time();
?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1><i class="fas fa-poll mr-2"></i><?= htmlspecialchars($examen['titulo']) ?></h1>
        <a href="<?= BASE_URL ?>examen/preguntas?examen_id=<?= (int)$examen['id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>
</section>

<section class="content">

    <?php if ($ventanaCerrada): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            La ventana de disponibilidad de este examen ya se cerró
            (<?= date('d/m/Y H:i', strtotime($examen['disponible_hasta'])) ?>).
            Si otorga intentos adicionales, el alumno no podrá rendirlos
            hasta que vuelva a abrir la ventana desde
            <a href="<?= BASE_URL ?>examen/preguntas?examen_id=<?= (int)$examen['id'] ?>">Preguntas</a>.
        </div>
    <?php endif; ?>

    <!-- ==================== INTENTOS POR ESTUDIANTE ==================== -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Intentos por estudiante</h3>
            <small class="text-muted">
                Intentos permitidos por defecto: <strong><?= (int)$examen['intentos_permitidos'] ?></strong>.
                Otorgar un intento adicional no borra el historial: el alumno conserva su
                intento anterior y la nota que se le muestra sigue siendo la mejor.
            </small>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover nodt w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th style="width:130px;">Intentos</th>
                        <th style="width:110px;">Mejor nota</th>
                        <th>Último intento</th>
                        <th style="width:200px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($resumen)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Ningún estudiante ha intentado este examen.</td></tr>
                <?php endif; ?>
                <?php foreach ($resumen as $i => $r): ?>
                    <?php
                    $usados  = (int) $r['intentos_usados'];
                    $limite  = (int) $r['limite'];
                    $extra   = (int) $r['intentos_extra'];
                    $enCurso = (int) $r['intentos_en_curso'] > 0;
                    ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td>
                            <strong><?= htmlspecialchars($r['estudiante_nombre']) ?></strong>
                            <br><small class="text-muted">@<?= htmlspecialchars($r['username']) ?></small>
                        </td>
                        <td>
                            <strong><?= $usados ?>/<?= $limite ?></strong>
                            <?php if ($extra > 0): ?>
                                <span class="badge badge-info ml-1" title="Intento adicional otorgado por el docente">
                                    <i class="fas fa-gift"></i> 1 extra
                                </span>
                            <?php endif; ?>
                            <?php if ($enCurso): ?>
                                <br><span class="badge badge-warning mt-1"><i class="fas fa-hourglass-half"></i> Rindiendo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['mejor_nota'] !== null): ?>
                                <strong><?= number_format((float)$r['mejor_nota'], 1) ?>/20</strong>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small">
                            <?= $r['ultimo_iniciado_at'] ? date('d/m/Y H:i', strtotime($r['ultimo_iniciado_at'])) : '—' ?>
                        </td>
                        <td>
                            <?php if ($extra > 0): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        title="Retirar el intento adicional"
                                        onclick="ExamenIntentos.revocar(<?= (int)$examen['id'] ?>, <?= (int)$r['estudiante_id'] ?>, '<?= htmlspecialchars(addslashes($r['estudiante_nombre'])) ?>')">
                                    <i class="fas fa-undo"></i> Retirar
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-primary"
                                        title="Dar un único intento adicional a este estudiante"
                                        onclick="ExamenIntentos.otorgar(<?= (int)$examen['id'] ?>, <?= (int)$r['estudiante_id'] ?>, '<?= htmlspecialchars(addslashes($r['estudiante_nombre'])) ?>')">
                                    <i class="fas fa-plus"></i> 1 Intento
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($extra > 0 && !empty($r['motivo_extra'])): ?>
                        <tr class="table-light">
                            <td></td>
                            <td colspan="5" class="small text-muted">
                                <i class="fas fa-sticky-note"></i>
                                Motivo del intento adicional
                                (<?= date('d/m/Y H:i', strtotime($r['extra_creado_en'])) ?>):
                                <?= htmlspecialchars($r['motivo_extra']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ==================== DETALLE DE CADA INTENTO ==================== -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-list mr-2"></i>Detalle de intentos</h3>
            <small class="text-muted">Puntaje sobre <?= number_format($totales['puntos_total'], 1) ?> puntos.</small>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover nodt w-100">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Estudiante</th>
                        <th>Intento</th>
                        <th>Iniciado</th>
                        <th>Finalizado</th>
                        <th>Puntaje / <?= number_format($totales['puntos_total'], 1) ?></th>
                        <th>Nota (20)</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($intentos)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Sin intentos registrados.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($intentos as $i => $it): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($it['estudiante_nombre']) ?></td>
                        <td><?= (int)$it['numero_intento'] ?></td>
                        <td><?= date('d/m H:i', strtotime($it['iniciado_at'])) ?></td>
                        <td><?= $it['finalizado_at'] ? date('d/m H:i', strtotime($it['finalizado_at'])) : '—' ?></td>
                        <td><?= $it['puntaje'] !== null ? number_format((float)$it['puntaje'], 1) : '—' ?></td>
                        <td><strong><?= $it['nota'] !== null ? number_format((float)$it['nota'], 1) : '—' ?></strong></td>
                        <td>
                            <?php if ($it['estado'] === 'en_curso'): ?>
                                <span class="badge badge-warning">Rindiendo</span>
                            <?php elseif ($it['estado'] === 'finalizado'): ?>
                                <span class="badge badge-success">Finalizado</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Expirado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>examen/revision?intento_id=<?= (int)$it['id'] ?>" class="btn btn-sm btn-outline-info" title="Revisar respuestas">
                                <i class="fas fa-search"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
const ExamenIntentos = {
    _csrf() {
        const m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.content : '';
    },
    _esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : s;
        return d.innerHTML;
    },

    async otorgar(examenId, estudianteId, nombre) {
        const popup = await Swal.fire({
            title: 'Dar un intento adicional',
            html:
                '<p style="text-align:left;font-size:.9rem;color:#374151;margin:0 0 16px;">' +
                'Le darás <strong>un único intento adicional</strong> a <strong>' + this._esc(nombre) + '</strong>.<br>' +
                'Solo puede hacerse <strong>una vez</strong>. Su intento anterior <strong>no se borra</strong> ' +
                'y la nota que se le muestra sigue siendo la mejor.' +
                '</p>' +
                '<div style="text-align:left;">' +
                '<label style="display:block;font-size:.7rem;font-weight:600;color:#6b6980;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px;">Motivo <span style="text-transform:none;letter-spacing:0;">(queda registrado para reclamos)</span></label>' +
                '<textarea id="swExMotivo" rows="3" ' +
                'placeholder="Ej: El examen tenía un error en la pregunta 3." ' +
                'style="width:100%;padding:10px 12px;border:2px solid #e4e2ed;border-radius:10px;font-size:.9rem;"></textarea>' +
                '</div>',
            showCancelButton: true,
            confirmButtonText: 'Otorgar y notificar',
            focusConfirm: false,
            preConfirm: () => {
                const mot = document.getElementById('swExMotivo').value.trim();
                if (!mot) {
                    Swal.showValidationMessage('Indica el motivo: queda registrado para reclamos.');
                    return false;
                }
                return { motivo: mot };
            }
        });

        if (!popup.isConfirmed) return;
        const datos = popup.value;

        // Indicador de progreso: el envio por SMTP puede tardar varios segundos
        Swal.fire({
            title: 'Otorgando intento...',
            html: 'Guardando el intento adicional y notificando al alumno por correo.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const fd = new FormData();
            fd.append('examen_id', examenId);
            fd.append('estudiante_id', estudianteId);
            fd.append('motivo', datos.motivo);
            fd.append('_csrf_token', this._csrf());

            const res = await fetch(BASE_URL + 'examen/otorgarIntento', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const out = await res.json();

            if (!out.success) {
                Swal.fire('No se pudo otorgar', out.message, 'error');
                return;
            }

            await Swal.fire({
                icon: out.correo_enviado ? 'success' : 'warning',
                title: 'Intento otorgado',
                html: out.correo_enviado
                    ? 'El alumno fue notificado por correo.<br>' +
                      '<small>Ya puede repetir el examen (límite: <strong>' + out.nuevo_limite + '</strong>).</small>'
                    : 'El intento quedó registrado, <strong>pero el correo no se envió</strong>.<br>' +
                      '<small>Avísale al alumno por otro medio. Límite: <strong>' + out.nuevo_limite + '</strong>.</small>',
                confirmButtonText: 'Entendido'
            });
            window.location.reload();
        } catch (e) {
            Swal.fire('Error', 'No se pudo completar la operación.', 'error');
        }
    },

    async revocar(examenId, estudianteId, nombre) {
        const popup = await Swal.fire({
            title: 'Retirar intento adicional',
            html:
                '<p style="text-align:left;font-size:.9rem;color:#374151;margin:0;">' +
                'Se retirará el intento adicional de <strong>' + this._esc(nombre) + '</strong>.<br>' +
                'El historial de intentos <strong>no se modifica</strong>.' +
                '</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, retirar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc2626'
        });

        if (!popup.isConfirmed) return;

        Swal.fire({
            title: 'Retirando...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const fd = new FormData();
            fd.append('examen_id', examenId);
            fd.append('estudiante_id', estudianteId);
            fd.append('_csrf_token', this._csrf());

            const res = await fetch(BASE_URL + 'examen/revocarIntento', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const out = await res.json();

            if (!out.success) {
                Swal.fire('No se pudo retirar', out.message, 'error');
                return;
            }

            await Swal.fire({ icon: 'success', title: 'Intento adicional retirado' });
            window.location.reload();
        } catch (e) {
            Swal.fire('Error', 'No se pudo completar la operación.', 'error');
        }
    }
};
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
