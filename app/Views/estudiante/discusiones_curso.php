<?php
$pageTitle = 'Debates — ' . htmlspecialchars($curso['nombre'] ?? '');
$extraHead = '<link rel="stylesheet" href="' . BASE_URL . 'css/discusion.css?v=' . filemtime(ROOT_PATH . 'public/css/discusion.css') . '">';
require __DIR__ . '/../layouts/header.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-comments text-primary mr-2"></i>Debates del Curso</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= BASE_URL ?>estudiante/misCursos" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Volver a Mis Cursos
                </a>
            </div>
        </div>
        <small class="text-muted"><?= htmlspecialchars($curso['nombre'] ?? '') ?></small>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <?php if (empty($discusiones)): ?>
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-comments fa-3x mb-3" style="opacity:.2"></i>
                    <p>No hay debates en este curso todavía.</p>
                </div>
            </div>
        <?php else: ?>
            <?php
            $agrupados = [];
            foreach ($discusiones as $d) {
                $key = 'Semana ' . $d['numero_semana'] . ' — ' . $d['semana_nombre'];
                $agrupados[$key][] = $d;
            }
            foreach ($agrupados as $grupo => $items): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-calendar-week mr-1"></i><?= htmlspecialchars($grupo) ?></h6>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($items as $d): ?>
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
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
