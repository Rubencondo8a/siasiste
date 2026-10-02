<?php
$pageTitle = 'Chat — ' . htmlspecialchars($discusion['titulo'] ?? '');
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/discusion.css?v=' . filemtime(ROOT_PATH . 'public/css/discusion.css') . '">';
require __DIR__ . '/../layouts/header.php';
?>

<section class="content-header" style="display:none;"></section>

<div class="chat-wrapper">
    <div class="chat-header">
        <a href="<?= BASE_URL ?>docente/discusiones?semana_id=<?= $semana['id'] ?>" class="chat-back">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="chat-header-info">
            <div class="chat-header-title" data-toggle="tooltip" data-placement="bottom"
                 title="<?= htmlspecialchars($discusion['titulo']) ?><?= $discusion['descripcion'] ? ' — ' . htmlspecialchars($discusion['descripcion']) : '' ?>">
                <?= htmlspecialchars($discusion['titulo']) ?>
            </div>
            <div class="chat-header-meta">
                <?= htmlspecialchars($curso['nombre'] ?? '') ?> — Semana <?= $semana['numero_semana'] ?>
                <span class="chat-estado <?= $discusion['estado'] === 'abierta' ? 'abierta' : 'cerrada' ?>" id="chatEstado">
                    <?= $discusion['estado'] === 'abierta' ? '🟢 Abierto' : '🔴 Cerrado' ?>
                </span>
                <?php if ($discusion['estado'] === 'cerrada' && !empty($discusion['cerrada_en'])
                        && (empty($discusion['fecha_fin']) || date('Y-m-d H:i', strtotime($discusion['cerrada_en'])) !== date('Y-m-d H:i', strtotime($discusion['fecha_fin'])))): ?>
                    <small class="text-danger ml-2 d-none d-md-inline">
                        <i class="fas fa-lock"></i> Cerrado el <?= date('d/m H:i', strtotime($discusion['cerrada_en'])) ?> (antes de la fecha prevista)
                    </small>
                <?php elseif (!empty($discusion['fecha_inicio']) && !empty($discusion['fecha_fin'])): ?>
                    <small class="text-muted ml-2 d-none d-md-inline" id="chatRango">
                        <i class="fas fa-calendar-alt"></i>
                        <?= date('d/m H:i', strtotime($discusion['fecha_inicio'])) ?> → <?= date('d/m H:i', strtotime($discusion['fecha_fin'])) ?>
                    </small>
                <?php endif; ?>
            </div>
        </div>
        <div class="chat-header-actions">
            <button class="btn btn-sm btn-outline-info" id="btnEditar" title="Editar debate">
                <i class="fas fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-warning ml-1" id="btnCerrarReabrir" title="<?= $discusion['estado'] === 'abierta' ? 'Cerrar debate' : 'Reabrir debate con nueva fecha' ?>">
                <i class="fas <?= $discusion['estado'] === 'abierta' ? 'fa-lock' : 'fa-lock-open' ?>"></i>
                <span class="d-none d-md-inline"><?= $discusion['estado'] === 'abierta' ? 'Cerrar' : 'Reabrir' ?></span>
            </button>
            <button class="btn btn-sm btn-outline-danger ml-1" id="btnAnular" title="Eliminar debate">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <?php if ($discusion['descripcion']): ?>
        <div class="chat-descripcion">
            <i class="fas fa-info-circle mr-1"></i><?= htmlspecialchars($discusion['descripcion']) ?>
        </div>
    <?php endif; ?>

    <div class="chat-messages" id="chatMessages">
        <div class="chat-loading"><i class="fas fa-spinner fa-spin"></i> Cargando mensajes...</div>
    </div>

    <div class="chat-typing" id="typingIndicator" style="display:none;"></div>

    <div class="chat-input-wrapper" id="chatInputWrapper" style="<?= $discusion['estado'] === 'cerrada' ? 'display:none' : '' ?>">
        <form class="chat-input-form no-warn" id="chatForm" method="POST">
            <input type="hidden" name="discusion_id" value="<?= $discusion['id'] ?>">
            <input type="hidden" name="_csrf_token" value="<?= generateCsrfToken() ?>">
            <textarea class="chat-input" id="chatInput" name="mensaje" placeholder="Escribe tu mensaje..." autocomplete="off" rows="1"></textarea>
            <button type="submit" class="chat-send" id="chatSend"><i class="fas fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<script>
