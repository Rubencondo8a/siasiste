<?php $pageTitle = 'Mi Horario'; require_once __DIR__ . '/../layouts/header.php'; ?>
<script>document.body.classList.add('schedule-page');</script>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<?php
$diasSemana = ['Lunes','Martes','Miercoles','Jueves','Viernes'];
$palette = ['#4E37E6','#E63777','#37E6A6','#0EA5E9','#CFE637','#8B5CF6','#F97316'];
$horas = range(7, 14);
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/horario-docente.css?v=<?= filemtime(ROOT_PATH . 'public/css/horario-docente.css') ?>">

<section class="content-header">
    <h1><i class="fas fa-calendar-alt mr-1"></i> Mi Horario de Clases</h1>
</section>

<section class="content">
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-week mr-1"></i> Horario Semanal</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-success" onclick="openCreateModal()">
                    <i class="fas fa-plus mr-1"></i> Nuevo
                </button>
                <a href="<?= BASE_URL ?>docente/exportarHorarios" class="btn btn-sm btn-outline-primary ml-1">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="sched-wrap">
                <table class="sched-table">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <?php foreach ($diasSemana as $d): ?>
                                <th><?= $d ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($horas as $hr): ?>
                            <tr>
                                <td class="sched-time"><?= str_pad($hr, 2, '0', STR_PAD_LEFT) ?>:00</td>
                                <?php foreach ($diasSemana as $d):
                                    $slotStart = str_pad($hr, 2, '0', STR_PAD_LEFT) . ':00:00';
                                    $slotEnd   = str_pad($hr + 1, 2, '0', STR_PAD_LEFT) . ':00:00';
                                    $blocks = array_filter($horarios, function($h) use ($d, $slotStart, $slotEnd) {
                                        return $h['dia'] === $d && $h['hora_inicio'] < $slotEnd && $h['hora_fin'] > $slotStart;
                                    });
                                ?>
                                    <?php
                                        $hasBlocks = !empty($blocks);
                                        $cellClick = $hasBlocks ? '' : " onclick=\"openCreateModal('{$d}', '" . str_pad($hr, 2, '0', STR_PAD_LEFT) . ":00')\"";
                                    ?>
                                    <td class="sched-cell"<?= $cellClick ?>>
                                        <?php if ($hasBlocks):
                                            foreach ($blocks as $b):
                                                $colorIdx = array_search($b['curso_id'], array_column($horarios, 'curso_id'));
                                                $color = $palette[$colorIdx % count($palette)];
                                        ?>
                                            <div class="sched-block" style="background:<?= $color ?>" onclick="openEditModal(<?= $b['id'] ?>)">
                                                <span class="sched-del" title="Eliminar horario" onclick="event.stopPropagation();deleteHorario(<?= $b['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </span>
                                                <div class="sched-code"><?= htmlspecialchars($b['curso_codigo']) ?></div>
                                                <div class="sched-time-sm">
                                                    <?= substr($b['hora_inicio'], 0, 5) ?> - <?= substr($b['hora_fin'], 0, 5) ?>
                                                </div>
                                                <?php if ($b['ambiente_nombre']): ?>
                                                    <div class="sched-room">
                                                        <i class="fas fa-map-marker-alt" style="margin-right:2px"></i>
                                                        <?= htmlspecialchars($b['ambiente_nombre']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach;
                                        else: ?>
                                        <div class="sched-add">
                                            <i class="fas fa-plus-circle"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="scheduleModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" id="scheduleModalHeader">
                <h5 class="modal-title"><i class="fas fa-plus mr-1"></i> Nuevo Horario</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="scheduleModalBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger mr-auto" id="scheduleDeleteBtn" onclick="deleteScheduleFromModal()" style="display:none;">
                    <i class="fas fa-trash mr-1"></i> Eliminar
                </button>
                <button type="button" class="btn btn-primary" id="scheduleSaveBtn" onclick="submitScheduleForm()">
                    <i class="fas fa-save mr-1"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash mr-1"></i> Confirmar Eliminación</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size:2.5rem;"></i>
                <p class="mb-0 font-weight-bold" id="confirmDeleteMsg"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="executeDelete()"><i class="fas fa-trash mr-1"></i> Sí, Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentEventId = null, pendingDeleteId = null;

window.addEventListener('resize', function() { setTimeout(sizeRows, 50); });

function sizeRows() {
    var rows = document.querySelectorAll('.sched-table tbody tr');
    if (!rows.length) return;
    var top = 0, el = rows[0];
    while (el) { top += el.offsetTop || 0; el = el.offsetParent; }
    var avail = window.innerHeight - top - 10;
    var h = Math.max(44, Math.floor(avail / rows.length));
    for (var i = 0; i < rows.length; i++) rows[i].style.height = h + 'px';
}
window.addEventListener('load', function() { setTimeout(sizeRows, 100); });
if (document.readyState === 'complete') setTimeout(sizeRows, 100);

function openEditModal(id) {
    currentEventId = id;
    document.getElementById('scheduleDeleteBtn').style.display = '';
    loadModalForm('<?= BASE_URL ?>docente/formularioHorario?id=' + id, 'Editar Horario', 'bg-primary text-white');
}

function openCreateModal(dia, hora) {
    currentEventId = null;
    document.getElementById('scheduleDeleteBtn').style.display = 'none';
    var url = '<?= BASE_URL ?>docente/formularioHorario';
    if (dia) url += '?dia=' + encodeURIComponent(dia);
    if (hora) url += '&hora=' + encodeURIComponent(hora);
    loadModalForm(url, 'Nuevo Horario', 'bg-success text-white');
}

function loadModalForm(url, title, headerClass) {
    var body = document.getElementById('scheduleModalBody');
    var header = document.getElementById('scheduleModalHeader');
    body.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';
    header.className = 'modal-header ' + headerClass;
    header.querySelector('.modal-title').innerHTML = '<i class="fas fa-' + (title === 'Nuevo Horario' ? 'plus' : 'pen') + ' mr-1"></i> ' + title;
    $('#scheduleModal').modal('show');
    fetch(url)
        .then(function(r) { return r.text(); })
        .then(function(html) { body.innerHTML = html; })
        .catch(function() { body.innerHTML = '<div class="alert alert-danger mb-0">Error al cargar el formulario.</div>'; });
}

function submitScheduleForm() {
    var form = document.getElementById('formularioHorario');
    if (!form) return;
    if (!form.checkValidity()) { form.reportValidity(); return; }
    var data = new FormData(form);
    var btn = document.getElementById('scheduleSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Guardando...';
    fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: data })
        .then(function(r) { return r.json().catch(function() { return {ok: r.ok}; }); })
        .then(function(res) {
            $('#scheduleModal').modal('hide');
            if (res.ok) location.reload();
            else showToast('error', res.message || 'Error al guardar');
        })
        .catch(function() {
            $('#scheduleModal').modal('hide');
            location.reload();
        })
        .finally(function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar';
        });
}

