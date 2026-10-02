<?php
$pageTitle = 'Chat — ' . htmlspecialchars($discusion['titulo'] ?? '');
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/discusion.css?v=' . filemtime(ROOT_PATH . 'public/css/discusion.css') . '">';
require __DIR__ . '/../layouts/header.php';
?>

<section class="content-header" style="display:none;"></section>

<div class="chat-wrapper">
    <div class="chat-header">
        <a href="<?= BASE_URL ?>estudiante/discusiones?semana_id=<?= $semana['id'] ?>" class="chat-back">
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
            </div>
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
var CHAT_CONFIG = {
    discusionId: <?= $discusion['id'] ?>,
    semanaId: <?= $semana['id'] ?>,
    userId: <?= $_SESSION['user_id'] ?>,
    userRol: 'estudiante',
    baseUrl: '<?= BASE_URL ?>',
    isClosed: <?= $discusion['estado'] === 'cerrada' ? 'true' : 'false' ?>,
    aprobaciones: <?= json_encode((object) ($aprobaciones ?? []), JSON_UNESCAPED_UNICODE) ?>,
    initialMessages: <?= json_encode($mensajes, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= BASE_URL ?>js/discusion.js?v=<?= filemtime(ROOT_PATH . 'public/js/discusion.js') ?>"></script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip({ trigger: 'hover focus', boundary: 'window' });
});
</script>
