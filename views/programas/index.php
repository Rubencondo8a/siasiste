<?php
$pageTitle = 'Programas de Estudio';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i>Programas de Estudio</h6>
    <button class="btn btn-primary btn-sm" onclick="modalPrograma()"><i class="fa-solid fa-plus me-1"></i> Nuevo Programa</button>
  </div>
  <div class="table-responsive">
    <table id="tablaProgramas" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Semestres</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalProgramaForm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalProgTitulo"><i class="fa-solid fa-graduation-cap me-2 text-primary"></i>Nuevo Programa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="mb-3">
          <label class="form-label fw-semibold">Nombre</label>
          <input type="text" class="form-control" id="progNombre" placeholder="Ej: Redes e Informática">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Descripción</label>
          <textarea class="form-control" id="progDescripcion" rows="3" placeholder="Descripción del programa de estudio"></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Duración (semestres)</label>
            <input type="number" class="form-control" id="progDuracion" min="1" max="12" value="6">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Estado</label>
            <select class="form-select" id="progActivo"><option value="1">Activo</option><option value="0">Inactivo</option></select>
          </div>
        </div>
        <input type="hidden" id="progId" value="">
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarPrograma()"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadProgramasAdmin());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
