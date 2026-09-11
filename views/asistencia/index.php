<?php
use App\Models\Programa;
use App\Models\Unidad;
use App\Models\Docente;
use App\Models\Motivo;

$pageTitle = 'Registro de Asistencias';
$userRole = $_SESSION['user_role'] ?? '';
$isDocente = $userRole === 'docente';
require __DIR__ . '/../layouts/header.php';

$programaModel = new Programa();
$unidadModel = new Unidad();
$docenteModel = new Docente();

$programas = $programaModel->findAll('nombre ASC');
$unidades = $unidadModel->findAll('nombre ASC');
foreach ($unidades as &$u) {
    $u['programa_ids'] = array_column($unidadModel->getProgramas($u['id']), 'id');
}
unset($u);

if ($isDocente) {
    $docente = $docenteModel->findByUsuarioId($_SESSION['user_id']);
    $docenteId = $docente ? $docente['id'] : 0;
    $unidades = $unidadModel->findByDocente($docenteId);
    foreach ($unidades as &$u) {
        $u['programa_ids'] = array_column($unidadModel->getProgramas($u['id']), 'id');
    }
    unset($u);
    $allProgIds = array_unique(array_merge(...array_map(fn($u) => $u['programa_ids'], $unidades)));
    $programas = array_values(array_filter($programas, fn($p) => in_array($p['id'], $allProgIds)));
}
?>
<style>
  #filtrosAsistencia .form-label { font-size:13px; margin-bottom:2px; font-weight:600; color:var(--text-secondary); }
