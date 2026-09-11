const API_BASE = (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/public/index.php';

async function api(section, action, id, method, body, extraQuery) {
  let url = `${API_BASE}?route=${section}`;
  if (action) url += `&action=${action}`;
  if (id) url += `&id=${id}`;
  if (extraQuery) url += extraQuery;
  const isWrite = /^(POST|PUT|PATCH|DELETE)$/i.test(method || 'GET');
  const opts = { method: method || 'GET', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
  if (body) {
    if (body instanceof FormData) {
      if (isWrite) body.append('_token', CSRF_TOKEN);
      opts.body = body;
    } else {
      if (isWrite) body._token = CSRF_TOKEN;
      opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      opts.body = new URLSearchParams(body).toString();
    }
  } else if (isWrite) {
    opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
    opts.body = new URLSearchParams({ _token: CSRF_TOKEN }).toString();
  }
  const res = await fetch(url, opts);
  const text = await res.text();
  try { return JSON.parse(text); } catch(e) { console.error('API non-JSON response:', text.substring(0, 500)); throw new Error('Respuesta no válida del servidor'); }
}

let _dtInstances = {};
const DT_LANG = { search: "Buscar:", lengthMenu: "Mostrar _MENU_", info: "Mostrando _START_ a _END_ de _TOTAL_", paginate: { previous: "Anterior", next: "Siguiente" }, zeroRecords: "Sin resultados" };

function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); }

function initTabla(id, cfg) {
  if (_dtInstances[id]) { _dtInstances[id].destroy(); _dtInstances[id] = null; }
  const el = document.getElementById(id);
  if (!el || !$.fn.DataTable) return;
  if (cfg.ajax && !cfg.ajax.headers) cfg.ajax.headers = { 'X-Requested-With': 'XMLHttpRequest' };
  const defs = { language: DT_LANG, pageLength: 10, lengthMenu: [5, 10, 25, 50], responsive: true, destroy: true };
  _dtInstances[id] = $('#' + id).DataTable($.extend(true, defs, cfg));
}

function reloadTabla(id) {
  if (_dtInstances[id]) _dtInstances[id].ajax.reload(null, false);
}

function renderEstadoBadge(d) {
  const a = d == 1;
  return `<span class="badge-status ${a?'badge-active':'badge-inactive'}">${a?'Activo':'Inactivo'}</span>`;
}

function renderSemestreCell(r) {
  const sv = r.semestre_actual || 0;
  if (sv) return `<span class="editable-semestre" style="cursor:pointer;border-bottom:1px dashed #94a3b8;padding:2px 6px;border-radius:4px" onclick="editarSemestreInline(${r.id},${sv},this)" title="Click para cambiar semestre">${sv}° Ciclo</span>`;
  return `<span class="editable-semestre" style="cursor:pointer;border-bottom:1px dashed #dc2626;padding:2px 6px;border-radius:4px;color:#dc2626" onclick="editarSemestreInline(${r.id},0,this)" title="Click para asignar semestre">Sin matrícula</span>`;
}

function aplicarFiltrosEstudiantes() {
  const dt = _dtInstances['tablaEstudiantes'];
  if (!dt) return;
  dt.column(4).search(document.getElementById('filtroGrado')?.value || '', false, false);
  dt.column(9).search(document.getElementById('filtroPrograma')?.value || '', false, false);
  dt.draw();
}

function initSelect2() {
  $('.select2-grado').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Ciclo', allowClear: true, minimumResultsForSearch: -1 });
  $('.select2-programa').select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Todos los programas --', allowClear: true });
  $('.select2-especialidad').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Seleccionar especialidad', allowClear: true });
  $('.select2-docente-unidad').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Seleccionar docente', allowClear: true });
}

async function loadEstudiantes() {
  if (_dtInstances['tablaEstudiantes']) { _dtInstances['tablaEstudiantes'].ajax.reload(null, false); return; }
  initTabla('tablaEstudiantes', {
    ajax: { url: `${API_BASE}?route=estudiantes&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: null, render: (d,t,r) => `${esc(r.apellido||'')}, ${esc(r.nombre||'')}` },
      { data: 'dni', defaultContent: '—' },
      { data: 'codigo', defaultContent: '—' },
      { data: 'grado', defaultContent: '—' },
      { data: 'seccion', defaultContent: '—' },
      { data: 'semestre_actual', render: (d,t,r) => renderSemestreCell(r) },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => {
        const sv = r.semestre_actual||0, ac = r.activo==1;
        return `<div class="btn-group btn-group-sm"><button class="btn btn-outline-info" title="Trazabilidad" onclick="abrirTrazabilidad(${r.id})"><i class="fa-solid fa-diagram-project"></i></button><button class="btn btn-outline-primary" onclick="editarEstudiante(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRegistro('estudiantes',${r.id},'estudiante')"><i class="fa-solid fa-trash-can"></i></button></div>`;
      }},
      { data: 'programa_nombre', visible: false }
    ],
    order: [[1, 'asc']],
    initComplete: function() {
      const sel = document.getElementById('filtroPrograma');
      if (!sel || sel.options.length > 1) return;
      const $sel = $(sel);
      if ($sel.data('select2')) $sel.select2('destroy');
      api('programas', 'all').then(res => {
        if (res.data) {
          sel.innerHTML = '<option value="">-- Todos los programas --</option>' +
            res.data.map(p => `<option value="${esc(p.nombre)}">${esc(p.nombre)}</option>`).join('');
          $sel.select2({ theme: 'bootstrap-5', width: '100%', placeholder: '-- Todos los programas --', allowClear: true });
        }
      }).catch(() => {});
    }
  });
}

async function loadDocentes() {
  if (_dtInstances['tablaDocentes']) { _dtInstances['tablaDocentes'].ajax.reload(null, false); return; }
  initTabla('tablaDocentes', {
    ajax: { url: `${API_BASE}?route=docentes&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: null, render: (d,t,r) => `${esc(r.apellido||'')}, ${esc(r.nombre||'')}` },
      { data: 'dni', defaultContent: '—' },
      { data: 'especialidad_nombre', defaultContent: '—' },
      { data: 'telefono', defaultContent: '—' },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => {
        const n = esc(`${r.apellido||''}, ${r.nombre||''}`);
        return `<div class="btn-group btn-group-sm"><button class="btn btn-outline-info" title="Ver cursos" onclick="verCursosDocente(${r.id},'${n.replace(/'/g,"\\'")}')"><i class="fa-solid fa-eye"></i></button><button class="btn btn-outline-primary" onclick="editarDocente(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRegistro('docentes',${r.id},'docente')"><i class="fa-solid fa-trash-can"></i></button></div>`;
      }}
    ],
    order: [[1, 'asc']],
    initComplete: function(s, json) { if (json && json.data) populateDocenteSelects(json.data); }
  });
}

