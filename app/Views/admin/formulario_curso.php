<?php if (empty($isAjax)): ?>
<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../partials/messages.php'; ?>

<section class="content-header">
    <h1><?= $curso ? 'Editar Curso' : 'Nuevo Curso' ?></h1>
</section>
<section class="content">
<?php endif; ?>

    <div class="card">
    <div class="card-body">
        <form action="<?= BASE_URL ?>admin/guardarCurso" method="POST" <?= !empty($isAjax) ? 'data-ajax="true"' : '' ?>>
            <?php if ($curso): ?>
                <input type="hidden" name="id" value="<?= $curso['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="nombre">Nombre del Curso / Unidad Didáctica *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required
                               placeholder="Ej: Base de Datos, Matemáticas I"
                               value="<?= htmlspecialchars($curso['nombre'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="codigo">Código *</label>
                        <input type="text" class="form-control" id="codigo" name="codigo" required
                               placeholder="Ej: BD-101"
                               value="<?= htmlspecialchars($curso['codigo'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                          placeholder="Descripción del curso, unidad o módulo..."><?= htmlspecialchars($curso['descripcion'] ?? '') ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="carrera_id">Carrera / Programa *</label>
                        <select class="form-control" id="carrera_id" name="carrera_id" required>
                            <option value="">-- Seleccione carrera --</option>
                            <?php if (!empty($carreras)): ?>
                                <?php foreach ($carreras as $c): ?>
                                    <?php $totalCiclos = (int) ($c['total_ciclos'] ?? 6); ?>
                                    <option value="<?= $c['id'] ?>"
                                            data-total-ciclos="<?= $totalCiclos ?>"
                                            <?= ($curso['carrera_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['nombre']) ?> (<?= $totalCiclos ?> ciclos)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="plan_id">Plan de Estudios *</label>
                        <select class="form-control" id="plan_id" name="plan_id" required>
                            <option value="">-- Seleccione plan --</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ciclo">Ciclo / Semestre *</label>
                        <select class="form-control" id="ciclo" name="ciclo" required>
                            <option value="">-- Seleccione ciclo --</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="1" <?= ($curso['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                            <option value="0" <?= ($curso['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-3 d-flex" style="gap:8px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <?php if (!empty($isAjax)): ?>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>admin/cursos" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Cancelar
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    </div>

    <script>
    (function() {
        var carreraSelect = document.getElementById('carrera_id');
        var planSelect = document.getElementById('plan_id');
        var cicloSelect = document.getElementById('ciclo');
        if (!carreraSelect || !cicloSelect || !planSelect) return;

        var planesPorCarrera = <?= json_encode($planesPorCarrera ?? [], JSON_UNESCAPED_UNICODE) ?>;
        var currentCiclo = <?= json_encode($curso['ciclo'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
        var currentPlan = <?= json_encode($curso['plan_id'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

        function fillCiclos() {
            var opt = carreraSelect.options[carreraSelect.selectedIndex];
            var total = opt ? parseInt(opt.getAttribute('data-total-ciclos') || '6', 10) : 0;
            if (!total) total = 6;
            cicloSelect.innerHTML = '<option value="">-- Seleccione ciclo --</option>';
            for (var i = 1; i <= total; i++) {
                var o = document.createElement('option');
                o.value = i;
                o.textContent = i + (i === 1 ? 'er' : (i === 2 ? 'do' : (i === 3 ? 'er' : 'to'))) + ' Ciclo';
                cicloSelect.appendChild(o);
            }
            if (currentCiclo !== '') {
                cicloSelect.value = currentCiclo;
            }
        }

        function fillPlanes() {
            var carreraId = carreraSelect.value;
            var planes = planesPorCarrera[carreraId] || [];
            planSelect.innerHTML = '<option value="">-- Seleccione plan --</option>';
            planes.forEach(function(p) {
                var o = document.createElement('option');
                o.value = p.id;
                o.textContent = 'Plan ' + p.nombre;
                planSelect.appendChild(o);
            });
            if (currentPlan !== '') {
                planSelect.value = currentPlan;
            }
        }

        function onCarreraChange() {
            currentCiclo = '';
            currentPlan = '';
            fillPlanes();
            fillCiclos();
        }

        carreraSelect.addEventListener('change', onCarreraChange);

        fillPlanes();
        fillCiclos();
    })();
    </script>

<?php if (empty($isAjax)): ?>
</section>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<?php endif; ?>
