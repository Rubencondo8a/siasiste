<?php
$pageTitle = 'Gestión de Horarios';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card mb-3">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-calendar-days me-2 text-primary"></i>Gestión de Horarios</h6>
    <div class="d-flex gap-2">
      <select class="form-select form-select-sm" id="filtroDocenteHorario" style="width:200px;font-size:13px;">
        <option value="">— Todos los docentes —</option>
      </select>
      <?php if (in_array($user['rol'] ?? '', ['admin','coordinador'])): ?>
      <button class="btn btn-primary btn-sm" onclick="modalHorario()"><i class="fa-solid fa-plus me-1"></i> Nuevo Horario</button>
      <?php endif; ?>
    </div>
  </div>
  <div class="horario-calendar" id="horarioCalendar">
    <div class="calendar-loading text-center py-5 text-muted">
      <i class="fa-solid fa-hourglass-half fs-3 d-block mb-2"></i> Cargando horarios...
    </div>
  </div>
</div>

<div class="modal fade" id="modalHorarioForm" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalHorarioTitulo">Nuevo Horario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Docente Responsable</label>
            <select class="form-select" id="selectDocenteHorario" name="docente_id" onchange="filtrarUnidadesPorDocente(this.value)">
              <option value="">— Seleccionar docente —</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Unidad Didáctica <small class="text-muted fw-normal">(filtra por docente)</small></label>
            <select class="form-select" id="selectUnidadHorario" name="unidad_id">
              <option value="">— Primero elige un docente —</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Aula</label>
            <select class="form-select" name="aula_id">
              <option value="">— Seleccionar aula —</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Día de la Semana</label>
            <select class="form-select" name="dia">
              <option value="">— Seleccionar día —</option>
              <option>Lunes</option><option>Martes</option><option>Miércoles</option><option>Jueves</option><option>Viernes</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Hora Inicio</label><input type="time" class="form-control" name="hora_inicio"></div>
          <div class="col-md-6"><label class="form-label">Hora Fin</label><input type="time" class="form-control" name="hora_fin"></div>
          <input type="hidden" name="id" value=""></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarHorario()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadHorarios());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
