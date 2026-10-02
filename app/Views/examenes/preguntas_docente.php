<?php
$pageTitle = $pageTitle ?? 'Preguntas';
$esBorrador = (int) $examen['estado'] === Examen::ESTADO_BORRADOR;
$esPersonal = $examen['tipo'] === 'personal';
$esInmediato = !$esPersonal || $examen['modo_disponibilidad'] !== Examen::MODO_PROGRAMADO;
$ventanaDesde = !empty($examen['disponible_desde']) ? strtotime($examen['disponible_desde']) : null;
$ventanaHasta = !empty($examen['disponible_hasta']) ? strtotime($examen['disponible_hasta']) : null;
$faltaDesde = $esPersonal && !$esInmediato && $ventanaDesde === null;

if (!$esPersonal) {
    $textoActivar = 'Los estudiantes lo verán, pero solo podrán rendirlo cuando usted lance la sesión en vivo.';
} elseif ($esInmediato) {
    $textoActivar = 'Quedará visible y disponible de inmediato, sin fecha de apertura ni de cierre. Cada estudiante tendrá '
        . (int) $examen['duracion_min'] . ' minutos desde que lo abra.';
} else {
    $textoActivar = 'Quedará visible y disponible desde el '
        . ($ventanaDesde ? date('d/m/Y \a\t H:i', $ventanaDesde) : '(sin fecha de inicio)')
        . ($ventanaHasta ? ' hasta el ' . date('d/m/Y \a\t H:i', $ventanaHasta) : ' y sin fecha límite')
        . '.';
}
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../partials/messages.php';
?>

<section class="content-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <h1>
            <i class="fas fa-question-circle mr-2"></i>
            <?= htmlspecialchars($examen['titulo']) ?>
            <?php if ($examen['tipo'] === 'en_vivo'): ?>
                <span class="badge badge-danger ml-2">En vivo</span>
            <?php else: ?>
                <span class="badge badge-info ml-2">Personal</span>
            <?php endif; ?>
            <?php if ($esBorrador): ?>
                <span class="badge badge-warning ml-2"><i class="fas fa-pen"></i> Borrador</span>
            <?php else: ?>
                <span class="badge badge-success ml-2"><i class="fas fa-eye"></i> Activo</span>
            <?php endif; ?>
        </h1>
        <div class="d-flex">
            <a href="<?= BASE_URL ?>examen/lista" class="btn btn-outline-secondary mr-2"><i class="fas fa-arrow-left"></i> Volver</a>
            <a href="<?= BASE_URL ?>examen/resultados?examen_id=<?= (int)$examen['id'] ?>" class="btn btn-outline-success mr-2"><i class="fas fa-poll"></i> Resultados</a>
            <?php if ($examen['tipo'] === 'en_vivo' && !$esBorrador): ?>
                <button type="button" class="btn btn-danger mr-2"
                        onclick="ExamenDocente.iniciarEnVivo(<?= (int)$examen['id'] ?>, <?= (int)$examen['duracion_min'] ?>)"
                        <?= !empty($sesionActiva) ? 'disabled' : '' ?>>
                    <i class="fas fa-broadcast-tower"></i> Lanzar en vivo
                </button>
            <?php endif; ?>
            <?php if ($esBorrador): ?>
                <button type="button" class="btn btn-success" id="btnActivar"
                        onclick="ExamenDocente.cambiarEstado(<?= (int)$examen['id'] ?>, 'activar')"
                        data-texto="<?= htmlspecialchars($textoActivar) ?>"
                        data-falta-desde="<?= $faltaDesde ? '1' : '0' ?>"
                        <?= (int) $totales['total'] === 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-play"></i> Activar examen
                </button>
            <?php else: ?>
                <button type="button" class="btn btn-outline-secondary"
                        onclick="ExamenDocente.cambiarEstado(<?= (int)$examen['id'] ?>, 'desactivar')">
                    <i class="fas fa-pause"></i> Desactivar
                </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($esBorrador): ?>
