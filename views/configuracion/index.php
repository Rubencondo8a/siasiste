<?php
$pageTitle = 'Configuración';
require __DIR__ . '/../layouts/header.php';
?>

<div class="mb-4">
  <h5 class="fw-bold mb-1" style="font-size:17px"><i class="fa-solid fa-gear me-2 text-primary"></i>Configuración del Sistema</h5>
  <p class="text-muted mb-0" style="font-size:13px">Administra los parámetros generales, perfil de usuario y preferencias del sistema.</p>
</div>
<div class="row g-3">
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalInstitucion()">
      <div class="config-icon" style="background:#dbeafe;color:#2563eb"><i class="fa-solid fa-building"></i></div>
      <div class="config-card-title">Datos de la Institución</div>
      <div class="config-card-desc">Nombre, dirección, UGEL, teléfono y logo del centro educativo.</div>
      <div class="config-card-footer"><span>Editar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalPerfil()">
      <div class="config-icon" style="background:#dcfce7;color:#16a34a"><i class="fa-solid fa-user-circle"></i></div>
      <div class="config-card-title">Mi Perfil</div>
      <div class="config-card-desc">Actualiza tus datos personales, correo y número de contacto.</div>
      <div class="config-card-footer"><span>Editar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalContrasena()">
      <div class="config-icon" style="background:#fef3c7;color:#d97706"><i class="fa-solid fa-key"></i></div>
      <div class="config-card-title">Cambiar Contraseña</div>
      <div class="config-card-desc">Actualiza tu contraseña de acceso al sistema de forma segura.</div>
      <div class="config-card-footer"><span>Cambiar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalAnio()">
      <div class="config-icon" style="background:#ede9fe;color:#7c3aed"><i class="fa-solid fa-calendar-days"></i></div>
      <div class="config-card-title">Año Académico</div>
      <div class="config-card-desc">Configura el año lectivo activo, fechas de inicio y fin del semestre.</div>
      <div class="config-card-footer"><span>Configurar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalApariencia()">
      <div class="config-icon" style="background:#ccfbf1;color:#0891b2"><i class="fa-solid fa-palette"></i></div>
      <div class="config-card-title">Apariencia</div>
      <div class="config-card-desc">Personaliza el color principal del sistema y las preferencias visuales.</div>
      <div class="config-card-footer"><span>Personalizar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <?php if ($user['rol'] === 'admin'): ?>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="config-card" onclick="abrirModalRoles()">
      <div class="config-icon" style="background:#fce7f3;color:#db2777"><i class="fa-solid fa-id-badge"></i></div>
      <div class="config-card-title">Roles del Sistema</div>
      <div class="config-card-desc">Administra los roles: agrega, edita o desactiva roles de usuario.</div>
      <div class="config-card-footer"><span>Gestionar</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </div>
  </div>
  <?php endif; ?>
  <div class="col-12 col-sm-6 col-lg-4">
    <a class="config-card config-card-danger" href="index.php?route=logout" style="text-decoration:none;display:block">
      <div class="config-icon" style="background:#fee2e2;color:#dc2626"><i class="fa-solid fa-right-from-bracket"></i></div>
      <div class="config-card-title" style="color:#dc2626">Cerrar Sesión</div>
      <div class="config-card-desc">Salir del sistema de forma segura y cerrar la sesión activa.</div>
      <div class="config-card-footer" style="color:#dc2626"><span>Salir</span><i class="fa-solid fa-arrow-right fs-5"></i></div>
    </a>
  </div>
</div>
<div class="table-card mt-4">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <div style="width:52px;height:52px;border-radius:50%;background:var(--accent-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;flex-shrink:0;"><?php echo strtoupper(substr($user['nombre'], 0, 1)); ?></div>
    <div>
      <div style="font-size:15px;font-weight:700;"><?php echo sanitize($user['nombre']); ?></div>
      <div style="font-size:12.5px;color:var(--text-muted);"><?php echo sanitize($user['email'] ?? $user['usuario']); ?> &nbsp;·&nbsp; <span class="badge-admin"><?php echo getNombreRol($user['rol']); ?></span></div>
    </div>
    <div class="ms-auto d-flex gap-2 flex-wrap">
      <button class="btn btn-sm btn-outline-primary" onclick="abrirModalPerfil()"><i class="fa-solid fa-pencil me-1"></i>Editar Perfil</button>
      <button class="btn btn-sm btn-outline-warning" onclick="abrirModalContrasena()"><i class="fa-solid fa-key me-1"></i>Cambiar Contraseña</button>
      <a class="btn btn-sm btn-outline-danger" href="index.php?route=logout"><i class="fa-solid fa-right-from-bracket me-1"></i>Cerrar Sesión</a>
    </div>
  </div>
</div>

<!-- MODAL INSTITUCIÓN -->
<div class="modal fade" id="modalInstitucion" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-building me-2 text-primary"></i>Datos de la Institución</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Nombre</label><input type="text" class="form-control" placeholder="Nombre de la institución"></div>
          <div class="col-12"><label class="form-label">Dirección</label><input type="text" class="form-control" placeholder="Dirección completa"></div>
          <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" class="form-control" placeholder="Número de contacto"></div>
          <div class="col-md-6"><label class="form-label">Correo Electrónico</label><input type="email" class="form-control" placeholder="correo@institucion.edu.pe"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarConfig('modalInstitucion','datos de la institución')"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL MI PERFIL -->
