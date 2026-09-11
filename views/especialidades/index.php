<?php
$pageTitle = 'Gestión de Especialidades';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-bookmark me-2 text-primary"></i>Gestión de Especialidades</h6>
    <button class="btn btn-primary btn-sm" onclick="modalEspecialidad()"><i class="fa-solid fa-plus me-1"></i> Nueva Especialidad</button>
  </div>
  <div class="table-responsive">
    <table id="tablaEspecialidades" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalEspecialidadForm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalEspecTitulo"><i class="fa-solid fa-bookmark me-2 text-primary"></i>Nueva Especialidad</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nombre</label>
          <input type="text" class="form-control" id="especNombre" placeholder="Ej: Matemáticas, Redes, etc.">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Estado</label>
          <select class="form-select" id="especActivo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
        </div>
        <input type="hidden" id="especId" value="">
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarEspecialidad()"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadEspecialidades());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