<div class="alert alert-warning mb-0">
    <i class="fas fa-eye-slash"></i> Este examen está en <strong>borrador</strong>: los estudiantes aún no pueden verlo.
    <?php if ($esPersonal): ?>
        <?php if ($esInmediato): ?>
            <br>Al activarlo quedará <strong>disponible de inmediato, sin fechas</strong>: cualquier estudiante matriculado podrá tomarlo cuando quiera.
        <?php elseif ($faltaDesde): ?>
            <br>Antes de activarlo debe indicar la <strong>fecha y hora de inicio</strong> en el panel de disponibilidad.
        <?php else: ?>
            <br>Al activarlo quedará disponible desde el <strong><?= date('d/m/Y \a\t H:i', $ventanaDesde) ?></strong><?php
                echo $ventanaHasta ? ' hasta el <strong>' . date('d/m/Y \a\t H:i', $ventanaHasta) . '</strong>' : ' (sin fecha límite)'; ?>.
        <?php endif; ?>
    <?php else: ?>
        <br>Al activarlo los estudiantes lo verán, pero solo podrán rendirlo cuando lance la sesión en vivo.
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($sesionActiva)): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap">
    <span><i class="fas fa-circle text-success"></i> Sesión en vivo activa hasta <?= date('H:i', strtotime($sesionActiva['fin_en'])) ?>.</span>
    <a href="<?= BASE_URL ?>examen/vigilar?sesion_id=<?= (int)$sesionActiva['id'] ?>" class="btn btn-sm btn-primary">Vigilar sesión</a>
</div>
<?php endif; ?>

<section class="content">

<?php if ($esPersonal): ?>
<div class="card" id="cardDisponibilidad">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="fas fa-calendar-alt"></i> Disponibilidad</h3>
        <?php if (!$esBorrador): ?>
            <span class="badge badge-<?= $esInmediato ? 'info' : 'success' ?>">
                <?= $esInmediato ? 'Inmediato' : 'Programado' ?>
            </span>
        <?php endif; ?>
    </div>
    <form method="post" action="<?= BASE_URL ?>examen/guardarDisponibilidad" class="card-body no-warn">
        <?= csrfField() ?>
        <input type="hidden" name="examen_id" value="<?= (int) $examen['id'] ?>">

        <div class="form-group" data-toggle="buttons">
            <label>Modalidad</label>
            <div class="row mx-n1">
                <div class="col-12 col-sm-6 px-1 pb-2 pb-sm-0">
                    <label class="btn btn-outline-primary btn-block <?= $esInmediato ? 'active' : '' ?>" id="btnModoInmediato">
                        <input type="radio" name="modo_disponibilidad" value="inmediato" <?= $esInmediato ? 'checked' : '' ?>>
                        <i class="fas fa-bolt"></i> Inmediato, sin fechas
                    </label>
                </div>
                <div class="col-12 col-sm-6 px-1 pb-2 pb-sm-0">
                    <label class="btn btn-outline-primary btn-block <?= !$esInmediato ? 'active' : '' ?>" id="btnModoProgramado">
                        <input type="radio" name="modo_disponibilidad" value="programado" <?= !$esInmediato ? 'checked' : '' ?>>
                        <i class="fas fa-calendar-alt"></i> Programado, por fecha y hora
                    </label>
                </div>
            </div>
        </div>

        <div id="bloqueVentanaFechas" style="display:<?= $esInmediato ? 'none' : 'block' ?>;">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label class="<?= $faltaDesde && $esBorrador ? 'text-danger font-weight-bold' : '' ?>">
                        Disponible desde <?= $esBorrador ? '<small>(necesario para activar)</small>' : '' ?>
                    </label>
                    <input type="datetime-local" name="disponible_desde" class="form-control" value="<?= $ventanaDesde ? date('Y-m-d\TH:i', $ventanaDesde) : '' ?>">
                </div>
                <div class="col-md-6 form-group">
                    <label>Disponible hasta <small>(opcional)</small></label>
                    <input type="datetime-local" name="disponible_hasta" class="form-control" value="<?= $ventanaHasta ? date('Y-m-d\TH:i', $ventanaHasta) : '' ?>">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <small class="text-muted mb-2" id="hintDisponibilidad"
                   data-texto-inmediato="Sin fecha de apertura ni de cierre. Cada estudiante tiene <?= (int) $examen['duracion_min'] ?> minutos desde que abre el examen."
                   data-texto-borrador="Mientras esté en borrador puede dejarlo vacío y ajustarlo cuantas veces necesite."
                   data-texto-activo="Al guardar, la ventana se aplica de inmediato a los estudiantes matriculados.">
                <?php if ($esInmediato): ?>
                    Sin fecha de apertura ni de cierre. Cada estudiante tiene <?= (int) $examen['duracion_min'] ?> minutos desde que abre el examen.
                <?php elseif ($esBorrador): ?>
                    Mientras esté en borrador puede dejarlo vacío y ajustarlo cuantas veces necesite.
                <?php else: ?>
                    Al guardar, la ventana se aplica de inmediato a los estudiantes matriculados.
                <?php endif; ?>
            </small>
            <button type="submit" class="btn btn-sm btn-primary mb-0" <?= !empty($sesionActiva) ? 'disabled' : '' ?>>
                <i class="fas fa-save"></i> Guardar disponibilidad
            </button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="callout callout-info">
    <h5><i class="fas fa-broadcast-tower"></i> Disponibilidad en vivo</h5>
    <p class="mb-0">Este examen no tiene fecha fija: los estudiantes solo pueden rendirlo mientras haya una sesión en vivo activa.</p>
