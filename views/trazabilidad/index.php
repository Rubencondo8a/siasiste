<?php
$pageTitle = 'Trazabilidad Académica';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card mb-3">
  <h6 class="fw-bold mb-3" style="font-size:15px"><i class="fa-solid fa-diagram-project me-2 text-primary"></i>Trazabilidad Académica</h6>
  <div class="row g-2 align-items-end">
    <div class="col-12 col-md-6">
      <label class="form-label fw-semibold" style="font-size:13px">Buscar estudiante</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
        <input type="text" class="form-control" id="trazabilidadSearch" placeholder="Código, nombre o apellido..." oninput="buscarEstudianteTrazabilidad()" onkeydown="if(event.key==='Escape') limpiarTrazabilidad()" style="font-size:13px">
        <button class="btn btn-outline-secondary" type="button" id="btnClearTrazabilidad" onclick="limpiarTrazabilidad()" title="Limpiar búsqueda" style="display:none"><i class="fa-solid fa-xmark"></i></button>
      </div>
      <div id="trazabilidadResults" style="display:none;position:absolute;z-index:100;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);max-height:240px;overflow:auto;width:calc(100% - 2rem);margin-top:4px;"></div>
    </div>
    <div class="col-12 col-md-6">
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <button class="btn btn-primary btn-sm" id="btnAvanzarSemestreTraz" style="display:none" onclick="avanzarSemestreTraz()"><i class="fa-solid fa-circle-arrow-up me-1"></i> Avanzar Semestre</button>
        <button class="btn btn-danger btn-sm" id="btnRetirarTraz" style="display:none" onclick="retirarEstudianteTraz()"><i class="fa-solid fa-circle-xmark me-1"></i> Retirar</button>
        <button class="btn btn-outline-secondary btn-sm" id="btnLimpiarTrazabilidad" style="display:none" onclick="limpiarTrazabilidad()"><i class="fa-solid fa-eraser me-1"></i> Limpiar</button>
      </div>
    </div>
  </div>
</div>

<div id="trazabilidadEmpty" class="text-center py-5">
  <i class="fa-solid fa-diagram-project" style="font-size:48px;color:#cbd5e1"></i>
  <h6 class="text-muted mt-3" style="font-size:14px">Selecciona un estudiante para ver su trazabilidad</h6>
  <p class="text-muted" style="font-size:12.5px">Usa el buscador arriba para encontrar un estudiante por código, nombre o apellido.</p>
</div>

<div id="trazabilidadContent" style="display:none">
  <div class="row g-3 mb-3">
    <div class="col-12 col-lg-4">
      <div class="table-card h-100">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:48px;height:48px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="fa-solid fa-user text-primary" style="font-size:22px"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-0" id="trazNombre" style="font-size:15px">—</h6>
            <small class="text-muted" id="trazCodigo" style="font-size:12px">—</small>
          </div>
        </div>
        <div style="font-size:13px">
          <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Programa</span><span class="fw-semibold" id="trazPrograma">—</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Ciclo actual</span><span class="fw-semibold" id="trazCiclo">—</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Sección</span><span class="fw-semibold" id="trazSeccion">—</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Estado</span><span id="trazEstado">—</span></div>
          <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Email</span><span class="fw-semibold" id="trazEmail">—</span></div>
          <div class="d-flex justify-content-between py-1"><span class="text-muted">Teléfono</span><span class="fw-semibold" id="trazTelefono">—</span></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-8">
      <div class="table-card h-100">
        <h6 class="fw-bold mb-3" style="font-size:14px"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Historial de Matrículas</h6>
        <div id="trazTimeline"></div>
      </div>
    </div>
  </div>
  <div class="table-card">
    <h6 class="fw-bold mb-3" style="font-size:14px"><i class="fa-solid fa-fingerprint me-2 text-primary"></i>Registro de Asistencias</h6>
    <div class="table-responsive">
      <table class="table table-hover w-100" id="tablaTrazAsistencia">
        <thead><tr><th>#</th><th>Fecha</th><th>Unidad Didáctica</th><th>Estado</th></tr></thead>
        <tbody id="trazAsistenciaBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  const urlParams = new URLSearchParams(window.location.search);
  const estudianteId = urlParams.get('estudiante_id');
  if (estudianteId) {
    try {
      const res = await api('trazabilidad', 'perfil', estudianteId);
      if (res && !res.error) {
        seleccionarEstudianteTraz(estudianteId, res.nombre, res.apellido, res.codigo,
          res.programa_nombre, res.grado, res.seccion, res.semestre_actual,
          res.matricula_estado, res.email, res.telefono);
      }
    } catch(e) {}
  }
  // toggle clear X en input
  const inp = document.getElementById('trazabilidadSearch');
  const btnClear = document.getElementById('btnClearTrazabilidad');
  const btnLimpiar = document.getElementById('btnLimpiarTrazabilidad');
  if (inp && btnClear) {
    inp.addEventListener('input', () => {
      const has = inp.value.trim().length > 0;
      btnClear.style.display = has ? '' : 'none';
      if (has) btnLimpiar.style.display = '';
    });
  }
  // mostrar Limpiar cuando se selecciona
  const _origSel = window.seleccionarEstudianteTraz;
  if (_origSel) {
    window.seleccionarEstudianteTraz = async function(...args){
      const r = await _origSel.apply(this, args);
      document.getElementById('btnClearTrazabilidad').style.display = '';
      document.getElementById('btnLimpiarTrazabilidad').style.display = '';
      return r;
    };
  }
});

function limpiarTrazabilidad(){
  _trazEstudianteId = null;
  const inp = document.getElementById('trazabilidadSearch');
  const box = document.getElementById('trazabilidadResults');
  if (inp) inp.value = '';
  if (box) { box.style.display='none'; box.innerHTML=''; }
  document.getElementById('trazabilidadContent').style.display='none';
  document.getElementById('trazabilidadEmpty').style.display='block';
  document.getElementById('btnAvanzarSemestreTraz').style.display='none';
  document.getElementById('btnRetirarTraz').style.display='none';
  document.getElementById('btnLimpiarTrazabilidad').style.display='none';
  document.getElementById('btnClearTrazabilidad').style.display='none';
  const url = new URL(window.location);
  url.searchParams.delete('estudiante_id');
  history.replaceState({}, '', url);
  if (inp) inp.focus();
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