async function loadAulas() {
  if (_dtInstances['tablaAulas']) { _dtInstances['tablaAulas'].ajax.reload(null, false); return; }
  initTabla('tablaAulas', {
    ajax: { url: `${API_BASE}?route=aulas&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'capacidad', defaultContent: '—' },
      { data: 'edificio', defaultContent: '—' },
      { data: 'tipo', defaultContent: '—' },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarAula(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRegistro('aulas',${r.id},'aula')"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    order: [[1, 'asc']],
    initComplete: function(s, json) { if (json && json.data) populateAulaSelects(json.data); }
  });
}

async function loadUnidades() {
  if (_dtInstances['tablaUnidades']) { _dtInstances['tablaUnidades'].ajax.reload(null, false); return; }
  initTabla('tablaUnidades', {
    ajax: { url: `${API_BASE}?route=unidades&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'programa', defaultContent: '—' },
      { data: 'semestre', defaultContent: '—' },
      { data: 'seccion', defaultContent: '—' },
      { data: 'horas_semanales', defaultContent: '—', className: 'text-center' },
      { data: 'activo', className: 'text-center', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarUnidad(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRegistro('unidades',${r.id},'unidad')"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    order: [[1, 'asc']]
  });
}

const DIAS = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'];
const CELL_COLORS = ['', 'green', 'amber', 'purple', 'red'];
const HOUR_START = 7;
const HOUR_END = 17;

let _horariosCache = [];
let _unidadesCache = [];
let _aulasCache = [];
let _docentesCache = [];

function filtrarHorario() {
  const docId = document.getElementById('filtroDocenteHorario')?.value || '';
  renderCalendarGrid(docId);
}

function renderCalendarGrid(filterDocId) {
  const cal = document.getElementById('horarioCalendar');
  if (!cal) return;

  const horarios = filterDocId
    ? _horariosCache.filter(h => String(h.docente_id) === String(filterDocId))
    : _horariosCache;

  const uMap = Object.fromEntries(_unidadesCache.map(u => [u.id, u]));
  const aMap = Object.fromEntries(_aulasCache.map(a => [a.id, a]));
  const dMap = Object.fromEntries(_docentesCache.map(d => [d.id, d]));

  const grid = [];
  for (let h = HOUR_START; h < HOUR_END; h++) {
    grid.push({ hour: h, label: `${String(h).padStart(2, '0')}:00` });
  }

  const slots = {};
  horarios.forEach(h => {
    const startH = parseInt((h.hora_inicio || '00:00').substring(0, 2), 10);
    const dia = (h.dia || '').trim();
    const diaIdx = DIAS.findIndex(d => d === dia);
    if (diaIdx === -1 || isNaN(startH)) return;
    const key = `${startH}-${diaIdx}`;
    if (!slots[key]) slots[key] = [];
    slots[key].push(h);
  });

  let html = '<div class="cal-wrapper"><div class="cal-grid">';
  html += '<div class="cal-header cal-header-time"></div>';
  DIAS.forEach(d => { html += `<div class="cal-header">${d}</div>`; });

  grid.forEach(slot => {
    html += `<div class="cal-time">${slot.label}</div>`;
    for (let d = 0; d < 5; d++) {
      const key = `${slot.hour}-${d}`;
      const items = slots[key] || [];
      html += `<div class="cal-cell${items.length ? ' has-event' : ''}">`;
      items.forEach((h, idx) => {
        const u = uMap[h.unidad_id] || {};
        const a = aMap[h.aula_id] || {};
        const doc = h.docente_id ? dMap[h.docente_id] : null;
        const colorClass = CELL_COLORS[(h.id || idx) % CELL_COLORS.length];
        const shortName = (u.nombre || 'Sin unidad').length > 18 ? (u.nombre || 'Sin unidad').substring(0, 16) + '…' : (u.nombre || 'Sin unidad');
        const docName = doc ? `${doc.apellido.split(' ')[0]}` : '';
        html += `<div class="cal-event ${colorClass}" onclick="editarHorario(${h.id})" title="${u.nombre || ''}\n${doc ? doc.nombre + ' ' + doc.apellido : ''}\n${a.nombre || ''}\n${(h.hora_inicio||'').substring(0,5)} - ${(h.hora_fin||'').substring(0,5)}">
          <div class="cal-event-title">${shortName}</div>
          <div class="cal-event-meta">${docName} · ${a.nombre || '—'}</div>
          <div class="cal-event-time"><i class="fa-solid fa-clock me-1"></i>${(h.hora_inicio||'').substring(0,5)} – ${(h.hora_fin||'').substring(0,5)}</div>
          <div class="cal-event-actions">
            <button class="cal-btn-edit" onclick="event.stopPropagation();editarHorario(${h.id})" title="Editar"><i class="fa-solid fa-pencil"></i></button>
            <button class="cal-btn-del" onclick="event.stopPropagation();eliminarRegistro('horarios',${h.id},'horario')" title="Eliminar"><i class="fa-solid fa-trash-can"></i></button>
          </div>
        </div>`;
      });
      html += '</div>';
    }
  });

  html += '</div></div>';

  if (!horarios.length) {
    html = '<div class="text-center py-5"><i class="fa-solid fa-calendar-xmark fs-1 d-block mb-2" style="color:var(--text-muted)"></i><span class="text-muted fw-semibold" style="font-size:13px;">No hay horarios registrados</span></div>';
  }

  cal.innerHTML = html;
}

async function loadHorarios() {
  const cal = document.getElementById('horarioCalendar');
  if (!cal) return;
  try {
    const [hRes, uRes, aRes, dRes] = await Promise.all([
      api('horarios', 'all'), api('unidades', 'all'), api('aulas', 'all'), api('docentes', 'all')
    ]);
    _horariosCache = hRes.data || [];
    _unidadesCache = uRes.data || [];
    _aulasCache = aRes.data || [];
    _docentesCache = dRes.data || [];
    populateDocenteSelects(_docentesCache);
    const filterSelect = document.getElementById('filtroDocenteHorario');
    if (filterSelect) filterSelect.onchange = filtrarHorario;
    renderCalendarGrid('');
  } catch (err) {
    cal.innerHTML = '<div class="text-center text-muted py-5"><i class="fa-solid fa-circle-exclamation fs-3 d-block mb-2"></i>Error al cargar horarios</div>';
    console.error('loadHorarios:', err);
  }
}

async function loadUsuarios() {
  if (_dtInstances['tablaUsuarios']) { _dtInstances['tablaUsuarios'].ajax.reload(null, false); return; }
  initTabla('tablaUsuarios', {
    ajax: { url: `${API_BASE}?route=usuarios&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'usuario', defaultContent: '—' },
      { data: 'nombre', defaultContent: '—' },
      { data: 'email', defaultContent: '—' },
      { data: 'rol_nombre', defaultContent: '—' },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarUsuario(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRegistro('usuarios',${r.id},'usuario')"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    order: [[1, 'asc']]
  });
}

function fmtFecha(iso){if(!iso)return'';var p=iso.split('-');return p[2]+'/'+p[1]+'/'+p[0]}

async function onFechaAsistenciaChange() {
  const unidad = document.getElementById('unidad_didactica');
  const fechaInput = document.getElementById('fechaAsistencia');
  if (!fechaInput || !fechaInput.value) { loadAsistencia(); return; }
  const d = new Date();
  const hoy = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  if (fechaInput.value > hoy) {
    await Swal.fire({ icon: 'error', title: 'Fecha inválida', text: 'No se puede registrar asistencia para una fecha futura.' });
    fechaInput.value = hoy;
    loadAsistencia();
    return;
  }
  const esTardio = fechaInput.value < hoy;
  if (!unidad || !unidad.value) { loadAsistencia(); return; }

  try {
    const checkParams = new URLSearchParams({ route: 'asistencia', action: 'all', unidad_id: unidad.value, fecha: fechaInput.value });
    const res = await fetch(`${API_BASE}?${checkParams}`);
    const data = await res.json();
    if (data.data && data.data.length > 0) { loadAsistencia(); return; }

    const f = fmtFecha(fechaInput.value);
    let pregunta = `Para la fecha <b>${f}</b> no hay registros. ¿Desea iniciar la jornada registrando a todos los estudiantes como Presente?`;
    let icono = 'question';
    if (esTardio) {
      pregunta = `⚠ La fecha <b>${f}</b> es anterior al día de hoy. Los registros se marcarán como <b>registro tardío</b>. ¿Desea continuar?`;
      icono = 'warning';
    }
    const result = await Swal.fire({
      icon: icono,
      title: 'No hay registros de asistencia',
      html: pregunta,
      showCancelButton: true,
      confirmButtonText: 'Sí, iniciar jornada',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#0d6efd'
    });
    if (!result.isConfirmed) { loadAsistencia(); return; }

    const res2 = await api('asistencia', 'iniciar-jornada', null, 'POST', { unidad_id: unidad.value, fecha: fechaInput.value });
    if (res2.success) {
      await Swal.fire({ icon: 'success', title: 'Jornada iniciada', text: res2.message, timer: 2000, showConfirmButton: false });
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res2.error || 'Error al iniciar jornada' });
    }
  } catch (e) { /* ignore fetch errors */ }
  loadAsistencia();
}

async function loadAsistencia() {
  const params = new URLSearchParams({ route: 'asistencia', action: 'all' });
  const fecha = document.getElementById('fechaAsistencia');
  if (fecha && fecha.value) params.set('fecha', fecha.value);
  const unidad = document.getElementById('unidad_didactica');
  if (unidad && unidad.value) params.set('unidad_id', unidad.value);
  const programa = document.getElementById('programaAsistencia');
  if (programa && programa.value) params.set('programa_id', programa.value);

  if (_dtInstances['tablaAsistencia']) {
    _dtInstances['tablaAsistencia'].ajax.url(`${API_BASE}?${params.toString()}`);
    _dtInstances['tablaAsistencia'].ajax.reload(null, false);
    return;
  }
  const badgeClass = { presente: 'badge-active', tardanza: 'badge-pending', ausente: 'badge-inactive', justificado: 'badge-justificado' };
  initTabla('tablaAsistencia', {
    ajax: { url: `${API_BASE}?${params.toString()}`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: null, render: (d,t,r) => `${esc(r.estudiante_apellido||'')}, ${esc(r.estudiante_nombre||'')}` },
      { data: 'programa_nombre', defaultContent: '—' },
      { data: 'unidad_nombre', defaultContent: '—' },
      { data: 'fecha', defaultContent: '—', render: d => d ? d.split('-').reverse().join('-') : '—' },
      { data: 'registro_tardio', defaultContent: 0, orderable: false, render: d => d == 1 ? `<span class="badge-status badge-pending" style="font-size:11px">Tardío</span>` : '—' },
      { data: 'estado', render: (d) => `<span class="badge-status ${badgeClass[d]||'badge-active'}">${d||'—'}</span>` },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => {
        const n = esc(`${r.estudiante_apellido||''}, ${r.estudiante_nombre||''}`);
        return `<button class="btn btn-sm btn-outline-primary" onclick="modalAccionAsistencia(${r.id},${r.estudiante_id},'${n.replace(/'/g,"\\'")}','${r.estado||''}')"><i class="fa-solid fa-pen-to-square me-1"></i>Acción</button>`;
      }}
    ],
    order: [[4, 'desc']]
  });
}

function populateDocenteSelects(docentes) {
  const options = '<option value="">— Seleccionar docente —</option>' + docentes.map(d => `<option value="${d.id}">${d.apellido}, ${d.nombre}</option>`).join('');
  const sel1 = document.getElementById('selectDocenteHorario');
  if (sel1) sel1.innerHTML = options;
  const filterSel = document.getElementById('filtroDocenteHorario');
  if (filterSel) {
    const prev = filterSel.value;
    filterSel.innerHTML = '<option value="">— Todos los docentes —</option>' + docentes.map(d => `<option value="${d.id}">${d.apellido}, ${d.nombre}</option>`).join('');
    filterSel.value = prev;
  }
  const sel2 = document.querySelector('.select2-docente-unidad');
  if (sel2) { sel2.innerHTML = options; $(sel2).trigger('change'); }
}

