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
                <a href="<?= BASE_URL ?>estudiante/misCursos" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Volver al Curso
                </a>
            </div>
        </div>
        <small class="text-muted"><?= htmlspecialchars($curso['nombre'] ?? '') ?> — <?= htmlspecialchars($semana['nombre'] ?? '') ?></small>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php if (empty($discusiones)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-comments fa-3x mb-3" style="opacity:.2"></i>
                    <p>No hay debates en esta semana todavía.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($discusiones as $d): ?>
                <a href="<?= BASE_URL ?>estudiante/chatDiscusion?discusion_id=<?= $d['id'] ?>"
                   class="discusion-item <?= $d['estado'] === 'cerrada' ? 'cerrada' : 'abierta' ?>">
                    <div class="discusion-item-left">
                        <div class="discusion-item-icon">
                            <i class="fas <?= $d['estado'] === 'abierta' ? 'fa-comments text-success' : 'fa-lock text-secondary' ?>"></i>
                        </div>
                    </div>
                    <div class="discusion-item-body">
                        <div class="discusion-item-titulo"><?= htmlspecialchars($d['titulo']) ?></div>
                        <div class="discusion-item-meta">
                            <span><i class="fas fa-message mr-1"></i><?= $d['total_mensajes'] ?> mensaje<?= $d['total_mensajes'] != 1 ? 's' : '' ?></span>
                            <span><i class="fas fa-clock mr-1"></i><?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="discusion-item-right">
                        <?php if ($d['mensajes_nuevos'] > 0): ?>
                            <span class="badge badge-danger"><?= $d['mensajes_nuevos'] ?> nuevos</span>
                        <?php endif; ?>
                        <?php if ($d['estado'] === 'abierta'): ?>
                            <span class="badge badge-success">Abierto</span>
                        <?php else: ?>
                            <span class="badge badge-secondary">Cerrado</span>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
