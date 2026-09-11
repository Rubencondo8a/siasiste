<?php
$pageTitle = 'Alertas Inteligentes';
require __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4 mb-4">
  <div class="col-sm-4">
    <div class="card shadow-sm border-0 text-center p-4 stat-card" style="border-left: 4px solid #dc3545;">
      <div class="fs-1 text-danger mb-1"><i class="fa-solid fa-octagon-exclamation"></i></div>
      <h6 class="text-danger">Alertas Críticas</h6>
      <h3 id="alertCriticas">0</h3>
      <small class="text-muted">3+ inasistencias seguidas</small>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card shadow-sm border-0 text-center p-4 stat-card" style="border-left: 4px solid #ffc107;">
      <div class="fs-1 text-warning mb-1"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <h6 class="text-warning">Alertas Moderadas</h6>
      <h3 id="alertModeradas">0</h3>
      <small class="text-muted">2 inasistencias seguidas</small>
    </div>
  </div>
  <div class="col-sm-4">
    <div class="card shadow-sm border-0 text-center p-4 stat-card" style="border-left: 4px solid #198754;">
      <div class="fs-1 text-success mb-1"><i class="fa-solid fa-circle-check"></i></div>
      <h6 class="text-success">Atendidas Hoy</h6>
      <h3 id="alertAtendidas">0</h3>
      <small class="text-muted">Resueltas hoy</small>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h6 class="mb-0"><i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>Alertas Activas</h6>
    <button class="btn btn-sm btn-outline-primary" onclick="loadAlerts()">
      <i class="fa-solid fa-arrows-rotate"></i> Actualizar
    </button>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Fecha</th>
            <th>Estudiante</th>
            <th>Grado/Sección</th>
            <th>Tipo</th>
            <th>Severidad</th>
            <th>Inasistencias</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <tr><td colspan="7" class="text-center text-muted">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white">
    <h6 class="mb-0"><i class="fa-solid fa-clock-rotate-left me-2"></i>Historial de Alertas Atendidas</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Fecha</th>
            <th>Estudiante</th>
            <th>Tipo</th>
            <th>Atendida por</th>
            <th>Fecha Atención</th>
          </tr>
        </thead>
        <tbody id="tableHistory">
          <tr><td colspan="5" class="text-center text-muted">Cargando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="atenderModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="fa-solid fa-circle-check me-2"></i>Atender Alerta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="atenderId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Acción tomada</label>
          <textarea class="form-control" id="atenderAccion" rows="3" placeholder="Describa la acción realizada..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success btn-sm" onclick="confirmAtender()">Marcar como Atendida</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="derivarModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="fa-solid fa-arrow-right me-2"></i>Derivar Alerta</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="derivarId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Derivar a</label>
          <select class="form-select" id="derivarDestino">
            <option value="tutor">Tutor</option>
            <option value="psicologo">Psicólogo</option>
            <option value="director">Dirección</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Observaciones</label>
          <textarea class="form-control" id="derivarObs" rows="3" placeholder="Motivo de la derivación..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning btn-sm" onclick="confirmDerivar()">Derivar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadAlerts());

function loadAlerts() {
  fetch(`${BASE_URL}/public/index.php?route=alertas&action=activas`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    const alerts = res.data || res.items || (Array.isArray(res) ? res : []);
    const criticas = alerts.filter(a => a.nivel === 'rojo').length;
    const moderadas = alerts.filter(a => a.nivel === 'amarillo').length;
    document.getElementById('alertCriticas').textContent = criticas;
    document.getElementById('alertModeradas').textContent = moderadas;
    document.getElementById('alertAtendidas').textContent = '0';

    const tbody = document.getElementById('tableBody');
    const active = alerts.filter(a => a.estado === 'activa');
    if (!active.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-success"><i class="fa-solid fa-circle-check me-1"></i>No hay alertas activas</td></tr>';
    } else {
      tbody.innerHTML = active.map(a => {
        const nivel = a.nivel || 'verde';
        const sevColor = nivel === 'rojo' ? 'danger' : (nivel === 'amarillo' ? 'warning' : 'success');
        const sevLabel = nivel === 'rojo' ? 'Crítica' : (nivel === 'amarillo' ? 'Moderada' : 'Leve');
        const fecha = (a.fecha_deteccion || '').substring(0,10).split('-').reverse().join('/');
        const estudiante = `${a.estudiante_apellido || ''}, ${a.estudiante_nombre || ''}`.trim();
        const gradoSeccion = `${a.grado || ''} ${a.seccion || ''}`.trim() || a.codigo || '—';
        return `
          <tr>
            <td>${fecha}</td>
            <td><strong>${estudiante}</strong></td>
            <td>${gradoSeccion}</td>
            <td><span class="badge bg-secondary">${a.derivado_a || '—'}</span></td>
            <td><span class="badge bg-${sevColor}">${sevLabel}</span></td>
            <td>${a.porcentaje_inasistencia ?? 0}%</td>
            <td>
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-success" onclick="openAtender(${a.id})" title="Atender"><i class="fa-solid fa-check"></i></button>
                <button class="btn btn-sm btn-outline-warning" onclick="openDerivar(${a.id})" title="Derivar"><i class="fa-solid fa-arrow-right"></i></button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    const histBody = document.getElementById('tableHistory');
    histBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin historial (solo activas)</td></tr>';
  })
  .catch((e) => {
    console.error('loadAlerts', e);
    document.getElementById('tableBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar alertas</td></tr>';
  });
}

function openAtender(id) {
  document.getElementById('atenderId').value = id;
  document.getElementById('atenderAccion').value = '';
  new bootstrap.Modal(document.getElementById('atenderModal')).show();
}

function confirmAtender() {
  const id = document.getElementById('atenderId').value;
  const fd = new FormData();
  fd.append('_token', CSRF_TOKEN);
  fetch(`${BASE_URL}/public/index.php?route=alertas&action=atender&id=${id}`, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('atenderModal')).hide();
      showAlert('success', 'Alerta atendida');
      loadAlerts();
    } else {
      showAlert('error', res.error || 'Error al atender');
    }
  })
  .catch(() => showAlert('error', 'Error de conexión'));
}

function openDerivar(id) {
  document.getElementById('derivarId').value = id;
  document.getElementById('derivarObs').value = '';
  new bootstrap.Modal(document.getElementById('derivarModal')).show();
}

function confirmDerivar() {
  const id = document.getElementById('derivarId').value;
  const destino = document.getElementById('derivarDestino').value;
  const obs = document.getElementById('derivarObs').value;
  const fd = new FormData();
  fd.append('derivado_a', destino);
  fd.append('observaciones', obs);
  fd.append('_token', CSRF_TOKEN);
  fetch(`${BASE_URL}/public/index.php?route=alertas&action=derivar&id=${id}`, {
    method: 'POST',
    body: fd,
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('derivarModal')).hide();
      showAlert('success', 'Alerta derivada');
      loadAlerts();
    } else {
      showAlert('error', res.error || 'Error al derivar');
    }
  })
  .catch(() => showAlert('error', 'Error de conexión'));
}
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>