function populateAulaSelects(aulas) {
  const options = '<option value="">— Seleccionar aula —</option>' + aulas.map(a => `<option value="${a.id}">${a.nombre}</option>`).join('');
  const sel = document.querySelector('#modalHorarioForm select[name="aula_id"]');
  if (sel) sel.innerHTML = options;
}

async function updateDashboardStats() {
  try {
    const [estRes, docRes, aulaRes] = await Promise.all([
      api('estudiantes', 'all'), api('docentes', 'all'), api('aulas', 'all')
    ]);
    const el = id => document.getElementById(id);
    if (el('statEstudiantes')) el('statEstudiantes').textContent = (estRes.data || []).length;
    if (el('statDocentes')) el('statDocentes').textContent = (docRes.data || []).length;
    if (el('statAulas')) el('statAulas').textContent = (aulaRes.data || []).length;
    if (el('statAsistencias')) el('statAsistencias').textContent = '0';
  } catch (err) { console.error('updateDashboardStats:', err); }
}

function clearModal(modalId) {
  const m = document.getElementById(modalId);
  if (!m) return;
  m.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(el => { el.value = ''; });
  const hid = m.querySelector('input[name="id"]');
  if (hid) hid.value = '';
}

function setFormValue(modalId, name, value) {
  const m = document.getElementById(modalId);
  if (!m) return;
  const el = m.querySelector(`[name="${name}"]`);
  if (el) {
    el.value = value || '';
    if (el.classList.contains('select2-docente-unidad') || el.classList.contains('select2-modal-grado') || el.classList.contains('select2-especialidad')) {
      setTimeout(() => $(el).trigger('change'), 100);
    }
  }
}

function togglePass(inputId, btnId) {
  const inp = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  if (inp.type === 'password') { inp.type = 'text'; btn.innerHTML = '<i class="fa-solid fa-eye"></i>'; }
  else { inp.type = 'password'; btn.innerHTML = '<i class="fa-solid fa-eye-slash"></i>'; }
}

function collectModalData(modalId) {
  const m = document.getElementById(modalId);
  if (!m) return {};
  const data = {};
  m.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(el => {
    const name = el.getAttribute('name');
    if (!name || el.name === 'id') return;
    const isArray = name.endsWith('[]');
    const key = isArray ? name.slice(0, -2) : name;
    if (isArray) {
      data[key] = Array.from(el.selectedOptions).map(o => o.value).filter(v => v);
    } else {
      data[key] = el.value;
    }
  });
  return data;
}

