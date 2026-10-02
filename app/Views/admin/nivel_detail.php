<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-hat-wizard mr-2"></i>Nivel de Magia</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-sm-2 text-center">
                <img src="<?= BASE_URL ?><?= htmlspecialchars($nivel['avatar']) ?>" alt="Avatar" class="img-fluid rounded-circle border" style="width:90px;height:90px;object-fit:cover;">
            </div>
            <div class="col-sm-10">
                <h4 class="mb-1"><i class="<?= htmlspecialchars($nivel['icono']) ?>" style="color:#4E37E6;"></i> <?= sanitize($nivel['nombre']) ?></h4>
                <span class="badge <?= htmlspecialchars($nivel['clase']) ?>"><?= sanitize($nivel['clave']) ?></span>
                <?php if ($nivel['estado']): ?>
                    <span class="badge badge-success ml-1">Activo</span>
                <?php else: ?>
                    <span class="badge badge-danger ml-1">Inactivo</span>
                <?php endif; ?>
                <?php if ($nivel['mensaje']): ?>
                    <p class="text-muted mb-0 mt-2"><i class="fas fa-quote-left mr-1"></i><?= sanitize($nivel['mensaje']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <dl class="row mb-0">
            <dt class="col-sm-4">Rango (orden)</dt>
            <dd class="col-sm-8"><?= $nivel['rango'] ?></dd>

            <dt class="col-sm-4">Rango de notas</dt>
            <dd class="col-sm-8"><?= number_format((float) $nivel['min_nota'], 2) ?> — <?= number_format((float) $nivel['max_nota'], 2) ?></dd>

            <dt class="col-sm-4">Maná</dt>
            <dd class="col-sm-8"><i class="fas fa-bolt" style="color: <?= htmlspecialchars($nivel['color_mana']) ?>;"></i> <?= $nivel['mana'] ?></dd>

            <dt class="col-sm-4">Color de maná</dt>
            <dd class="col-sm-8">
                <span class="d-inline-block align-middle rounded-circle mr-1" style="width:16px;height:16px;background:<?= htmlspecialchars($nivel['color_mana']) ?>;"></span>
                <code><?= htmlspecialchars($nivel['color_mana']) ?></code>
            </dd>

            <dt class="col-sm-4">Costo de donación</dt>
            <dd class="col-sm-8">
                <?php if ($nivel['costo_donacion'] !== null && (float) $nivel['costo_donacion'] > 0): ?>
                    <span class="badge badge-success">1/<?= round(1 / (float) $nivel['costo_donacion']) ?> de tu poder por maná</span>
                <?php else: ?>
                    <span class="badge badge-secondary">No puede donar maná</span>
                <?php endif; ?>
            </dd>

            <dt class="col-sm-4">Fecha de registro</dt>
            <dd class="col-sm-8"><?= formatDate($nivel['created_at']) ?></dd>
        </dl>

        <?php if (!empty($nivel['poderes'])): ?>
            <h6 class="mt-4"><i class="fas fa-magic mr-1"></i>Poderes</h6>
            <ul class="list-group">
                <?php foreach ($nivel['poderes'] as $poder): ?>
                    <li class="list-group-item">
                        <i class="<?= htmlspecialchars($poder['icono'] ?? 'fas fa-check') ?> mr-2" style="color:#E63777;"></i>
                        <?= sanitize($poder['texto'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
