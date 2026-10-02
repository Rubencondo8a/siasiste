<?php
$pageTitle = 'Mis Debates';
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/discusion.css?v=' . filemtime(ROOT_PATH . 'public/css/discusion.css') . '">';
require __DIR__ . '/../layouts/header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-comments text-primary mr-2"></i>Mis Debates</h1>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php if (!empty($semanasAbiertas)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-plus-circle mr-1"></i>Nuevo Tema de Debate</h5>
            </div>
            <div class="card-body">
                <form id="formCrearDiscusion">
                    <?= csrfField() ?>
                    <div class="form-group">
                        <label for="semana_select">Curso / Semana *</label>
                        <select class="form-control" id="semana_select" name="semana_id" required>
                            <option value="">— Seleccione curso y semana —</option>
                            <?php foreach ($semanasAbiertas as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['curso_nombre']) ?> — Semana <?= $s['numero_semana'] ?>: <?= htmlspecialchars($s['semana_nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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

        <?php if (empty($discusiones)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-comments fa-3x mb-3" style="opacity:.2"></i>
                    <p>No has creado ningún debate todavía.</p>
                </div>
            </div>
        <?php else: ?>
            <?php
            $agrupados = [];
            foreach ($discusiones as $d) {
                $key = $d['curso_nombre'] . ' — Semana ' . $d['numero_semana'];
                $agrupados[$key][] = $d;
            }
            foreach ($agrupados as $grupo => $items): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-book-open mr-1"></i><?= htmlspecialchars($grupo) ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($items as $d): ?>
                            <?php
                                $ahoraD = date('Y-m-d H:i:s');
                                $vigenteD = $d['estado'] === 'abierta'
                                    && !empty($d['fecha_fin']) && $d['fecha_fin'] > $ahoraD;
                                $vencidaD = $d['estado'] === 'cerrada'
                                    && !empty($d['fecha_fin']) && $d['fecha_fin'] <= $ahoraD;
                                if ($d['estado'] === 'abierta') {
                                    $badgeD = 'badge-success'; $textoD = 'Vigente'; $iconoD = 'fa-comments text-success';
                                } elseif ($vencidaD) {
                                    $badgeD = 'badge-dark'; $textoD = 'Vencido (cierre automático)'; $iconoD = 'fa-hourglass-end text-dark';
                                } else {
                                    $badgeD = 'badge-secondary'; $textoD = 'Finalizado';
                                    if (!empty($d['cerrada_en']) && !empty($d['fecha_fin']) && date('Y-m-d', strtotime($d['cerrada_en'])) !== date('Y-m-d', strtotime($d['fecha_fin']))) {
                                        $textoD = 'Finalizado (cerrado ' . date('d/m', strtotime($d['cerrada_en'])) . ')';
                                    } $iconoD = 'fa-lock text-secondary';
                                }
                            ?>
                            <a href="<?= BASE_URL ?>docente/chatDiscusion?discusion_id=<?= $d['id'] ?>"
                               class="discusion-item <?= $d['estado'] === 'cerrada' ? 'cerrada' : 'abierta' ?>">
                                <div class="discusion-item-left">
                                    <div class="discusion-item-icon">
                                        <i class="fas <?= $iconoD ?>"></i>
                                    </div>
                                </div>
                                <div class="discusion-item-body">
                                    <div class="discusion-item-titulo"><?= htmlspecialchars($d['titulo']) ?></div>
                                    <div class="discusion-item-meta">
                                        <span><i class="fas fa-message mr-1"></i><?= $d['total_mensajes'] ?> mensaje<?= $d['total_mensajes'] != 1 ? 's' : '' ?></span>
                                        <span><i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></span>
                                        <?php if (!empty($d['fecha_inicio']) && !empty($d['fecha_fin'])): ?>
                                        <span>
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            <?= date('d/m H:i', strtotime($d['fecha_inicio'])) ?> → <?= date('d/m H:i', strtotime($d['fecha_fin'])) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="discusion-item-right">
                                    <span class="badge <?= $badgeD ?>"><?= $textoD ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
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