function modalEstudiante() {
  clearModal('modalEstudianteForm');
  document.getElementById('modalEstudianteTitulo').textContent = 'Nuevo Estudiante';
  populateProgramaSelects();
  new bootstrap.Modal(document.getElementById('modalEstudianteForm')).show();
  setTimeout(() => { $('.select2-modal-grado').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalEstudianteForm') }); }, 300);
}
async function editarEstudiante(id) {
  try {
    const [res, progRes] = await Promise.all([api('estudiantes', 'all'), api('matriculas', 'programas')]);
    const e = (res.data || []).find(x => x.id == id);
    if (!e) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Estudiante no encontrado.', confirmButtonColor: '#2563eb' }); return; }
    const programas = progRes.data || [];
    const progOpts = '<option value="">Seleccionar</option>' + programas.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
    const selProg = document.querySelector('#modalEstudianteForm select[name="programa_id"]');
    if (selProg) selProg.innerHTML = progOpts;
    clearModal('modalEstudianteForm');
    if (selProg) selProg.innerHTML = progOpts;
    document.getElementById('modalEstudianteTitulo').textContent = 'Editar Estudiante';
    setFormValue('modalEstudianteForm', 'id', e.id);
    setFormValue('modalEstudianteForm', 'codigo', e.codigo);
    setFormValue('modalEstudianteForm', 'dni', e.dni);
    setFormValue('modalEstudianteForm', 'nombre', e.nombre);
    const apellidos = (e.apellido || '').split(' ');
    setFormValue('modalEstudianteForm', 'apellido_paterno', apellidos[0] || '');
    setFormValue('modalEstudianteForm', 'apellido_materno', apellidos.slice(1).join(' ') || '');
    setFormValue('modalEstudianteForm', 'grado', e.grado);
    setFormValue('modalEstudianteForm', 'seccion', e.seccion);
    setFormValue('modalEstudianteForm', 'programa_id', e.programa_id);
    setFormValue('modalEstudianteForm', 'telefono', e.telefono);
    setFormValue('modalEstudianteForm', 'email', e.email);
    setFormValue('modalEstudianteForm', 'activo', String(e.activo ?? 1));
    new bootstrap.Modal(document.getElementById('modalEstudianteForm')).show();
    setTimeout(() => { $('.select2-modal-grado').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalEstudianteForm') }); }, 300);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.', confirmButtonColor: '#2563eb' }); }
}

async function guardarEstudiante() {
  const m = document.getElementById('modalEstudianteForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalEstudianteForm');
  if (!data.codigo || !data.nombre) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Código y nombre son obligatorios.', confirmButtonColor: '#2563eb' });
    return;
  }
  if (data.apellido_paterno || data.apellido_materno) {
    data.apellido = [data.apellido_paterno, data.apellido_materno].filter(Boolean).join(' ');
    delete data.apellido_paterno;
    delete data.apellido_materno;
  }
  if (data.activo === 'Activo' || data.activo === '1' || data.activo === 1) data.activo = 1;
  else if (data.activo === 'Inactivo' || data.activo === '0' || data.activo === 0) data.activo = 0;
  else data.activo = 1;
  try {
    const res = id ? await api('estudiantes', 'update', id, 'POST', data) : await api('estudiantes', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Estudiante guardado correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    // Forzar recarga sin caché y limpiar filtros para que se vea la actualización
    if (_dtInstances['tablaEstudiantes']) {
      _dtInstances['tablaEstudiantes'].ajax.url(`${API_BASE}?route=estudiantes&action=all&_=${Date.now()}`).load(null, false);
      // limpiar filtros de programa/ciclo por si ocultaban el registro actualizado
      const fp = document.getElementById('filtroPrograma');
      const fg = document.getElementById('filtroGrado');
      if (fp && $(fp).val()) { $(fp).val('').trigger('change'); }
      if (fg && fg.value) { fg.value=''; _dtInstances['tablaEstudiantes'].column(4).search('', false, false); _dtInstances['tablaEstudiantes'].draw(false); }
    } else {
      loadEstudiantes();
    }
    updateDashboardStats();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

async function modalDocente() {
  clearModal('modalDocenteForm');
  document.getElementById('modalDocTitulo').textContent = 'Nuevo Docente';
  await loadEspecialidadesSelect();
  new bootstrap.Modal(document.getElementById('modalDocenteForm')).show();
  setTimeout(() => { $('.select2-especialidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalDocenteForm') }); }, 300);
}
async function editarDocente(id) {
  try {
    const res = await api('docentes', 'all');
    const d = (res.data || []).find(x => x.id == id);
    if (!d) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Docente no encontrado.', confirmButtonColor: '#2563eb' }); return; }
    clearModal('modalDocenteForm');
    document.getElementById('modalDocTitulo').textContent = 'Editar Docente';
    setFormValue('modalDocenteForm', 'id', d.id);
    setFormValue('modalDocenteForm', 'nombre', d.nombre);
    setFormValue('modalDocenteForm', 'apellido', d.apellido);
    setFormValue('modalDocenteForm', 'dni', d.dni);
    setFormValue('modalDocenteForm', 'email', d.email);
    setFormValue('modalDocenteForm', 'telefono', d.telefono);
    await loadEspecialidadesSelect(d.especialidad_id);
    new bootstrap.Modal(document.getElementById('modalDocenteForm')).show();
    setTimeout(() => { $('.select2-especialidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalDocenteForm') }); }, 300);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.', confirmButtonColor: '#2563eb' }); }
}

async function guardarDocente() {
  const m = document.getElementById('modalDocenteForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalDocenteForm');
  if (!data.nombre || !data.apellido) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Nombre y apellido son obligatorios.', confirmButtonColor: '#2563eb' });
    return;
  }
  if (data.activo === 'Activo') data.activo = 1;
  else if (data.activo === 'Inactivo') data.activo = 0;
  try {
    const res = id ? await api('docentes', 'update', id, 'POST', data) : await api('docentes', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Docente guardado correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadDocentes();
    updateDashboardStats();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

function modalAula() {
  clearModal('modalAulaForm');
  document.getElementById('modalAulaTitulo').textContent = 'Nueva Aula';
  new bootstrap.Modal(document.getElementById('modalAulaForm')).show();
}
async function editarAula(id) {
  try {
    const res = await api('aulas', 'all');
    const a = (res.data || []).find(x => x.id == id);
    if (!a) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Aula no encontrada.', confirmButtonColor: '#2563eb' }); return; }
    clearModal('modalAulaForm');
    document.getElementById('modalAulaTitulo').textContent = 'Editar Aula';
    setFormValue('modalAulaForm', 'id', a.id);
    setFormValue('modalAulaForm', 'nombre', a.nombre);
    setFormValue('modalAulaForm', 'capacidad', a.capacidad);
    setFormValue('modalAulaForm', 'edificio', a.edificio);
    setFormValue('modalAulaForm', 'tipo', a.tipo);
    setFormValue('modalAulaForm', 'activo', a.activo == 1 ? 'Activo' : 'Inactivo');
    new bootstrap.Modal(document.getElementById('modalAulaForm')).show();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.', confirmButtonColor: '#2563eb' }); }
}

async function guardarAula() {
  const m = document.getElementById('modalAulaForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalAulaForm');
  if (!data.nombre) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'El nombre del aula es obligatorio.', confirmButtonColor: '#2563eb' });
    return;
  }
  data.codigo = data.nombre;
  if (!data.capacidad) data.capacidad = 40;
  if (data.activo === 'Activo') data.activo = 1;
  else if (data.activo === 'Inactivo') data.activo = 0;
  try {
    const res = id ? await api('aulas', 'update', id, 'POST', data) : await api('aulas', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Aula guardada correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadAulas();
    updateDashboardStats();
  } catch (err) { console.error('guardarAula error:', err); Swal.fire({ icon: 'error', title: 'Error de conexión', text: err.message || 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

function modalUnidad() {
  clearModal('modalUnidadForm');
  document.getElementById('modalUnidadTitulo').textContent = 'Nueva Unidad Didáctica';
  populateProgramaSelects();
  loadProgramas().then(progs => {
    const sel = document.querySelector('.select2-programa-unidad');
    if (sel) {
      sel.innerHTML = progs.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
      $(sel).val([]).trigger('change');
    }
  });
  new bootstrap.Modal(document.getElementById('modalUnidadForm')).show();
  setTimeout(() => {
    $('.select2-docente-unidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalUnidadForm') });
    $('.select2-programa-unidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalUnidadForm') });
  }, 300);
}
async function editarUnidad(id) {
  try {
    const [uRes, dRes, progRes] = await Promise.all([api('unidades', 'all'), api('docentes', 'all'), loadProgramas()]);
    const u = (uRes.data || []).find(x => x.id == id);
    if (!u) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Unidad no encontrada.' }); return; }
    populateDocenteSelects(dRes.data || []);
    clearModal('modalUnidadForm');
    document.getElementById('modalUnidadTitulo').textContent = 'Editar Unidad Didáctica';
    setFormValue('modalUnidadForm', 'id', u.id);
    setFormValue('modalUnidadForm', 'nombre', u.nombre);
    setFormValue('modalUnidadForm', 'descripcion', u.descripcion);
    setFormValue('modalUnidadForm', 'semestre', u.semestre);
    setFormValue('modalUnidadForm', 'seccion', u.seccion);
    setFormValue('modalUnidadForm', 'horas_semanales', u.horas_semanales);
    setFormValue('modalUnidadForm', 'activo', u.activo);
    const selProg = document.querySelector('.select2-programa-unidad');
    if (selProg) {
      selProg.innerHTML = progRes.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
      if (u.programas) {
        const ids = u.programas.map(p => String(p.id));
        $(selProg).val(ids).trigger('change');
      }
    }
    const sel = document.querySelector('.select2-docente-unidad');
    if (sel && u.docentes) {
      const ids = u.docentes.map(d => String(d.id));
      $(sel).val(ids).trigger('change');
    }
    new bootstrap.Modal(document.getElementById('modalUnidadForm')).show();
    setTimeout(() => {
      $('.select2-docente-unidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalUnidadForm') });
      $('.select2-programa-unidad').select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#modalUnidadForm') });
    }, 300);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.' }); }
}

async function guardarUnidad() {
  const m = document.getElementById('modalUnidadForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalUnidadForm');
  if (!data.nombre) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'El nombre es obligatorio.' });
    return;
  }
  if (data.activo !== undefined) data.activo = parseInt(data.activo);
  if (data.semestre !== undefined) data.semestre = parseInt(data.semestre);
  const fd = new FormData();
  for (const [k, v] of Object.entries(data)) {
    if (Array.isArray(v)) { v.forEach(x => fd.append(k + '[]', x)); }
    else { fd.append(k, v); }
  }
  console.log('📦 guardarUnidad data:', JSON.stringify(data), 'id:', id);
  try {
    const res = id ? await api('unidades', 'update', id, 'POST', fd) : await api('unidades', 'store', '', 'POST', fd);
    console.log('📦 guardarUnidad response:', JSON.stringify(res));
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Unidad guardada correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadUnidades();
  } catch (err) { console.error('guardarUnidad:', err); Swal.fire({ icon: 'error', title: 'Error de conexión', text: err.message || 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

function modalHorario() {
  clearModal('modalHorarioForm');
  document.getElementById('modalHorarioTitulo').textContent = 'Nuevo Horario';
  const sel = document.getElementById('selectDocenteHorario');
  if (sel) sel.value = '';
  const selU = document.getElementById('selectUnidadHorario');
  if (selU) selU.innerHTML = '<option value="">— Primero elige un docente —</option>';
  api('aulas', 'all').then(r => populateAulaSelects(r.data || []));
  new bootstrap.Modal(document.getElementById('modalHorarioForm')).show();
}
async function editarHorario(id) {
  try {
    const [hRes, dRes, aRes, uRes] = await Promise.all([api('horarios', 'all'), api('docentes', 'all'), api('aulas', 'all'), api('unidades', 'all')]);
    const h = (hRes.data || []).find(x => x.id == id);
    if (!h) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Horario no encontrado.' }); return; }
    populateDocenteSelects(dRes.data || []);
    populateAulaSelects(aRes.data || []);
    clearModal('modalHorarioForm');
    document.getElementById('modalHorarioTitulo').textContent = 'Editar Horario';
    setFormValue('modalHorarioForm', 'id', h.id);
    setFormValue('modalHorarioForm', 'aula_id', h.aula_id);
    setFormValue('modalHorarioForm', 'dia', h.dia);
    setFormValue('modalHorarioForm', 'hora_inicio', h.hora_inicio);
    setFormValue('modalHorarioForm', 'hora_fin', h.hora_fin);
    if (h.docente_id) {
      const selDoc = document.getElementById('selectDocenteHorario');
      if (selDoc) { selDoc.value = h.docente_id; await filtrarUnidadesPorDocente(h.docente_id); }
    }
    setFormValue('modalHorarioForm', 'unidad_id', h.unidad_id);
    new bootstrap.Modal(document.getElementById('modalHorarioForm')).show();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.' }); }
}

async function guardarHorario() {
  const m = document.getElementById('modalHorarioForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalHorarioForm');
  if (!data.dia || !data.hora_inicio || !data.hora_fin || !data.docente_id || !data.unidad_id) {
    Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Todos los campos son obligatorios.' });
    return;
  }
  try {
    const res = id ? await api('horarios', 'update', id, 'POST', data) : await api('horarios', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Horario guardado correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadHorarios();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

async function loadRolesSelect() {
  try {
    const res = await api('usuarios', 'roles');
    const opts = '<option value="">— Seleccionar rol —</option>' + (res.data || []).map(r => `<option value="${r.id}">${r.nombre_mostrar}</option>`).join('');
    document.querySelectorAll('#modalUsuarioForm select[name="role_id"]').forEach(sel => { if (sel.tagName === 'SELECT') sel.innerHTML = opts; });
  } catch (e) { console.error('loadRolesSelect:', e); }
}

function modalUsuario() {
  clearModal('modalUsuarioForm');
  document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
  loadRolesSelect();
  new bootstrap.Modal(document.getElementById('modalUsuarioForm')).show();
}
async function editarUsuario(id) {
  try {
    const [uRes, rRes] = await Promise.all([api('usuarios', 'all'), api('usuarios', 'roles')]);
    const u = (uRes.data || []).find(x => x.id == id);
    if (!u) { Swal.fire({ icon: 'error', title: 'No encontrado', text: 'Usuario no encontrado.' }); return; }
    clearModal('modalUsuarioForm');
    document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
    const opts = '<option value="">— Seleccionar rol —</option>' + (rRes.data || []).map(r => `<option value="${r.id}">${r.nombre_mostrar}</option>`).join('');
    document.querySelectorAll('#modalUsuarioForm select[name="role_id"]').forEach(sel => { if (sel.tagName === 'SELECT') sel.innerHTML = opts; });
    setFormValue('modalUsuarioForm', 'id', u.id);
    setFormValue('modalUsuarioForm', 'usuario', u.usuario);
    setFormValue('modalUsuarioForm', 'nombre', u.nombre);
    setFormValue('modalUsuarioForm', 'email', u.email);
    setFormValue('modalUsuarioForm', 'role_id', u.role_id);
    setFormValue('modalUsuarioForm', 'activo', u.activo == 1 ? 'Activo' : 'Inactivo');
    new bootstrap.Modal(document.getElementById('modalUsuarioForm')).show();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.' }); }
}

async function guardarUsuario() {
  const m = document.getElementById('modalUsuarioForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalUsuarioForm');
  const p1 = data.contrasena;
  const p2 = data.contrasena_confirm;
  delete data.contrasena_confirm;
  if (!id && !p1) {
    Swal.fire({ icon: 'warning', title: 'Contraseña requerida', text: 'Debes ingresar una contraseña.' });
    return;
  }
  if (p1 && p1 !== p2) {
    Swal.fire({ icon: 'warning', title: 'Contraseñas no coinciden', text: 'Verifica las contraseñas ingresadas.' });
    return;
  }
  if (!p1) delete data.contrasena;
  if (data.activo === 'Activo') data.activo = 1;
  else if (data.activo === 'Inactivo') data.activo = 0;
  try {
    const res = id ? await api('usuarios', 'update', id, 'POST', data) : await api('usuarios', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Usuario guardado correctamente.', timer: 2000, timerProgressBar: true });
    loadUsuarios();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.' }); }
}

function eliminarRegistro(section, id, label) {
  Swal.fire({
    title: '¿Eliminar ' + label + '?',
    text: 'Esta acción no se puede deshacer.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(async result => {
    if (result.isConfirmed) {
      try {
        const res = await api(section, 'delete', id, 'POST');
        if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
        Swal.fire({ icon: 'success', title: 'Eliminado', text: 'El registro fue eliminado.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
        if (section === 'estudiantes') { loadEstudiantes(); updateDashboardStats(); }
        else if (section === 'docentes') { loadDocentes(); updateDashboardStats(); }
        else if (section === 'aulas') { loadAulas(); updateDashboardStats(); }
        else if (section === 'unidades') loadUnidades();
        else if (section === 'horarios') loadHorarios();
        else if (section === 'usuarios') loadUsuarios();
      } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo eliminar.', confirmButtonColor: '#2563eb' }); }
    }
  });
}

async function filtrarUnidadesPorDocente(docId) {
  const sel = document.getElementById('selectUnidadHorario');
  sel.innerHTML = '<option value="">— Cargando —</option>';
  if (!docId) { sel.innerHTML = '<option value="">— Primero elige un docente —</option>'; return; }
  try {
    const res = await api('unidades', 'all');
    const unidades = (res.data || []).filter(u => (u.docentes || []).some(d => String(d.id) === String(docId)));
    sel.innerHTML = '<option value="">— Seleccionar unidad —</option>';
    unidades.forEach(u => { const o = document.createElement('option'); o.value = u.id; o.textContent = u.nombre; sel.appendChild(o); });
  } catch (err) { sel.innerHTML = '<option value="">— Error al cargar —</option>'; }
}

async function verCursosDocente(id, nombre) {
  document.getElementById('modalCursosNombre').textContent = 'Docente: ' + nombre;
  const tbody = document.getElementById('bodyCursosDocente');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Cargando...</td></tr>';
  try {
    const [uRes, hRes, aRes] = await Promise.all([api('unidades', 'all'), api('horarios', 'all'), api('aulas', 'all')]);
    const aulas = Object.fromEntries((aRes.data || []).map(a => [a.id, a]));
    const horarios = (hRes.data || []).filter(h => String(h.docente_id) === String(id));
    const unidades = (uRes.data || []).filter(u => (u.docentes || []).some(d => String(d.id) === String(id)));
    if (!unidades.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No tiene unidades asignadas.</td></tr>';
    } else {
      tbody.innerHTML = unidades.map(u => {
        const hs = horarios.filter(h => String(h.unidad_id) === String(u.id));
        const horarioStr = hs.length ? hs.map(h => `${h.dia} ${(h.hora_inicio||'').substring(0,5)}-${(h.hora_fin||'').substring(0,5)}`).join('<br>') : '—';
        const aulaStr = hs.length ? hs.map(h => aulas[h.aula_id]?.nombre || '—').join('<br>') : '—';
        return `<tr><td style="font-size:13px;font-weight:600;">${u.nombre}</td><td style="font-size:13px;">${u.semestre || '—'}</td><td style="font-size:13px;">${u.seccion || '—'}</td><td style="font-size:13px;">${horarioStr}</td><td style="font-size:13px;">${aulaStr}</td><td style="font-size:13px;">${u.horas_semanales || '—'}</td></tr>`;
      }).join('');
    }
  } catch (err) { tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al cargar</td></tr>'; }
  new bootstrap.Modal(document.getElementById('modalCursosDocente')).show();
}

let _accionSeleccionada = '';
let _asistenciaIdAccion = null;
function modalAccionAsistencia(asistenciaId, estudianteId, nombre, estadoActual) {
  _asistenciaIdAccion = asistenciaId;
  _accionSeleccionada = '';
  document.getElementById('modalAsistNombre').textContent = 'Estudiante: ' + nombre;
  document.getElementById('motivoSelect').value = '';
  document.getElementById('otroMotivoText').value = '';
  document.getElementById('obsAsist').value = '';
  document.getElementById('bloqueMotivo').style.display = 'none';
  document.getElementById('otroMotivoWrap').style.display = 'none';
  document.querySelectorAll('.btn-accion-asist').forEach(b => { b.style.opacity = '1'; b.style.transform = 'scale(1)'; });
  new bootstrap.Modal(document.getElementById('modalAccionAsistenciaForm')).show();
}
function seleccionarAccion(btn) {
  _accionSeleccionada = btn.getAttribute('data-valor');
  document.querySelectorAll('.btn-accion-asist').forEach(b => {
    b.style.opacity = b === btn ? '1' : '0.4';
    b.style.transform = b === btn ? 'scale(1.06)' : 'scale(1)';
  });
  const necesitaMotivo = ['Tardanza', 'Ausente', 'Justificado'].includes(_accionSeleccionada);
  document.getElementById('bloqueMotivo').style.display = necesitaMotivo ? 'block' : 'none';
  if (!necesitaMotivo) document.getElementById('otroMotivoWrap').style.display = 'none';
}
function toggleOtroMotivo(sel) {
  document.getElementById('otroMotivoWrap').style.display = sel.value === 'otro' ? 'block' : 'none';
}
async function guardarAccionAsistencia() {
  var modalEl = document.getElementById('modalAccionAsistenciaForm');
  if (!_accionSeleccionada) {
    Swal.fire({ icon: 'warning', title: 'Selecciona un tipo', text: 'Debes elegir una acción antes de guardar.' });
    return;
  }
  const estadoMap = { 'Presente': 'presente', 'Tardanza': 'tardanza', 'Ausente': 'ausente', 'Justificado': 'justificado' };
  const estado = estadoMap[_accionSeleccionada] || 'presente';
  const necesitaMotivo = ['Tardanza', 'Ausente', 'Justificado'].includes(_accionSeleccionada);
  const body = { estado };
  if (necesitaMotivo) {
    const motivoVal = document.getElementById('motivoSelect').value;
    if (!motivoVal) {
      Swal.fire({ icon: 'warning', title: 'Motivo requerido', text: 'Selecciona el motivo correspondiente.' });
      return;
    }
    body.motivo_id = motivoVal === 'otro' ? null : motivoVal;
    body.otro_motivo = motivoVal === 'otro' ? (document.getElementById('otroMotivoText').value.trim() || null) : null;
    body.observaciones = document.getElementById('obsAsist').value.trim() || null;
  }
  try {
    const res = await api('asistencia', 'update', _asistenciaIdAccion, 'POST', body);
    if (res.success) {
      Swal.fire({ icon: 'success', title: '¡Registrado!', text: 'La acción fue guardada correctamente.', timer: 2500, showConfirmButton: false });
      loadAsistencia();
    } else {
      Swal.fire({ icon: 'error', title: 'Error', text: res.error || 'No se pudo registrar.' });
    }
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo registrar la asistencia.' });
  }
}

let asistenciaChart = null;
async function initAsistenciaChart() {
  const canvas = document.getElementById('asistenciaChart');
  if (!canvas) return;
  try {
    const res = await api('dashboard', 'chart');
    const months = (res.data || []).map(d => d.nombre.substring(0, 3).toUpperCase());
    const totals = (res.data || []).map(d => d.total);
    if (asistenciaChart) asistenciaChart.destroy();
    asistenciaChart = new Chart(canvas.getContext('2d'), {
      type: 'bar',
      data: {
        labels: months,
        datasets: [{
          label: 'Asistencias',
          data: totals,
          backgroundColor: '#2563eb28',
          borderColor: '#2563eb',
          borderWidth: 2,
          borderRadius: 4,
          borderSkipped: false
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#64748b', maxRotation: 0 } },
          y: { beginAtZero: true, ticks: { stepSize: 10, font: { family: 'Plus Jakarta Sans', size: 10 }, color: '#64748b' }, grid: { color: 'rgba(0,0,0,.06)' } }
        }
      }
    });
  } catch (err) { console.error('initAsistenciaChart:', err); }
}

function abrirModalInstitucion() { new bootstrap.Modal(document.getElementById('modalInstitucion')).show(); }
function abrirModalPerfil() { new bootstrap.Modal(document.getElementById('modalPerfil')).show(); }
function abrirModalContrasena() { new bootstrap.Modal(document.getElementById('modalCambiarPass')).show(); }
function abrirModalAnio() { new bootstrap.Modal(document.getElementById('modalAnioAcademico')).show(); }
function abrirModalApariencia() { new bootstrap.Modal(document.getElementById('modalApariencia')).show(); }
async function guardarConfig(modalId, texto) {
  const m = document.getElementById(modalId);
  const data = {};
  m.querySelectorAll('input[name], select[name], textarea[name]').forEach(el => { data[el.name] = el.value; });
  try {
    let res;
    if (modalId === 'modalPerfil') {
      res = await api('configuracion', 'perfil', '', 'POST', data);
    } else {
      bootstrap.Modal.getInstance(m).hide();
      Swal.fire({ icon: 'success', title: '¡Guardado!', text: `La configuración de ${texto} fue actualizada.`, confirmButtonColor: '#2563eb', timer: 2500, timerProgressBar: true });
      return;
    }
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error }); return; }
    bootstrap.Modal.getInstance(m).hide();
    if (res.data) {
      document.querySelectorAll('.user-menu-name, .top-user-name').forEach(el => el.textContent = res.data.nombre);
      document.querySelectorAll('.user-menu-email').forEach(el => el.textContent = res.data.email);
    }
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Perfil actualizado correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.' }); }
}
/* ---- Roles ---- */
let _dtRoles = null;

function abrirModalRoles() {
  new bootstrap.Modal(document.getElementById('modalRoles')).show();
  if (_dtRoles) { _dtRoles.ajax.reload(null, false); return; }
  _dtRoles = initTabla('tablaRoles', {
    ajax: { url: `${API_BASE}?route=roles&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'nombre_mostrar', defaultContent: '—' },
      { data: 'descripcion', defaultContent: '—', render: d => d || '<span class="text-muted">—</span>' },
      { data: 'activo', render: d => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarRol(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarRol(${r.id})"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    autoWidth: false, scrollX: true,
    order: [[2, 'asc']]
  });
}

function nuevoRol() {
  clearModal('modalRolForm');
  document.getElementById('modalRolTitulo').textContent = 'Nuevo Rol';
  new bootstrap.Modal(document.getElementById('modalRolForm')).show();
}

async function editarRol(id) {
  try {
    const res = await api('roles', 'all');
    const r = (res.data || []).find(x => x.id == id);
    if (!r) { Swal.fire({ icon: 'error', title: 'No encontrado' }); return; }
    clearModal('modalRolForm');
    document.getElementById('modalRolTitulo').textContent = 'Editar Rol';
    setFormValue('modalRolForm', 'id', r.id);
    setFormValue('modalRolForm', 'nombre', r.nombre);
    setFormValue('modalRolForm', 'nombre_mostrar', r.nombre_mostrar);
    setFormValue('modalRolForm', 'descripcion', r.descripcion);
    setFormValue('modalRolForm', 'activo', r.activo == 1 ? '1' : '0');
    new bootstrap.Modal(document.getElementById('modalRolForm')).show();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.' }); }
}

async function guardarRol() {
  const m = document.getElementById('modalRolForm');
  const id = m.querySelector('input[name="id"]')?.value;
  const data = collectModalData('modalRolForm');
  try {
    const res = id ? await api('roles', 'update', id, 'POST', data) : await api('roles', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error }); return; }
    bootstrap.Modal.getInstance(m).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', timer: 2000, timerProgressBar: true });
    if (_dtRoles) _dtRoles.ajax.reload(null, false);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.' }); }
}

async function eliminarRol(id) {
  const confirmed = await Swal.fire({ title: '¿Eliminar rol?', text: 'No podrás revertir esto.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar' });
  if (!confirmed.isConfirmed) return;
  try {
    const res = await api('roles', 'delete', id, 'POST');
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error }); return; }
    Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1500, timerProgressBar: true });
    if (_dtRoles) _dtRoles.ajax.reload(null, false);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión' }); }
}

async function guardarContrasena() {
  const actual = document.getElementById('passActual').value;
  const nueva = document.getElementById('passNueva').value;
  const confirm = document.getElementById('passNuevaConfirm').value;
  if (!actual || !nueva) { Swal.fire({ icon: 'warning', title: 'Campos requeridos', text: 'Completa todos los campos.', confirmButtonColor: '#2563eb' }); return; }
  if (nueva !== confirm) { Swal.fire({ icon: 'warning', title: 'Contraseñas no coinciden', text: 'La nueva contraseña y su confirmación deben ser iguales.', confirmButtonColor: '#2563eb' }); return; }
  try {
    const res = await api('configuracion', 'contrasena', '', 'POST', { passActual: actual, passNueva: nueva });
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error }); return; }
    bootstrap.Modal.getInstance(document.getElementById('modalCambiarPass')).hide();
    document.getElementById('passActual').value = '';
    document.getElementById('passNueva').value = '';
    document.getElementById('passNuevaConfirm').value = '';
    Swal.fire({ icon: 'success', title: '¡Contraseña actualizada!', text: 'Tu contraseña fue cambiada correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo cambiar la contraseña.' }); }
}
function setColor(color, btn) {
  document.querySelectorAll('.color-swatch').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.documentElement.style.setProperty('--accent-blue', color);
  document.documentElement.style.setProperty('--sidebar-active', color);
}

async function renderReportes() {
  try {
    const [estRes, docRes] = await Promise.all([api('estudiantes', 'all'), api('docentes', 'all')]);
    const el = id => document.getElementById(id);
    if (el('reportEstudiantes')) el('reportEstudiantes').textContent = (estRes.data || []).length;
    if (el('reportDocentes')) el('reportDocentes').textContent = (docRes.data || []).length;
    if (el('reportAsistencias')) el('reportAsistencias').textContent = '0';
  } catch (err) { console.error('renderReportes:', err); }
}

let _trazSearchTimeout = null;
let _trazEstudianteId = null;

function abrirTrazabilidad(id) {
  window.location.href = `index.php?route=trazabilidad&estudiante_id=${id}`;
}

function buscarEstudianteTrazabilidad() {
  const q = document.getElementById('trazabilidadSearch').value.trim();
  const box = document.getElementById('trazabilidadResults');
  if (q.length < 2) { box.style.display = 'none'; return; }
  clearTimeout(_trazSearchTimeout);
  _trazSearchTimeout = setTimeout(async () => {
    try {
      const res = await api('trazabilidad', 'buscar', '', 'GET', null, `&q=${encodeURIComponent(q)}`);
      const data = res.data || [];
      if (!data.length) { box.innerHTML = '<div class="p-3 text-muted" style="font-size:13px">Sin resultados</div>'; box.style.display = 'block'; return; }
      box.innerHTML = data.map(e => {
        const nombre = `${e.apellido || ''}, ${e.nombre || ''}`;
        const sem = e.semestre_actual ? `${e.semestre_actual}° Sem.` : '—';
        const estado = e.matricula_estado || 'sin matrícula';
        return `<div class="p-2 border-bottom" style="cursor:pointer;font-size:13px" onmousedown="seleccionarEstudianteTraz(${e.id},'${(e.nombre||'').replace(/'/g,"\\'")}','${(e.apellido||'').replace(/'/g,"\\'")}','${(e.codigo||'').replace(/'/g,"\\'")}','${(e.programa_nombre||'').replace(/'/g,"\\'")}','${(e.grado||'').replace(/'/g,"\\'")}','${(e.seccion||'').replace(/'/g,"\\'")}',${e.semestre_actual||0},'${estado}','${(e.email||'').replace(/'/g,"\\'")}','${(e.telefono||'').replace(/'/g,"\\'")}')">
          <strong>${nombre}</strong> <span class="text-muted ms-1">(${e.codigo || '—'})</span><br>
          <small class="text-muted">${e.programa_nombre || '—'} | ${sem} | <span class="badge bg-${estado==='activo'?'success':estado==='egresado'?'primary':'danger'}" style="font-size:10px">${estado}</span></small>
        </div>`;
      }).join('');
      box.style.display = 'block';
    } catch (err) { box.style.display = 'none'; }
  }, 300);
}

async function seleccionarEstudianteTraz(id, nombre, apellido, codigo, programa, grado, seccion, semestre, estado, email, telefono) {
  _trazEstudianteId = id;
  document.getElementById('trazabilidadSearch').value = `${apellido}, ${nombre}`;
  document.getElementById('trazabilidadResults').style.display = 'none';
  document.getElementById('trazabilidadEmpty').style.display = 'none';
  document.getElementById('trazabilidadContent').style.display = 'block';

  document.getElementById('trazNombre').textContent = `${apellido}, ${nombre}`;
  document.getElementById('trazCodigo').textContent = codigo;
  document.getElementById('trazPrograma').textContent = programa || '—';
  document.getElementById('trazCiclo').textContent = grado || '—';
  document.getElementById('trazSeccion').textContent = seccion || '—';
  document.getElementById('trazEmail').textContent = email || '—';
  document.getElementById('trazTelefono').textContent = telefono || '—';
  const estadoEl = document.getElementById('trazEstado');
  estadoEl.innerHTML = `<span class="badge bg-${estado==='activo'?'success':estado==='egresado'?'primary':'danger'}">${estado}</span>`;

  const showBtns = estado === 'activo';
  document.getElementById('btnAvanzarSemestreTraz').style.display = showBtns ? '' : 'none';
  document.getElementById('btnRetirarTraz').style.display = showBtns ? '' : 'none';

  await Promise.all([loadTrazHistorial(id), loadTrazAsistencias(id)]);
}

async function loadTrazHistorial(id) {
  const tl = document.getElementById('trazTimeline');
  tl.innerHTML = '<div class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span> Cargando...</div>';
  try {
    const res = await api('trazabilidad', 'historial', id);
    const data = res.data || [];
    if (!data.length) {
      tl.innerHTML = '<div class="text-center py-4"><i class="fa-solid fa-inbox fs-3 d-block mb-2" style="color:#cbd5e1"></i><span class="text-muted" style="font-size:13px">Sin matrículas registradas</span></div>';
      return;
    }
    const estadoColors = { activo: 'success', egresado: 'primary', retirado: 'danger' };
    const estadoIcons = { activo: 'circle-check', egresado: 'award', retirado: 'circle-xmark' };
    let html = '<div class="timeline">';
    data.forEach(m => {
      const color = estadoColors[m.estado] || 'secondary';
      const icon = estadoIcons[m.estado] || 'circle';
      html += `
        <div class="timeline-item">
          <div class="d-flex align-items-start gap-3">
            <div class="timeline-dot bg-${color}">
              <i class="fa-solid fa-${icon} text-white"></i>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0" style="font-size:14px">Semestre ${m.semestre} — ${m.periodo_nombre || 'Sin período'}</h6>
                <span class="badge bg-${color}" style="font-size:11px">${m.estado}</span>
              </div>
              <div class="text-muted mt-1" style="font-size:12.5px">
                <i class="fa-solid fa-book me-1"></i>${m.programa_nombre || '—'}
                ${m.fecha_inicio ? `<span class="ms-2"><i class="fa-solid fa-calendar me-1"></i>${m.fecha_inicio} — ${m.fecha_fin || '...'}</span>` : ''}
              </div>
            </div>
          </div>
        </div>`;
    });
    html += '</div>';
    tl.innerHTML = html;
  } catch (err) {
    tl.innerHTML = '<div class="text-center text-danger py-3">Error al cargar historial</div>';
  }
}

async function loadTrazAsistencias(id) {
  const tbody = document.getElementById('trazAsistenciaBody');
  tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>';
  try {
    const res = await api('trazabilidad', 'asistencias', id);
    const data = res.data || [];
    const stats = res.stats || {};
    const porcentaje = res.porcentaje_asistencia || 0;

    if (!data.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3" style="font-size:13px">Sin registros de asistencia</td></tr>';
      return;
    }

    let statsHtml = `<div class="d-flex gap-3 mb-2 flex-wrap" style="font-size:12px">
      <span><i class="fa-solid fa-circle-check text-success me-1"></i>Presente: ${stats.presente || 0}</span>
      <span><i class="fa-solid fa-circle-xmark text-danger me-1"></i>Ausente: ${stats.ausente || 0}</span>
      <span><i class="fa-solid fa-circle-exclamation text-warning me-1"></i>Tardanza: ${stats.tardanza || 0}</span>
      <span><i class="fa-solid fa-file-lines text-info me-1"></i>Justificado: ${stats.justificado || 0}</span>
      <span class="fw-bold">Asistencia: ${porcentaje}%</span>
    </div>`;

    const estadoBadge = { presente: 'success', ausente: 'danger', tardanza: 'warning', justificado: 'info' };
    tbody.innerHTML = statsHtml + data.map((a, i) => {
      const badge = estadoBadge[a.estado] || 'secondary';
      return `<tr><td>${i + 1}</td><td>${a.fecha || '—'}</td><td>${a.unidad_nombre || '—'}</td><td><span class="badge bg-${badge}">${a.estado || '—'}</span></td></tr>`;
    }).join('');
  } catch (err) {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar asistencias</td></tr>';
  }
}

async function avanzarSemestreTraz() {
  if (!_trazEstudianteId) return;
  const result = await Swal.fire({
    title: '¿Avanzar semestre?', text: 'El estudiante pasará al siguiente semestre.',
    icon: 'question', showCancelButton: true, confirmButtonColor: '#2563eb', cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, avanzar', cancelButtonText: 'Cancelar'
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api('trazabilidad', 'avanzar', _trazEstudianteId, 'POST');
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    Swal.fire({ icon: 'success', title: '¡Avanzado!', text: res.message, confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    document.getElementById('trazabilidadSearch').dispatchEvent(new Event('input'));
    buscarEstudianteTrazabilidad();
    if (_trazEstudianteId) {
      const perfilRes = await api('trazabilidad', 'perfil', _trazEstudianteId);
      if (perfilRes && !perfilRes.error) {
        seleccionarEstudianteTraz(_trazEstudianteId, perfilRes.nombre, perfilRes.apellido, perfilRes.codigo, perfilRes.programa_nombre, perfilRes.grado, perfilRes.seccion, perfilRes.semestre_actual, perfilRes.matricula_estado, perfilRes.email, perfilRes.telefono);
      }
    }
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo avanzar.', confirmButtonColor: '#2563eb' });
  }
}

async function retirarEstudianteTraz() {
  if (!_trazEstudianteId) return;
  const result = await Swal.fire({
    title: '¿Retirar estudiante?', text: 'Se marcará la matrícula activa como retirado.',
    icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, retirar', cancelButtonText: 'Cancelar'
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api('trazabilidad', 'retirar', _trazEstudianteId, 'POST');
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    Swal.fire({ icon: 'success', title: '¡Retirado!', text: res.message, confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    if (_trazEstudianteId) {
      const perfilRes = await api('trazabilidad', 'perfil', _trazEstudianteId);
      if (perfilRes && !perfilRes.error) {
        seleccionarEstudianteTraz(_trazEstudianteId, perfilRes.nombre, perfilRes.apellido, perfilRes.codigo, perfilRes.programa_nombre, perfilRes.grado, perfilRes.seccion, perfilRes.semestre_actual, perfilRes.matricula_estado, perfilRes.email, perfilRes.telefono);
      }
    }
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo retirar.', confirmButtonColor: '#2563eb' });
  }
}

let _especialidadesCache = [];
async function loadEspecialidadesSelect(selectedId) {
  try {
    const res = await api('especialidades', 'activas');
    _especialidadesCache = res.data || [];
    const sel = document.querySelector('#modalDocenteForm select[name="especialidad_id"]');
    if (sel) {
      sel.innerHTML = '<option value="">Seleccionar</option>' + _especialidadesCache.map(e => `<option value="${e.id}" ${e.id == selectedId ? 'selected' : ''}>${e.nombre}</option>`).join('');
    }
  } catch (e) { _especialidadesCache = []; }
}

async function loadEspecialidades() {
  if (_dtInstances['tablaEspecialidades']) { _dtInstances['tablaEspecialidades'].ajax.reload(null, false); return; }
  initTabla('tablaEspecialidades', {
    ajax: { url: `${API_BASE}?route=especialidades&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarEspecialidad(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarEspecialidad(${r.id})"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    order: [[1, 'asc']]
  });
}

function modalEspecialidad(id, nombre, activo) {
  document.getElementById('especId').value = id || '';
  document.getElementById('especNombre').value = nombre || '';
  document.getElementById('especActivo').value = activo !== undefined ? activo : '1';
  document.getElementById('modalEspecTitulo').innerHTML = id ? '<i class="fa-solid fa-bookmark me-2 text-primary"></i>Editar Especialidad' : '<i class="fa-solid fa-bookmark me-2 text-primary"></i>Nueva Especialidad';
  new bootstrap.Modal(document.getElementById('modalEspecialidadForm')).show();
}

async function editarEspecialidad(id) {
  try {
    const res = await api('especialidades', 'all');
    const e = (res.data || []).find(x => x.id == id);
    if (!e) return;
    modalEspecialidad(e.id, e.nombre, e.activo);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.', confirmButtonColor: '#2563eb' }); }
}

async function guardarEspecialidad() {
  const id = document.getElementById('especId').value;
  const nombre = document.getElementById('especNombre').value.trim();
  const activo = document.getElementById('especActivo').value;
  if (!nombre) {
    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre de la especialidad es obligatorio.', confirmButtonColor: '#2563eb' });
    return;
  }
  try {
    const data = { nombre, activo };
    const res = id ? await api('especialidades', 'update', id, 'POST', data) : await api('especialidades', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(document.getElementById('modalEspecialidadForm')).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Especialidad guardada correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadEspecialidades();
    _especialidadesCache = [];
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

async function eliminarEspecialidad(id) {
  const result = await Swal.fire({
    title: '¿Eliminar especialidad?', text: 'Esta acción no se puede deshacer.',
    icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api('especialidades', 'delete', id, 'POST');
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    Swal.fire({ icon: 'success', title: '¡Eliminada!', text: res.message, confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadEspecialidades();
    _especialidadesCache = [];
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo eliminar.', confirmButtonColor: '#2563eb' }); }
}

async function loadProgramasAdmin() {
  if (_dtInstances['tablaProgramas']) { _dtInstances['tablaProgramas'].ajax.reload(null, false); return; }
  initTabla('tablaProgramas', {
    ajax: { url: `${API_BASE}?route=programas&action=all`, dataSrc: 'data' },
    columns: [
      { data: null, orderable: false, className: 'text-center', render: (d,t,r,m) => m.row+1 },
      { data: 'nombre', defaultContent: '—' },
      { data: 'descripcion', defaultContent: '—', render: (d) => d ? `<span style="max-width:260px;font-size:12.5px">${esc(d)}</span>` : '—' },
      { data: 'duracion_semestres', defaultContent: '—' },
      { data: 'activo', render: (d) => renderEstadoBadge(d) },
      { data: null, orderable: false, className: 'text-end', render: (d,t,r) => `<div class="btn-group btn-group-sm"><button class="btn btn-outline-primary" onclick="editarPrograma(${r.id})"><i class="fa-solid fa-pencil"></i></button><button class="btn btn-outline-danger" onclick="eliminarPrograma(${r.id})"><i class="fa-solid fa-trash-can"></i></button></div>` }
    ],
    order: [[1, 'asc']]
  });
}

function modalPrograma(id, nombre, descripcion, duracion, activo) {
  document.getElementById('progId').value = id || '';
  document.getElementById('progNombre').value = nombre || '';
  document.getElementById('progDescripcion').value = descripcion || '';
  document.getElementById('progDuracion').value = duracion || 6;
  document.getElementById('progActivo').value = activo !== undefined ? activo : '1';
  document.getElementById('modalProgTitulo').innerHTML = id ? '<i class="fa-solid fa-graduation-cap me-2 text-primary"></i>Editar Programa' : '<i class="fa-solid fa-graduation-cap me-2 text-primary"></i>Nuevo Programa';
  new bootstrap.Modal(document.getElementById('modalProgramaForm')).show();
}

async function editarPrograma(id) {
  try {
    const res = await api('programas', 'all');
    const p = (res.data || []).find(x => x.id == id);
    if (!p) return;
    modalPrograma(p.id, p.nombre, p.descripcion, p.duracion_semestres, p.activo);
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.', confirmButtonColor: '#2563eb' }); }
}

async function guardarPrograma() {
  const id = document.getElementById('progId').value;
  const nombre = document.getElementById('progNombre').value.trim();
  const descripcion = document.getElementById('progDescripcion').value.trim();
  const duracion = document.getElementById('progDuracion').value;
  const activo = document.getElementById('progActivo').value;
  if (!nombre) {
    Swal.fire({ icon: 'warning', title: 'Campo requerido', text: 'El nombre del programa es obligatorio.', confirmButtonColor: '#2563eb' });
    return;
  }
  try {
    const data = { nombre, descripcion, duracion_semestres: duracion, activo };
    const res = id ? await api('programas', 'update', id, 'POST', data) : await api('programas', 'store', '', 'POST', data);
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    bootstrap.Modal.getInstance(document.getElementById('modalProgramaForm')).hide();
    Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message || 'Programa guardado correctamente.', confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadProgramasAdmin();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo guardar.', confirmButtonColor: '#2563eb' }); }
}

async function eliminarPrograma(id) {
  const result = await Swal.fire({
    title: '¿Eliminar programa?', text: 'Esta acción no se puede deshacer.',
    icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#64748b',
    confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar'
  });
  if (!result.isConfirmed) return;
  try {
    const res = await api('programas', 'delete', id, 'POST');
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    Swal.fire({ icon: 'success', title: '¡Eliminado!', text: res.message, confirmButtonColor: '#2563eb', timer: 2000, timerProgressBar: true });
    loadProgramasAdmin();
  } catch (err) { Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo eliminar.', confirmButtonColor: '#2563eb' }); }
}

let _programasCache = [];
async function loadProgramas() {
  if (_programasCache.length) return _programasCache;
  try {
    const res = await api('matriculas', 'programas');
    _programasCache = res.data || [];
    return _programasCache;
  } catch (e) { return []; }
}

async function populateProgramaSelects() {
  const programas = await loadProgramas();
  const options = '<option value="">Seleccionar</option>' + programas.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
  document.querySelectorAll('[name="programa_id"]').forEach(sel => {
    if (sel.tagName === 'SELECT') { sel.innerHTML = options; }
  });
}

function editarSemestreInline(estudianteId, currentVal, el) {
  const td = el.parentNode;
  td.innerHTML = '';
  td.style.display = 'inline-flex';
  td.style.gap = '4px';
  td.style.alignItems = 'center';

  const select = document.createElement('select');
  select.id = 'inlineSemestreSelect';
  select.className = 'form-select form-select-sm';
  select.style.cssText = 'width:auto;font-size:12px;padding:2px 24px 2px 8px';
  [1,2,3,4,5,6].forEach(n => {
    const opt = document.createElement('option');
    opt.value = n;
    opt.textContent = n + '° Ciclo';
    if (n === currentVal) opt.selected = true;
    select.appendChild(opt);
  });
  td.appendChild(select);

  const btnOk = document.createElement('button');
  btnOk.className = 'btn btn-sm btn-success';
  btnOk.style.cssText = 'padding:2px 8px;font-size:11px';
  btnOk.innerHTML = '<i class="fa-solid fa-check"></i>';
  btnOk.addEventListener('click', function(e) {
    e.stopPropagation();
    guardarSemestreInline(estudianteId);
  });
  td.appendChild(btnOk);

  const btnCancel = document.createElement('button');
  btnCancel.className = 'btn btn-sm btn-outline-secondary';
  btnCancel.style.cssText = 'padding:2px 8px;font-size:11px';
  btnCancel.innerHTML = '<i class="fa-solid fa-xmark"></i>';
  btnCancel.addEventListener('click', function(e) {
    e.stopPropagation();
    cancelarSemestreInline();
  });
  td.appendChild(btnCancel);

  select.focus();
}

async function guardarSemestreInline(estudianteId) {
  const semestre = document.getElementById('inlineSemestreSelect').value;
  try {
    const res = await api('matriculas', 'set-semestre', '', 'POST', { estudiante_id: estudianteId, semestre });
    if (res.error) { Swal.fire({ icon: 'error', title: 'Error', text: res.error, confirmButtonColor: '#2563eb' }); return; }
    loadEstudiantes();
    updateDashboardStats();
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo actualizar.', confirmButtonColor: '#2563eb' });
    loadEstudiantes();
  }
}

function cancelarSemestreInline() { loadEstudiantes(); }

let _importType = '';
let _csvData = null;

function modalImportar(type) {
  _importType = type;
  _csvData = null;
  document.getElementById('modalImportarTitulo').textContent = type === 'estudiantes' ? 'Importar Estudiantes' : 'Importar Docentes';
  document.getElementById('csvFileInput').value = '';
  document.getElementById('csvPreviewArea').style.display = 'none';
  document.getElementById('btnImportarCSV').disabled = true;
  new bootstrap.Modal(document.getElementById('modalImportar')).show();
}

document.addEventListener('DOMContentLoaded', () => {
  const btnPlantilla = document.getElementById('btnDescargarPlantilla');
  if (btnPlantilla) btnPlantilla.addEventListener('click', () => {
    window.location.href = `${API_BASE}?route=import&action=template&type=${_importType}`;
  });

  const csvInput = document.getElementById('csvFileInput');
  if (csvInput) csvInput.addEventListener('change', e => {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = evt => {
      const text = evt.target.result;
      const lines = text.split('\n').filter(l => l.trim());
      if (lines.length < 2) { Swal.fire({ icon: 'warning', title: 'Archivo vacío', text: 'El CSV no tiene datos.' }); return; }
      _csvData = file;
      const head = lines[0].split(',');
      const preview = lines.slice(1, 6);
      document.getElementById('csvPreviewHead').innerHTML = '<tr>' + head.map(h => `<th>${h.trim()}</th>`).join('') + '</tr>';
      document.getElementById('csvPreviewBody').innerHTML = preview.map(r => {
        const cols = r.split(',');
        return '<tr>' + cols.map(c => `<td>${c.trim()}</td>`).join('') + '</tr>';
      }).join('');
      document.getElementById('csvFileInfo').textContent = `${lines.length - 1} registros encontrados en el archivo.`;
      document.getElementById('csvPreviewArea').style.display = 'block';
      document.getElementById('btnImportarCSV').disabled = false;
    };
    reader.readAsText(file);
  });

  const btnImportar = document.getElementById('btnImportarCSV');
  if (btnImportar) btnImportar.addEventListener('click', async () => {
    if (!_csvData) return;
    btnImportar.disabled = true;
    btnImportar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Importando...';
    const fd = new FormData();
    fd.append('csv', _csvData);
    fd.append('_token', CSRF_TOKEN);
    try {
      const res = await fetch(`${API_BASE}?route=import&action=${_importType}`, {
        method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
      }).then(r => r.json());
      bootstrap.Modal.getInstance(document.getElementById('modalImportar')).hide();
      if (res.errores && res.errores.length) {
        const errList = res.errores.map(e => `Fila ${e.fila}: ${e.error}`).join('<br>');
        Swal.fire({ icon: 'warning', title: 'Importación con errores', html: `<p>${res.message}</p><div style="text-align:left;max-height:150px;overflow:auto;font-size:12px;background:#f8f9fa;padding:8px;border-radius:4px">${errList}</div>`, confirmButtonColor: '#2563eb', width: 600 });
      } else {
        Swal.fire({ icon: 'success', title: '¡Importado!', text: res.message, confirmButtonColor: '#2563eb', timer: 3000, timerProgressBar: true });
      }
      if (_importType === 'estudiantes') loadEstudiantes();
      else loadDocentes();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo importar el archivo.', confirmButtonColor: '#2563eb' });
    }
    btnImportar.disabled = false;
    btnImportar.innerHTML = '<i class="fa-solid fa-upload me-1"></i> Importar';
  });
});

document.addEventListener('click', (e) => {
  const box = document.getElementById('trazabilidadResults');
  const input = document.getElementById('trazabilidadSearch');
  if (box && input && !box.contains(e.target) && e.target !== input) box.style.display = 'none';
});
