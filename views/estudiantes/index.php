<?php
$pageTitle = 'Gestión de Estudiantes';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px">Lista de Estudiantes</h6>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success btn-sm" onclick="modalImportar('estudiantes')"><i class="fa-solid fa-upload me-1"></i> Carga Masiva</button>
      <button class="btn btn-primary btn-sm" onclick="modalEstudiante()"><i class="fa-solid fa-plus me-1"></i> Nuevo Estudiante</button>
    </div>
  </div>
  <div class="row g-2 mb-3">
    <div class="col-12 col-md-6">
      <select class="form-select select2-programa" id="filtroPrograma" onchange="aplicarFiltrosEstudiantes()">
        <option value="">-- Todos los programas --</option>
      </select>
    </div>
    <div class="col-12 col-md-2">
      <select class="form-select select2-grado" id="filtroGrado" onchange="aplicarFiltrosEstudiantes()">
        <option value="">Ciclo</option>
        <option>1° Ciclo</option><option>2° Ciclo</option><option>3° Ciclo</option><option>4° Ciclo</option><option>5° Ciclo</option><option>6° Ciclo</option>
      </select>
    </div>
  </div>
  <div class="table-responsive">
    <table id="tablaEstudiantes" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Apellidos y Nombres</th><th>DNI</th><th>Código</th><th>Ciclo</th><th>Sección</th><th>Semestre</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalEstudianteForm" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalEstudianteTitulo">Nuevo Estudiante</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-2">
          <div class="col-md-3"><label class="form-label">Código</label><input type="text" class="form-control" placeholder="EST-2026-001" name="codigo"></div>
          <div class="col-md-3"><label class="form-label">DNI</label><input type="text" class="form-control" placeholder="Número de DNI" maxlength="8" name="dni"></div>
          <div class="col-md-3"><label class="form-label">Ciclo</label><select class="form-select select2-modal-grado" name="grado"><option value="">Seleccionar</option><option>1° Ciclo</option><option>2° Ciclo</option><option>3° Ciclo</option><option>4° Ciclo</option><option>5° Ciclo</option><option>6° Ciclo</option></select></div>
          <div class="col-md-3"><label class="form-label">Sección</label><select class="form-select" name="seccion"><option value="">Seleccionar</option><option>A</option><option>B</option><option>C</option></select></div>
          <div class="col-md-4"><label class="form-label">Apellido Paterno</label><input type="text" class="form-control" placeholder="Apellido paterno" name="apellido_paterno"></div>
          <div class="col-md-4"><label class="form-label">Apellido Materno</label><input type="text" class="form-control" placeholder="Apellido materno" name="apellido_materno"></div>
          <div class="col-md-4"><label class="form-label">Nombres</label><input type="text" class="form-control" placeholder="Nombres completos" name="nombre"></div>
          <div class="col-md-3"><label class="form-label">Fecha Nacimiento</label><input type="date" class="form-control" name="fecha_nacimiento"></div>
          <div class="col-md-3"><label class="form-label">Teléfono</label><input type="text" class="form-control" placeholder="Número de contacto" name="telefono"></div>
          <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" placeholder="correo@estudiante.edu.pe" name="email"></div>
          <div class="col-md-8"><label class="form-label">Programa</label><select class="form-select" name="programa_id"><option value="">Seleccionar programa</option></select></div>
          <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
          <input type="hidden" name="id" value=""></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarEstudiante()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
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
  loadEstudiantes();
  initSelect2();
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