</div>
<?php endif; ?>

    <div class="row">
        <div class="col-md-5">
            <div class="card card-primary" id="cardPregunta">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title" id="tituloFormulario"><i class="fas fa-plus"></i> Nueva pregunta</h3>
                    <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btnCancelarEdicion" onclick="ExamenPreguntas.cancelarEdicion()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
                <style>
.selector-tipo .btn {
    display: block;
    width: 100%;
    min-height: 48px;
    line-height: 1.2;
    padding-top: .65rem;
    padding-bottom: .65rem;
    border-radius: .25rem;
}
</style>
<form id="frmPregunta" class="card-body no-warn" onsubmit="return ExamenPreguntas.guardar(event);">
                    <?= csrfField() ?>
                    <input type="hidden" name="examen_id" value="<?= (int) $examen['id'] ?>">
                    <input type="hidden" name="pregunta_id" id="preg_id" value="0">
                    <div class="alert alert-warning py-2 px-3 d-none" id="avisoEdicion" style="font-size:.85rem;">
                        <i class="fas fa-pencil-alt mr-1"></i>
                        <strong>Editando pregunta.</strong> Los cambios reemplazan los anteriores al guardar.
                    </div>
                    <div class="form-group">
                        <label>Texto de la pregunta</label>
                        <textarea name="texto" id="preg_texto" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Tipo de pregunta</label>
                        <div class="row mx-n1 selector-tipo" data-toggle="buttons">
                            <div class="col-12 col-sm-6 px-1 pb-2 pb-sm-0">
                                <label class="btn btn-outline-primary btn-block active" id="btnTipoOpcion">
                                    <input type="radio" name="tipo" value="opcion" checked>
                                    <i class="fas fa-list-ul"></i> Opción múltiple
                                </label>
                            </div>
                            <div class="col-12 col-sm-6 px-1">
                                <label class="btn btn-outline-primary" id="btnTipoAbierta">
                                    <input type="radio" name="tipo" value="abierta">
                                    <i class="fas fa-keyboard"></i> Respuesta escrita
                                </label>
                            </div>
                        </div>
                        <small class="text-muted d-none d-block" id="hintAbierta">El estudiante escribirá su respuesta (máx. 150 caracteres). Usted la calificará manualmente asignando puntos.</small>
                    </div>
                    <div class="form-group">
                        <label>Puntos</label>
                        <input type="number" name="puntos" id="preg_puntos" class="form-control" min="0.5" step="0.5" value="1" style="max-width:120px;">
                    </div>
                    <div id="contOpciones"></div>
                    <small class="text-muted d-block mb-2"><i class="fas fa-info-circle"></i> Marque con el checkbox la(s) respuesta(s) correcta(s). Puede marcar más de una.</small>
                    <button type="button" class="btn btn-sm btn-outline-secondary mb-2" id="btnAgregarOpcion" onclick="ExamenPreguntas.agregarOpcion()">
                        <i class="fas fa-plus"></i> Agregar opción
                    </button>
                    <hr>
                    <button type="submit" class="btn btn-success" id="btnGuardarPregunta"><i class="fas fa-save"></i> <span id="txtBotonGuardar">Guardar pregunta</span></button>
                </form>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Preguntas (<?= $totales['total'] ?>)</h3>
                    <span class="badge badge-dark"><?= number_format($totales['puntos_total'], 1) ?> puntos totales</span>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped w-100 nodt">
                        <thead>
                            <tr><th style="width:50px;">#</th><th>Pregunta</th><th>Tipo</th><th>Ptos</th><th>Correcta</th><th></th></tr>
                        </thead>
                        <tbody id="tbodyPreguntas">
                            <?php foreach ($preguntas as $i => $p): ?>
                            <tr data-id="<?= (int)$p['id'] ?>">
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($p['texto'], 0, 80, '…')) ?></td>
                                <td>
                                    <?php if ($p['tipo'] === 'abierta'): ?>
                                        <span class="badge badge-secondary"><i class="fas fa-keyboard"></i> Escrita</span>
                                    <?php else: ?>
                                        <span class="badge badge-light">Opciones</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((float)$p['puntos'], 1) ?></td>
                                <td>
                                    <?php if ($p['tipo'] === 'opcion'): foreach ($p['opciones'] as $op): if ((int)$op['es_correcta'] === 1): ?>
                                        <small class="text-success d-block"><i class="fas fa-check"></i> <?= htmlspecialchars($op['texto']) ?></small>
                                    <?php endif; endforeach; else: ?>
                                        <small class="text-muted">Calificación manual</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-1"
                                            data-editar="<?= (int)$p['id'] ?>"
                                            title="Editar esta pregunta">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="ExamenPreguntas.eliminar(<?= (int)$examen['id'] ?>, <?= (int)$p['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($preguntas)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay preguntas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