<div class="modal fade" id="modalPerfil" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-circle me-2 text-success"></i>Mi Perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="text-center mb-3">
          <div style="width:64px;height:64px;border-radius:50%;background:var(--accent-blue);color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;margin:0 auto 8px;"><?php echo strtoupper(substr($user['nombre'], 0, 1)); ?></div>
          <div style="font-size:12px;color:var(--text-muted);">Administrador del Sistema</div>
        </div>
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" value="<?php echo sanitize($user['nombre']); ?>"></div>
          <div class="col-12"><label class="form-label">Correo Electrónico</label><input type="email" name="email" class="form-control" value="<?php echo sanitize($user['email'] ?? $user['usuario']); ?>"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarConfig('modalPerfil','perfil')"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL CAMBIAR CONTRASEÑA -->
<div class="modal fade" id="modalCambiarPass" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-key me-2 text-warning"></i>Cambiar Contraseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Contraseña Actual</label>
            <div class="pass-wrap">
              <input type="password" class="form-control" id="passActual" placeholder="Ingresa tu contraseña actual">
              <button type="button" class="pass-toggle" id="btnPassA" onclick="togglePass('passActual','btnPassA')"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Nueva Contraseña</label>
            <div class="pass-wrap">
              <input type="password" class="form-control" id="passNueva" placeholder="Nueva contraseña">
              <button type="button" class="pass-toggle" id="btnPassB" onclick="togglePass('passNueva','btnPassB')"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Confirmar Nueva Contraseña</label>
            <div class="pass-wrap">
              <input type="password" class="form-control" id="passNuevaConfirm" placeholder="Repite la nueva contraseña">
              <button type="button" class="pass-toggle" id="btnPassC" onclick="togglePass('passNuevaConfirm','btnPassC')"><i class="fa-solid fa-eye-slash"></i></button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-warning text-white" onclick="guardarContrasena()"><i class="fa-solid fa-shield me-1"></i> Actualizar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL AÑO ACADÉMICO -->
<div class="modal fade" id="modalAnioAcademico" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-calendar-days me-2" style="color:#7c3aed"></i>Año Académico</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Año Académico</label><select class="form-select"><option>2026</option><option>2025</option><option>2024</option></select></div>
          <div class="col-md-6"><label class="form-label">Fecha de Inicio</label><input type="date" class="form-control" value="2026-03-03"></div>
          <div class="col-md-6"><label class="form-label">Fecha de Fin</label><input type="date" class="form-control" value="2026-07-31"></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarConfig('modalAnioAcademico','año académico')"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL APARIENCIA -->
<div class="modal fade" id="modalApariencia" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-palette me-2" style="color:#0891b2"></i>Apariencia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Color Principal del Sistema</label>
            <div class="d-flex gap-2 flex-wrap mt-1">
              <button class="color-swatch active" style="background:#2563eb" onclick="setColor('#2563eb',this)" title="Azul"></button>
              <button class="color-swatch" style="background:#dc2626" onclick="setColor('#dc2626',this)" title="Rojo"></button>
              <button class="color-swatch" style="background:#16a34a" onclick="setColor('#16a34a',this)" title="Verde"></button>
              <button class="color-swatch" style="background:#d97706" onclick="setColor('#d97706',this)" title="Ámbar"></button>
              <button class="color-swatch" style="background:#7c3aed" onclick="setColor('#7c3aed',this)" title="Púrpura"></button>
              <button class="color-swatch" style="background:#0891b2" onclick="setColor('#0891b2',this)" title="Cian"></button>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Tamaño de Fuente</label>
            <select class="form-select">
              <option>Normal</option>
              <option>Pequeño</option>
              <option>Grande</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarConfig('modalApariencia','apariencia')"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ROLES -->
<div class="modal fade" id="modalRoles" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="fa-solid fa-id-badge me-2" style="color:#db2777"></i>Roles del Sistema</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <button class="btn btn-sm btn-primary mb-3" onclick="nuevoRol()"><i class="fa-solid fa-plus me-1"></i>Nuevo Rol</button>
        <div class="table-responsive">
          <table class="table table-sm table-hover" id="tablaRoles" style="font-size:13px">
            <thead>
              <tr>
                <th>#</th>
                <th>Identificador</th>
                <th>Nombre Mostrar</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th style="width:90px">Acciones</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- MODAL ROL FORM -->
<div class="modal fade" id="modalRolForm" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalRolTitulo"><i class="fa-solid fa-id-card me-2" style="color:#db2777"></i>Nuevo Rol</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <input type="hidden" name="id">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Identificador <small class="text-muted">(ej: coordinador)</small></label><input type="text" class="form-control" name="nombre" placeholder="identificador_unico"></div>
          <div class="col-md-6"><label class="form-label">Nombre a Mostrar</label><input type="text" class="form-control" name="nombre_mostrar" placeholder="Coordinador"></div>
          <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="2" placeholder="Descripción opcional"></textarea></div>
          <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="activo"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" onclick="guardarRol()"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