<?php
$finPrevio = !empty($discusion['fecha_fin']) ? strtotime((string) $discusion['fecha_fin']) : 0;
$finSugerido = $finPrevio > time() ? date('Y-m-d\TH:i', $finPrevio) : date('Y-m-d\TH:i', strtotime('+3 days'));
$iniPrevio = !empty($discusion['fecha_inicio']) ? strtotime((string) $discusion['fecha_inicio']) : 0;
$inicioSugerido = $iniPrevio > time() ? date('Y-m-d\TH:i', $iniPrevio) : date('Y-m-d\TH:i');
?>
var CHAT_CONFIG = {
    discusionId: <?= $discusion['id'] ?>,
    semanaId: <?= $semana['id'] ?>,
    userId: <?= $_SESSION['user_id'] ?>,
    userRol: 'docente',
    baseUrl: '<?= BASE_URL ?>',
    isClosed: <?= $discusion['estado'] === 'cerrada' ? 'true' : 'false' ?>,
    fechaInicioSugerida: '<?= $inicioSugerido ?>',
    fechaFinSugerida: '<?= $finSugerido ?>',
    aprobaciones: <?= json_encode((object) ($aprobaciones ?? []), JSON_UNESCAPED_UNICODE) ?>,
    initialMessages: <?= json_encode($mensajes, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= BASE_URL ?>js/discusion.js?v=<?= filemtime(ROOT_PATH . 'public/js/discusion.js') ?>"></script>
<script>
document.getElementById('btnAnular')?.addEventListener('click', function() {
    Swal.fire({
        title: '¿Eliminar este debate?',
        text: 'Se eliminarán todos los mensajes. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar'
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('discusion_id', CHAT_CONFIG.discusionId);
            fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
            fetch(BASE_URL + 'docente/anularDiscusion', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1200, showConfirmButton: false })
                    .then(function(){ window.location.href = BASE_URL + 'docente/discusiones?semana_id=<?= $semana["id"] ?>'; });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                }
            })
            .catch(function(){ Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar.' }); });
        }
    });
});
document.getElementById('btnEditar')?.addEventListener('click', function() {
    Swal.fire({
        title: 'Editar debate',
        html:
            '<input id="swal-titulo" class="swal2-input" placeholder="Título" value="<?= addslashes(html_entity_decode($discusion["titulo"])) ?>" style="font-size:.9rem">' +
            '<textarea id="swal-descripcion" class="swal2-textarea" placeholder="Descripción (opcional)" style="font-size:.9rem"><?= addslashes(html_entity_decode($discusion["descripcion"] ?? "")) ?></textarea>',
        showCancelButton: true,
        confirmButtonText: 'Guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d6efd',
        preConfirm: function() {
            var t = document.getElementById('swal-titulo').value.trim();
            if (!t) { Swal.showValidationMessage('El título es obligatorio'); return false; }
            return { titulo: t, descripcion: document.getElementById('swal-descripcion').value.trim() };
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            var fd = new FormData();
            fd.append('discusion_id', CHAT_CONFIG.discusionId);
            fd.append('titulo', result.value.titulo);
            fd.append('descripcion', result.value.descripcion);
            fd.append('_csrf_token', document.querySelector('input[name="_csrf_token"]').value);
            fetch(BASE_URL + 'docente/editarDiscusion', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d.success) {
                    Swal.fire({ icon: 'success', title: 'Actualizado', timer: 1200, showConfirmButton: false })
                    .then(function(){ location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                }
            })
            .catch(function(){ Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar.' }); });
        }
    });
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover focus', boundary: 'window' });
});
</script>
