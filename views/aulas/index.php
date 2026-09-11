<?php
$pageTitle = 'Gestión de Aulas';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-building me-2 text-primary"></i>Gestión de Aulas</h6>
    <?php if (in_array($user['rol'] ?? '', ['admin','coordinador'])): ?>
    <button class="btn btn-primary btn-sm" onclick="modalAula()"><i class="fa-solid fa-plus me-1"></i> Nueva Aula</button>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table id="tablaAulas" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Nombre / N°</th><th>Capacidad</th><th>Piso / Ubic.</th><th>Tipo</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalAulaForm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalAulaTitulo">Nueva Aula</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-8"><label class="form-label">Nombre / Número de Aula</label><input type="text" class="form-control" placeholder="Ej: Aula 101, Lab. Informática 1" name="nombre"></div>
          <div class="col-md-4"><label class="form-label">Capacidad</label><input type="number" class="form-control" placeholder="N° alumnos" min="1" max="200" name="capacidad"></div>
          <div class="col-md-8"><label class="form-label">Piso / Ubicación</label><input type="text" class="form-control" placeholder="Ej: Piso 2 — Bloque B" name="edificio"></div>
          <div class="col-md-4"><label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
              <option value="">Seleccionar</option>
              <option>Teórica</option>
              <option>Laboratorio</option>
              <option>Taller</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="activo"><option>Activo</option><option>Inactivo</option></select></div>
          <input type="hidden" name="id" value=""></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarAula()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadAulas());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