</style>

  <div class="table-card">
  <div class="mb-3">
    <h6 class="fw-bold mb-3" style="font-size:15px">Registro de Asistencias</h6>
    <div class="row g-2 align-items-end" id="filtrosAsistencia">
      <div class="col-12 col-md-4">
        <label class="form-label">Programa de Estudios</label>
        <select class="form-select form-select-sm" id="programaAsistencia" onchange="filtrarUnidadesPorPrograma()">
          <option value="">Todos los programas</option>
          <?php foreach ($programas as $p): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Unidad Didáctica</label>
        <select class="form-select form-select-sm" id="unidad_didactica" onchange="onUnidadChange()">
          <option value="">Todas las unidades</option>
          <?php foreach ($unidades as $u): ?>
            <option value="<?= $u['id'] ?>" data-programa-ids="<?= implode(',', $u['programa_ids'] ?? []) ?>"><?= htmlspecialchars($u['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-4">
        <label class="form-label">Fecha</label>
        <input type="date" class="form-control form-control-sm" id="fechaAsistencia" onchange="onFechaAsistenciaChange()">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="tablaAsistencia" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Estudiante</th><th>Programa</th><th>Unidad</th><th>Fecha</th><th>Tardío</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalAccionAsistenciaForm" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold mb-0">Registrar Acción de Asistencia</h5>
          <small class="text-muted" id="modalAsistNombre" style="font-size:12.5px;"></small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
        <label class="form-label mb-2">Tipo de registro</label>
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-3"><button type="button" class="btn w-100 fw-bold btn-accion-asist" data-valor="Presente" onclick="seleccionarAccion(this)" style="background:#dcfce7;color:#16a34a;border:2px solid #16a34a;border-radius:8px;padding:10px;font-size:13px;"><i class="fa-solid fa-circle-check me-1"></i>Presente</button></div>
          <div class="col-6 col-md-3"><button type="button" class="btn w-100 fw-bold btn-accion-asist" data-valor="Tardanza" onclick="seleccionarAccion(this)" style="background:#fef3c7;color:#d97706;border:2px solid #d97706;border-radius:8px;padding:10px;font-size:13px;"><i class="fa-solid fa-clock me-1"></i>Tardanza</button></div>
          <div class="col-6 col-md-3"><button type="button" class="btn w-100 fw-bold btn-accion-asist" data-valor="Ausente" onclick="seleccionarAccion(this)" style="background:#fee2e2;color:#dc2626;border:2px solid #dc2626;border-radius:8px;padding:10px;font-size:13px;"><i class="fa-solid fa-circle-xmark me-1"></i>Ausente</button></div>
          <div class="col-6 col-md-3"><button type="button" class="btn w-100 fw-bold btn-accion-asist" data-valor="Justificado" onclick="seleccionarAccion(this)" style="background:#ede9fe;color:#7c3aed;border:2px solid #7c3aed;border-radius:8px;padding:10px;font-size:13px;"><i class="fa-solid fa-clipboard-check me-1"></i>Justificado</button></div>
        </div>
        <div id="bloqueMotivo" style="display:none;">
          <hr class="my-3" style="border-color:var(--border);">
          <label class="form-label">Motivo</label>
          <select class="form-select mb-2" id="motivoSelect" onchange="toggleOtroMotivo(this)">
            <option value="">— Seleccionar motivo —</option>
            <?php
            $motivoModel = new Motivo();
            $motivos = $motivoModel->findWhere('activo', 1);
            foreach ($motivos as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nombre']) ?></option>
            <?php endforeach; ?>
            <option value="otro">Otro (especificar)</option>
          </select>
          <div id="otroMotivoWrap" style="display:none;"><label class="form-label">Especificar motivo</label><textarea class="form-control" id="otroMotivoText" rows="2" placeholder="Describa el motivo..." style="resize:none;font-size:13.5px;"></textarea></div>
        </div>
        <div class="mt-3"><label class="form-label">Observaciones <span class="text-muted fw-normal">(opcional)</span></label><textarea class="form-control" id="obsAsist" rows="2" placeholder="Notas adicionales..." style="resize:none;font-size:13.5px;"></textarea></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarAccionAsistencia()"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar registro</button>
      </div>
    </div>
  </div>
</div>

<script>
async function filtrarUnidadesPorPrograma() {
  const programaId = document.getElementById('programaAsistencia').value;
  const sel = document.getElementById('unidad_didactica');
  const options = sel.querySelectorAll('option');
  let firstVisible = '';
  options.forEach(o => {
    if (!o.value) { o.hidden = false; return; }
    const ids = (o.dataset.programaIds || '').split(',').filter(Boolean);
    const match = !programaId || ids.includes(programaId);
    o.hidden = !match;
    if (match && !firstVisible) firstVisible = o.value;
  });
  if (!programaId) { sel.value = ''; loadAsistencia(); return; }
  if (!sel.value || sel.querySelector(`option[value="${sel.value}"]`)?.hidden) {
    sel.value = firstVisible || '';
  }
  await onUnidadChange();
}

async function onUnidadChange(){
  const unidad = document.getElementById('unidad_didactica').value;
  const fechaInput = document.getElementById('fechaAsistencia');
  if (unidad) {
    try {
      const res = await api('asistencia','ultimaFecha','','GET',null,`&unidad_id=${unidad}`);
      if (res.ultima_fecha) {
        fechaInput.value = res.ultima_fecha;
      } else {
        fechaInput.valueAsDate = new Date();
      }
    } catch(e){ fechaInput.valueAsDate = new Date(); }
  }
  loadAsistencia();
}

document.addEventListener('DOMContentLoaded', async function() {
  const f = document.getElementById('fechaAsistencia');
  const unidadSel = document.getElementById('unidad_didactica');
  // si docente ya tiene unidad preseleccionada (una sola), cargar su última fecha
  if (unidadSel && unidadSel.value) {
    await onUnidadChange();
  } else if (f) {
    f.valueAsDate = new Date();
    loadAsistencia();
  }
  // si solo hay una unidad visible para docente, auto-seleccionarla
  <?php if ($isDocente && count($unidades)===1): ?>
  if (unidadSel && !unidadSel.value) {
    unidadSel.value = '<?= $unidades[0]['id'] ?>';
    await onUnidadChange();
  }
  <?php endif; ?>
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>