const PREGUNTAS_EXAMEN = <?= json_encode($preguntas, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

const ExamenPreguntas = {
    csrf: () => document.querySelector('meta[name="csrf-token"]').content,
    esAbierta: () => document.getElementById('btnTipoAbierta').classList.contains('active'),
    editandoId: 0,
    alternarTipo() {
        const abierta = this.esAbierta();
        document.getElementById('contOpciones').style.display = abierta ? 'none' : 'block';
        document.getElementById('btnAgregarOpcion').style.display = abierta ? 'none' : 'inline-block';
        document.getElementById('hintAbierta').classList.toggle('d-none', !abierta);
    },
    limpiarOpciones() {
        document.getElementById('contOpciones').innerHTML = '';
    },
    agregarOpcion(texto, correcta) {
        const cont = document.getElementById('contOpciones');
        const div = document.createElement('div');
        div.className = 'input-group mb-2 opcion-item';
        div.innerHTML =
            '<div class="input-group-prepend">' +
              '<div class="input-group-text"><input type="checkbox" class="chk-correcta" title="Marcar como correcta (puede marcar varias)"></div>' +
            '</div>' +
            '<input type="text" class="form-control op-texto" placeholder="Texto de la opción" maxlength="500">' +
            '<div class="input-group-append">' +
              '<button type="button" class="btn btn-outline-danger btn-quitar" title="Quitar opción"><i class="fas fa-times"></i></button>' +
            '</div>';
        cont.appendChild(div);
        const input = div.querySelector('.op-texto');
        input.value = texto || '';
        const chk = div.querySelector('.chk-correcta');
        chk.checked = !!correcta;
        if (correcta) {
            div.classList.add('border-success');
        }
        div.querySelector('.btn-quitar').addEventListener('click', () => {
            const habiaMarcada = div.classList.contains('border-success');
            div.remove();
            if (habiaMarcada) {
                document.querySelectorAll('#contOpciones .opcion-item').forEach(o => o.classList.remove('border-success'));
            }
        });
        chk.addEventListener('change', () => {
            div.classList.toggle('border-success', chk.checked);
        });
    },
    editar(preguntaId) {
        const p = PREGUNTAS_EXAMEN.find(x => parseInt(x.id, 10) === parseInt(preguntaId, 10));
        if (!p) {
            Swal.fire('No encontrada', 'La pregunta ya no está en el examen.', 'error');
            return;
        }
        this.editandoId = parseInt(p.id, 10);
        document.getElementById('preg_id').value = this.editandoId;
        document.getElementById('preg_texto').value = p.texto;
        document.getElementById('preg_puntos').value = parseFloat(p.puntos);

        const abierta = p.tipo === 'abierta';
        document.getElementById('btnTipoAbierta').classList.toggle('active', abierta);
        document.getElementById('btnTipoOpcion').classList.toggle('active', !abierta);
        document.querySelector('input[name=tipo][value=abierta]').checked = abierta;
        document.querySelector('input[name=tipo][value=opcion]').checked = !abierta;
        this.alternarTipo();

        this.limpiarOpciones();
        (p.opciones || []).forEach(o => this.agregarOpcion(o.texto, parseInt(o.es_correcta, 10) === 1));

        document.getElementById('avisoEdicion').classList.remove('d-none');
        document.getElementById('btnCancelarEdicion').classList.remove('d-none');
        document.getElementById('txtBotonGuardar').textContent = 'Guardar cambios';
        document.getElementById('tituloFormulario').innerHTML =
            '<i class="fas fa-pencil-alt"></i> Editando pregunta #' + this.editandoId;

        document.getElementById('cardPregunta').scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('preg_texto').focus();
    },
    cancelarEdicion() {
        this.editandoId = 0;
        document.getElementById('preg_id').value = 0;
        document.getElementById('preg_texto').value = '';
        document.getElementById('preg_puntos').value = 1;
        this.limpiarOpciones();
        document.getElementById('avisoEdicion').classList.add('d-none');
        document.getElementById('btnCancelarEdicion').classList.add('d-none');
        document.getElementById('txtBotonGuardar').textContent = 'Guardar pregunta';
        document.getElementById('tituloFormulario').innerHTML = '<i class="fas fa-plus"></i> Nueva pregunta';
    },
    async guardar(ev) {
        ev.preventDefault();
        const fd = new FormData(ev.target);
        if (!fd.has('_csrf_token')) {
            fd.append('_csrf_token', this.csrf());
        }
        if (this.esAbierta()) {
            // Respuesta escrita: no se envían opciones
        } else {
            const radios = document.querySelectorAll('#contOpciones .opcion-item');
            let idx = 0;
            let marcadas = 0;
            radios.forEach(item => {
                const texto = item.querySelector('.op-texto').value.trim();
                if (!texto) return;
                fd.append('opcion_texto[' + idx + ']', texto);
                if (item.querySelector('.chk-correcta').checked) {
                    fd.append('opcion_correcta[' + idx + ']', '1');
                    marcadas++;
                }
                idx++;
            });
            if (!fd.has('opcion_texto[0]') || idx < 2) {
                Swal.fire('Faltan opciones', 'Agregue al menos 2 opciones.', 'warning');
                return false;
            }
            if (marcadas === 0) {
                Swal.fire('Sin respuesta correcta', 'Marque al menos una opción como correcta (puede marcar varias).', 'warning');
                return false;
            }
        }
        const esEdicion = this.editandoId > 0;
        const res = await fetch(BASE_URL + 'examen/' + (esEdicion ? 'editarPregunta' : 'guardarPregunta'), {
            method: 'POST', body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: esEdicion ? 'Actualizada' : 'Guardada', timer: 900, showConfirmButton: false });
            setTimeout(() => window.location.reload(), 700);
        } else {
            Swal.fire('Error', data.message, 'error');
        }
        return false;
    },
    async eliminar(examenId, preguntaId) {
        Swal.fire({
            title: '¿Eliminar pregunta?',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
        }).then(async r => {
            if (!r.isConfirmed) return;
            const fd = new FormData();
            fd.append('examen_id', examenId);
            fd.append('pregunta_id', preguntaId);
            fd.append('_csrf_token', this.csrf());
            try {
                const res = await fetch(BASE_URL + 'examen/eliminarPregunta', {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: data.message, timer: 900, showConfirmButton: false });
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'No se pudo eliminar la pregunta.', 'error');
            }
        });
    }
};
document.getElementById('btnTipoOpcion').addEventListener('click', () => setTimeout(() => ExamenPreguntas.alternarTipo(), 0));
document.getElementById('btnTipoAbierta').addEventListener('click', () => setTimeout(() => ExamenPreguntas.alternarTipo(), 0));
ExamenPreguntas.alternarTipo();
document.querySelectorAll('[data-editar]').forEach(b => {
    b.addEventListener('click', () => ExamenPreguntas.editar(b.getAttribute('data-editar')));
});

