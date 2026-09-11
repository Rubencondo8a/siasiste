<?php
$pageTitle = 'Gestión de Docentes';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px">Lista de Docentes</h6>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success btn-sm" onclick="modalImportar('docentes')"><i class="fa-solid fa-upload me-1"></i> Carga Masiva</button>
      <button class="btn btn-primary btn-sm" onclick="modalDocente()"><i class="fa-solid fa-plus me-1"></i> Nuevo Docente</button>
    </div>
  </div>
  <div class="table-responsive">
    <table id="tablaDocentes" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Apellidos y Nombres</th><th>DNI</th><th>Especialidad</th><th>Teléfono</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalDocenteForm" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalDocTitulo">Nuevo Docente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Apellidos</label><input type="text" class="form-control" placeholder="Apellidos completos" name="apellido"></div>
          <div class="col-md-6"><label class="form-label">Nombres</label><input type="text" class="form-control" placeholder="Nombres completos" name="nombre"></div>
          <div class="col-md-6"><label class="form-label">DNI</label><input type="text" class="form-control" placeholder="Número de DNI" maxlength="8" name="dni"></div>
          <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" placeholder="correo@iespvah.edu.pe" name="email"></div>
          <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" class="form-control" placeholder="Número de contacto" name="telefono"></div>
          <div class="col-md-6"><label class="form-label">Especialidad</label><select class="form-select select2-especialidad" name="especialidad_id"><option value="">Seleccionar</option></select></div>
          <input type="hidden" name="id" value=""></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarDocente()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCursosDocente" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="fa-solid fa-book-open me-2 text-primary"></i>Cursos del Docente</h5>
          <small class="text-muted" id="modalCursosNombre" style="font-size:12.5px;"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="tablaCursosDocente">
            <thead style="background:#f8fafc;">
              <tr><th style="font-size:11.5px;">Unidad Didáctica</th><th style="font-size:11.5px;">Ciclo</th><th style="font-size:11.5px;">Sección</th><th style="font-size:11.5px;">Horario</th><th style="font-size:11.5px;">Aula</th><th style="font-size:11.5px;">Hrs/Sem</th></tr>
            </thead>
            <tbody id="bodyCursosDocente"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalImportar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalImportarTitulo">Carga Masiva</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="alert alert-info py-2 mb-3" style="font-size:13px">
          <i class="fa-solid fa-circle-info me-1"></i> El archivo debe ser CSV con UTF-8. Descarga la plantilla para el formato correcto.
        </div>
        <div class="d-flex gap-2 mb-3">
          <button class="btn btn-outline-primary btn-sm" id="btnDescargarPlantilla"><i class="fa-solid fa-download me-1"></i> Descargar Plantilla</button>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Seleccionar archivo CSV</label>
          <input type="file" class="form-control" id="csvFileInput" accept=".csv">
        </div>
        <div id="csvPreviewArea" style="display:none;">
          <label class="form-label fw-semibold">Vista previa (primeras 5 filas):</label>
          <div class="table-responsive" style="max-height:200px;overflow:auto;border:1px solid #e2e8f0;border-radius:8px;">
            <table class="table table-sm table-bordered mb-0" style="font-size:12px">
              <thead class="table-light" id="csvPreviewHead"></thead>
              <tbody id="csvPreviewBody"></tbody>
            </table>
          </div>
          <div class="mt-2 text-muted" style="font-size:12px" id="csvFileInfo"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" id="btnImportarCSV" disabled><i class="fa-solid fa-upload me-1"></i> Importar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  loadDocentes();
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
