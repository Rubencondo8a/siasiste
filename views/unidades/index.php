<?php
$pageTitle = 'Unidades Didácticas';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-book me-2 text-primary"></i>Unidades Didácticas</h6>
    <?php if (in_array($user['rol'] ?? '', ['admin','coordinador'])): ?>
    <button class="btn btn-primary btn-sm" onclick="modalUnidad()"><i class="fa-solid fa-plus me-1"></i> Nueva Unidad</button>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table id="tablaUnidades" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Nombre de Unidad</th><th>Programa(s)</th><th>Ciclo</th><th>Sección</th><th>Hrs/Sem</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalUnidadForm" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalUnidadTitulo">Nueva Unidad Didáctica</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Nombre de la Unidad</label><input type="text" class="form-control" placeholder="Nombre de la unidad didáctica" name="nombre"></div>
          <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" rows="2" placeholder="Descripción de la unidad" name="descripcion"></textarea></div>
          <div class="col-md-6">
            <label class="form-label">Programa(s)</label>
            <select class="form-select select2-programa-unidad" name="programas[]" multiple>
              <option value="">— Seleccionar programas —</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Docentes Asignados</label>
            <select class="form-select select2-docente-unidad" name="docentes[]" multiple>
              <option value="">— Seleccionar docentes —</option>
            </select>
          </div>
          <div class="col-md-3"><label class="form-label">Ciclo</label><select class="form-select" name="semestre"><option value="1">1° Ciclo</option><option value="2">2° Ciclo</option><option value="3">3° Ciclo</option><option value="4">4° Ciclo</option><option value="5">5° Ciclo</option><option value="6">6° Ciclo</option></select></div>
          <div class="col-md-3"><label class="form-label">Sección</label><select class="form-select" name="seccion"><option value="">—</option><option>A</option><option>B</option><option>C</option><option>D</option><option>E</option><option>Única</option></select></div>
          <div class="col-md-3"><label class="form-label">Hrs/Sem</label><input type="number" class="form-control" placeholder="Ej: 4" min="1" max="40" name="horas_semanales"></div>
          <div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
          <input type="hidden" name="id" value="">
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarUnidad()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadUnidades());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
