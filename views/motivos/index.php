<?php
$pageTitle = 'Gestión de Motivos';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px">Gestión de Motivos</h6>
    <button class="btn btn-primary btn-sm" onclick="modalMotivo()"><i class="fa-solid fa-plus me-1"></i> Nuevo Motivo</button>
  </div>
  <div class="table-responsive">
    <table id="tablaMotivos" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Nombre</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalMotivoForm" tabindex="-1">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalMotivoTitle">Nuevo Motivo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-3">
        <input type="hidden" id="editMotivoId">
        <div class="mb-3"><label class="form-label">Nombre</label><input type="text" class="form-control" id="motivoNombre" maxlength="100"></div>
        <div class="mb-3"><label class="form-label">Descripción</label><textarea class="form-control" id="motivoDescripcion" rows="2" style="resize:none;"></textarea></div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="motivoActivo" checked>
          <label class="form-check-label" for="motivoActivo">Activo</label>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarMotivo()"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
async function loadMotivos() {
  if (_dtInstances['tablaMotivos']) { _dtInstances['tablaMotivos'].ajax.reload(); return; }
  initTabla('tablaMotivos', {
    ajax: { url: `${API_BASE}?route=motivos&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'descripcion', defaultContent: '—' },
      { data: 'activo', render: d => d ? '<span class="badge-status badge-active">Activo</span>' : '<span class="badge-status badge-inactive">Inactivo</span>' },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `
        <div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarMotivo(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarMotivo(${r.id})"><i class="fa-solid fa-trash-can"></i></button></div>
      `}
    ]
  });
}

function modalMotivo() {
  document.getElementById('editMotivoId').value = '';
  document.getElementById('motivoNombre').value = '';
  document.getElementById('motivoDescripcion').value = '';
  document.getElementById('motivoActivo').checked = true;
  document.getElementById('modalMotivoTitle').textContent = 'Nuevo Motivo';
  new bootstrap.Modal(document.getElementById('modalMotivoForm')).show();
}

function editarMotivo(id) {
  const row = _dtInstances['tablaMotivos'].rows().data().toArray().find(r => r.id === id);
  if (!row) return;
  document.getElementById('editMotivoId').value = row.id;
  document.getElementById('motivoNombre').value = row.nombre || '';
  document.getElementById('motivoDescripcion').value = row.descripcion || '';
  document.getElementById('motivoActivo').checked = !!row.activo;
  document.getElementById('modalMotivoTitle').textContent = 'Editar Motivo';
  new bootstrap.Modal(document.getElementById('modalMotivoForm')).show();
}

async function guardarMotivo() {
  const id = document.getElementById('editMotivoId').value;
  const nombre = document.getElementById('motivoNombre').value.trim();
  if (!nombre) { Swal.fire({ icon: 'warning', title: 'Nombre requerido' }); return; }
  const body = { nombre, descripcion: document.getElementById('motivoDescripcion').value.trim(), activo: document.getElementById('motivoActivo').checked ? 1 : 0 };
  try {
    const res = id ? await api('motivos', 'update', id, 'POST', body) : await api('motivos', 'store', '', 'POST', body);
    if (res.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalMotivoForm')).hide();
      Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
      loadMotivos();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.error });
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Error de conexión' });
  }
}

async function eliminarMotivo(id) {
  const result = await Swal.fire({ icon: 'warning', title: '¿Eliminar motivo?', text: 'Esta acción no se puede deshacer.', showCancelButton: true, confirmButtonText: 'Eliminar', confirmButtonColor: '#dc2626' });
  if (!result.isConfirmed) return;
  try {
    const res = await api('motivos', 'delete', id, 'POST');
    if (res.success) {
      Swal.fire({ icon: 'success', title: res.message, timer: 1500, showConfirmButton: false });
      loadMotivos();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.error });
    }
  } catch (e) {
    Swal.fire({ icon: 'error', title: 'Error de conexión' });
  }
}

document.addEventListener('DOMContentLoaded', () => loadMotivos());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>