function deleteHorario(id) {
    pendingDeleteId = id;
    document.getElementById('confirmDeleteMsg').textContent = '¿Está seguro de eliminar este horario?';
    $('#confirmDeleteModal').modal({ backdrop: 'static', keyboard: false }).modal('show');
}

function executeDelete() {
    if (!pendingDeleteId) return;
    var id = pendingDeleteId;
    pendingDeleteId = null;
    $('#confirmDeleteModal').modal('hide');
    fetch('<?= BASE_URL ?>docente/eliminarHorario?id=' + id + '&ajax=1', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json().catch(function() { return {ok: r.ok}; }); })
        .then(function() { location.reload(); })
        .catch(function() { location.reload(); });
}

function deleteScheduleFromModal() {
    if (!currentEventId) return;
    var id = currentEventId;
    $('#scheduleModal').one('hidden.bs.modal', function() {
        $('body').removeClass('modal-open');
        $('.modal-backdrop').remove();
        setTimeout(function() { deleteHorario(id); }, 150);
    }).modal('hide');
}

function showToast(type, msg) {
    var bg = type === 'success' ? '#37E6A6' : '#E63777';
    var tc = type === 'success' ? '#0A3D28' : '#fff';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    var html = '<div id="schedToast" style="position:fixed;top:20px;right:20px;z-index:99999;background:' + bg + ';color:' + tc + ';padding:14px 20px;border-radius:8px;box-shadow:0 10px 25px rgba(18,0,38,.2);font-weight:600;display:flex;align-items:center;gap:10px;min-width:280px;">' +
               '<i class="fas ' + icon + '" style="font-size:1.2rem;"></i> ' + msg + '</div>';
    document.body.insertAdjacentHTML('beforeend', html);
    var el = document.getElementById('schedToast');
    setTimeout(function() {
        if (el) { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(function() { if (el) el.remove(); }, 300); }
    }, 3000);
}
</script>

<?php
$inlineScripts = $inlineScripts ?? [];
$inlineScripts[] = '
    var _ms = false;
    $(document).on("shown.bs.modal", "#confirmDeleteModal, #scheduleModal", function() {
        if (_ms) return;
        _ms = true;
        $("#confirmDeleteModal, #scheduleModal").on("hidden.bs.modal", function() {
            $("body").removeClass("modal-open");
            $(".modal-backdrop").remove();
        });
    });
';
require_once __DIR__ . '/../layouts/footer.php'; ?>