// --- Disponibilidad: cambio de modalidad y confirmacion al guardar ---
(function () {
    const frm = document.querySelector('#cardDisponibilidad form');
    if (!frm) return;

    const btnInmediato = document.getElementById('btnModoInmediato');
    const btnProgramado = document.getElementById('btnModoProgramado');
    const bloqueFechas = document.getElementById('bloqueVentanaFechas');
    const hint = document.getElementById('hintDisponibilidad');
    const esBorrador = <?= $esBorrador ? 'true' : 'false' ?>;
    const enCurso = <?= (int) $intentosEnCurso ?>;

    function esProgramado() {
        return btnProgramado.classList.contains('active');
    }

    function alternar() {
        const programado = esProgramado();
        bloqueFechas.style.display = programado ? 'block' : 'none';
        if (!programado) {
            hint.textContent = hint.dataset.textoInmediato;
        } else {
            hint.textContent = esBorrador ? hint.dataset.textoBorrador : hint.dataset.textoActivo;
        }
    }

    btnInmediato.addEventListener('click', () => setTimeout(alternar, 0));
    btnProgramado.addEventListener('click', () => setTimeout(alternar, 0));

    frm.addEventListener('submit', ev => {
        if (enCurso === 0) return;
        ev.preventDefault();
        Swal.fire({
            title: 'Hay estudiantes rindiendo ahora',
            html: '<strong>' + enCurso + '</strong> intento(s) en curso.<br>Al guardar, el cambio se aplica de inmediato y puede cerrarles el examen antes de tiempo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Guardar de todos modos',
            cancelButtonText: 'Cancelar'
        }).then(r => {
            if (r.isConfirmed) frm.submit();
        });
    });

    if (esBorrador === false) {
        hint.textContent = esProgramado() ? hint.dataset.textoActivo : hint.dataset.textoInmediato;
    }
})();</script>

