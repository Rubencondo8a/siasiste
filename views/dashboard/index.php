<?php
$pageTitle = 'Inicio';
require __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-card">
      <div class="d-flex align-items-center gap-3">
        <div class="dash-icon blue"><i class="fa-solid fa-users"></i></div>
        <div>
          <div class="dash-label">Estudiantes</div>
          <div class="dash-value" id="statEstudiantes">0</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-card">
      <div class="d-flex align-items-center gap-3">
        <div class="dash-icon green"><i class="fa-solid fa-id-badge"></i></div>
        <div>
          <div class="dash-label">Docentes</div>
          <div class="dash-value" id="statDocentes">0</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-card">
      <div class="d-flex align-items-center gap-3">
        <div class="dash-icon amber"><i class="fa-solid fa-building"></i></div>
        <div>
          <div class="dash-label">Aulas</div>
          <div class="dash-value" id="statAulas">0</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="dashboard-card">
      <div class="d-flex align-items-center gap-3">
        <div class="dash-icon purple"><i class="fa-solid fa-fingerprint"></i></div>
        <div>
          <div class="dash-label">Asistencias</div>
          <div class="dash-value" id="statAsistencias">0</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="table-card">
      <h6 class="fw-bold mb-3" style="font-size:14px"><i class="fa-solid fa-chart-bar me-2 text-primary"></i>Resumen de Asistencias</h6>
      <div style="position:relative;height:260px;">
        <canvas id="asistenciaChart"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="table-card">
      <h6 class="fw-bold mb-3" style="font-size:14px"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Resumen Rápido</h6>
      <div style="font-size:13px">
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Período Activo</span><span class="fw-semibold">2026-I</span></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Estudiantes con Matrícula</span><span class="fw-semibold">—</span></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Docentes Asignados</span><span class="fw-semibold">—</span></div>
        <div class="d-flex justify-content-between py-2"><span class="text-muted">% Asistencia Global</span><span class="fw-semibold">—</span></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  updateDashboardStats();
  initAsistenciaChart();
});
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
