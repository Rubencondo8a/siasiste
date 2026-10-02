<form action="<?= BASE_URL ?>docente/guardarHorario" method="POST" id="formularioHorario">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= $horario['id'] ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Curso <span class="text-danger">*</span></label>
            <select name="curso_id" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php foreach ($cursos as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $horario['curso_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Día <span class="text-danger">*</span></label>
            <select name="dia" class="form-control" required>
                <?php foreach (['Lunes','Martes','Miercoles','Jueves','Viernes'] as $d): ?>
                    <option value="<?= $d ?>" <?= $d === $horario['dia'] ? 'selected' : '' ?>><?= $d ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label>Hora Inicio <span class="text-danger">*</span></label>
            <input type="time" name="hora_inicio" class="form-control" value="<?= htmlspecialchars(substr($horario['hora_inicio'],0,5)) ?>" required>
        </div>
        <div class="col-md-6 mb-3">
            <label>Hora Fin <span class="text-danger">*</span></label>
            <input type="time" name="hora_fin" class="form-control" value="<?= htmlspecialchars(substr($horario['hora_fin'],0,5)) ?>" required>
        </div>
    </div>
    <div class="mb-3">
        <label>Ambiente</label>
        <select name="ambiente_id" class="form-control">
            <option value="">Ninguno</option>
            <?php foreach ($ambientes as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $a['id'] == $horario['ambiente_id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
