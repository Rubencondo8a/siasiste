<?php
$pageTitle = 'Debates — ' . htmlspecialchars($curso['nombre'] ?? '') . ' — Semana ' . ($semana['numero_semana'] ?? '');
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/discusion.css?v=' . filemtime(ROOT_PATH . 'public/css/discusion.css') . '">';
require __DIR__ . '/../layouts/header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-comments text-primary mr-2"></i>Debates Semana <?= $semana['numero_semana'] ?? '' ?></h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= BASE_URL ?>docente/semanasCurso?curso_id=<?= $semana['curso_id'] ?? 0 ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Volver a Semanas
                </a>
            </div>
        </div>
        <small class="text-muted"><?= htmlspecialchars($curso['nombre'] ?? '') ?> — <?= htmlspecialchars($semana['nombre'] ?? '') ?></small>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php if ($semana['cerrada']): ?>
            <div class="alert alert-warning"><i class="fas fa-lock mr-1"></i>Esta semana está cerrada. No se pueden crear debates.</div>
        <?php endif; ?>

        <?php if (!$semana['cerrada']): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-plus-circle mr-1"></i>Nuevo Tema de Debate</h5>
            </div>
            <div class="card-body">
                <form id="formCrearDiscusion">
                    <input type="hidden" name="semana_id" value="<?= $semana['id'] ?>">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="titulo">Título del tema *</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255" placeholder="Ej: ¿Qué es la normalización de bases de datos?">
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción / Instrucciones</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Instrucciones o contexto para los estudiantes..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6 form-group">
                            <label for="fecha_inicio">Vigente desde *</label>
                            <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-12 col-sm-6 form-group">
                            <label for="fecha_fin">Vigente hasta *</label>
                            <input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin" required>
                        </div>
                    </div>
                    <small class="text-muted d-block mb-2"><i class="fas fa-info-circle mr-1"></i>Al vencer la fecha de fin el debate se cerrará automáticamente.</small>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Crear Debate</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-list mr-1"></i>Debates de la Semana (<?= count($discusiones) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($discusiones)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-comments fa-3x mb-3" style="opacity:.2"></i>
                        <p>No hay debates creados aún.</p>
                    </div>
                <?php else: ?>
                    <?php
                        $ahora = date('Y-m-d H:i:s');
                        foreach ($discusiones as $d):
                            $vigente = $d['estado'] === 'abierta'
                                && !empty($d['fecha_fin']) && $d['fecha_fin'] > $ahora;
                            $vencida = $d['estado'] === 'cerrada'
                                && !empty($d['fecha_fin']) && $d['fecha_fin'] <= $ahora;
                            if ($d['estado'] === 'abierta') {
                                $badge = 'badge-success';
                                $textoBadge = 'Vigente';
                                $icono = 'fa-comments text-success';
                            } elseif ($vencida) {
                                $badge = 'badge-dark';
                                $textoBadge = 'Vencido (cierre automático)';
                                $icono = 'fa-hourglass-end text-dark';
                            } else {
                                $badge = 'badge-secondary';
                                $textoBadge = 'Finalizado';
                                    if (!empty($d['cerrada_en']) && !empty($d['fecha_fin']) && date('Y-m-d', strtotime($d['cerrada_en'])) !== date('Y-m-d', strtotime($d['fecha_fin']))) {
                                        $textoBadge = 'Finalizado (cerrado ' . date('d/m', strtotime($d['cerrada_en'])) . ')';
                                    }
                                $icono = 'fa-lock text-secondary';
                            }
                    ?>
                        <a href="<?= BASE_URL ?>docente/chatDiscusion?discusion_id=<?= $d['id'] ?>"
                           class="discusion-item <?= $d['estado'] === 'cerrada' ? 'cerrada' : 'abierta' ?>">
                            <div class="discusion-item-left">
                                <div class="discusion-item-icon">
                                    <i class="fas <?= $icono ?>"></i>
                                </div>
                            </div>
                            <div class="discusion-item-body">
                                <div class="discusion-item-titulo"><?= htmlspecialchars($d['titulo']) ?></div>
                                <div class="discusion-item-meta">
                                    <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($d['creado_por'] == $this->docenteId() ? 'Tú' : $d['creado_por']) ?></span>
                                    <span><i class="fas fa-message mr-1"></i><?= $d['total_mensajes'] ?> mensaje<?= $d['total_mensajes'] != 1 ? 's' : '' ?></span>
                                    <span><i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></span>
                                    <?php if (!empty($d['fecha_inicio']) && !empty($d['fecha_fin'])): ?>
                                    <span class="<?= (!$vigente && $d['estado']==='abierta') ? 'text-warning font-weight-bold' : '' ?>">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?= date('d/m H:i', strtotime($d['fecha_inicio'])) ?> → <?= date('d/m H:i', strtotime($d['fecha_fin'])) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="discusion-item-right">
                                <span class="badge <?= $badge ?>"><?= $textoBadge ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('formCrearDiscusion')?.addEventListener('submit', function(e) {
    e.preventDefault();
    var fi = this.fecha_inicio.value, ff = this.fecha_fin.value;
    if (fi && ff && new Date(ff) <= new Date(fi)) {
        Swal.fire({ icon: 'warning', title: 'Fechas inválidas', text: 'La fecha de fin debe ser posterior a la de inicio.' });
        return;
    }
    var form = this;
    var btn = form.querySelector('button[type="submit"]');
    if (btn.disabled) return;
    btn.disabled = true;
    var fd = new FormData(form);
    fetch(BASE_URL + 'docente/crearDiscusion', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) {
            Swal.fire({ icon: 'success', title: 'Debate creado', timer: 1500, showConfirmButton: false })
            .then(function(){ location.reload(); });
        } else {
            btn.disabled = false;
            Swal.fire({ icon: 'error', title: 'Error', text: d.message });
        }
    })
    .catch(function(){ btn.disabled = false; Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo crear el debate.' }); });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
