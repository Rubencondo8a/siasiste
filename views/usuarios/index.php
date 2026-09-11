<?php
$pageTitle = 'Gestión de Usuarios';
require __DIR__ . '/../layouts/header.php';
?>

<div class="table-card">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-user-gear me-2 text-primary"></i>Gestión de Usuarios</h6>
    <button class="btn btn-primary btn-sm" onclick="modalUsuario()"><i class="fa-solid fa-plus me-1"></i> Nuevo Usuario</button>
  </div>
  <div class="table-responsive">
    <table id="tablaUsuarios" class="table table-hover w-100">
      <thead><tr><th>#</th><th>Usuario</th><th>Nombre Completo</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalUsuarioForm" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalUsuarioTitulo">Nuevo Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Usuario</label><input type="text" class="form-control" placeholder="Nombre de usuario" name="usuario"></div>
          <div class="col-md-6"><label class="form-label">Nombre Completo</label><input type="text" class="form-control" placeholder="Apellidos y nombres completos" name="nombre"></div>
          <div class="col-12"><label class="form-label">Correo Electrónico</label><input type="email" class="form-control" placeholder="correo@iespvah.edu.pe" name="email"></div>
          <div class="col-md-6"><label class="form-label">Rol</label>
            <select class="form-select" name="role_id">
              <option value="">— Seleccionar rol —</option>
            </select>
          </div>
          <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="activo"><option>Activo</option><option>Inactivo</option></select></div>
          <div class="col-md-6">
            <label class="form-label">Contraseña</label>
            <div class="pass-wrap">
              <input type="password" class="form-control" id="passUsuario" name="contrasena" placeholder="Contraseña">
              <button type="button" class="pass-toggle" id="btnPass1" onclick="togglePass('passUsuario','btnPass1')"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Confirmar Contraseña</label>
            <div class="pass-wrap">
              <input type="password" class="form-control" id="passConfirm" name="contrasena_confirm" placeholder="Repetir contraseña">
              <button type="button" class="pass-toggle" id="btnPass2" onclick="togglePass('passConfirm','btnPass2')"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
          </div>
          <input type="hidden" name="id" value="">
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarUsuario()"><i class="fa-solid fa-floppy-disk me-1"></i> Registrar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => loadUsuarios());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