<script>
const ExamenDocente = {
    csrf: () => document.querySelector('meta[name="csrf-token"]').content,
    cambiarEstado(id, accion) {
        const btn = document.getElementById('btnActivar');
        const esActivar = accion === 'activar';

        if (esActivar && btn && btn.dataset.faltaDesde === '1') {
            Swal.fire({
                title: 'Falta la fecha de inicio',
                text: 'Indique desde qué fecha y hora estará disponible y luego vuelva a activar el examen.',
                icon: 'warning',
                confirmButtonText: 'Ir a disponibilidad',
                cancelButtonText: 'Entendido',
                showCancelButton: true
            }).then(r => {
                if (r.isConfirmed) {
                    const card = document.getElementById('cardDisponibilidad');
                    if (card) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const input = card.querySelector('input[name="disponible_desde"]');
                        if (input) input.focus();
                    }
                }
            });
            return;
        }

        const texto = esActivar && btn ? btn.dataset.texto : 'Volverá a estado borrador y dejará de ser visible para los estudiantes.';
        Swal.fire({
            title: esActivar ? '¿Activar el examen?' : '¿Desactivar el examen?',
            text: texto,
            icon: esActivar ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: esActivar ? 'Sí, activar' : 'Sí, desactivar',
            cancelButtonText: 'Cancelar'
        }).then(async r => {
            if (!r.isConfirmed) return;
            const fd = new FormData();
            fd.append('id', id);
            fd.append('accion', accion);
            fd.append('_csrf_token', ExamenDocente.csrf());
            const res = await fetch(BASE_URL + 'examen/cambiarEstado', {
                method: 'POST', body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: data.message, timer: 1100, showConfirmButton: false });
                setTimeout(() => window.location.reload(), 800);
            } else {
                Swal.fire('Error', data.message, 'error');
            }
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
            fd.append('_csrf_token', ExamenDocente.csrf());
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
