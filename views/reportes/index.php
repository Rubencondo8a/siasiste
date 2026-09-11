<?php
$pageTitle = 'Reportes';
require __DIR__ . '/../layouts/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="report-card">
      <div class="report-icon blue"><i class="fa-solid fa-users"></i></div>
      <div class="report-title">Total Estudiantes</div>
      <div class="report-desc">Cantidad total de estudiantes registrados en el sistema.</div>
      <div class="stat-value" id="reportEstudiantes" style="font-size:32px;font-weight:800;color:#2563eb;">—</div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="report-card">
      <div class="report-icon green"><i class="fa-solid fa-id-badge"></i></div>
      <div class="report-title">Total Docentes</div>
      <div class="report-desc">Cantidad total de docentes activos e inactivos.</div>
      <div class="stat-value" id="reportDocentes" style="font-size:32px;font-weight:800;color:#16a34a;">—</div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="report-card">
      <div class="report-icon amber"><i class="fa-solid fa-fingerprint"></i></div>
      <div class="report-title">Total Asistencias</div>
      <div class="report-desc">Registros de asistencia acumulados en el semestre.</div>
      <div class="stat-value" id="reportAsistencias" style="font-size:32px;font-weight:800;color:#d97706;">—</div>
    </div>
  </div>
</div>
<div class="table-card mb-3">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h6 class="fw-bold mb-0" style="font-size:15px"><i class="fa-solid fa-table me-2 text-primary"></i>Resumen por Mes</h6>
  </div>
  <div class="table-responsive">
    <table id="tableReportes" class="table table-hover w-100">
      <thead><tr><th>Mes</th><th>Presente</th><th>Ausente</th><th>Justificado</th><th>Tardanza</th><th>Total</th></tr></thead>
      <tbody>
        <tr><td>Enero</td><td>280</td><td>15</td><td>8</td><td>12</td><td>315</td></tr>
        <tr><td>Febrero</td><td>265</td><td>20</td><td>10</td><td>15</td><td>310</td></tr>
        <tr><td>Marzo</td><td>290</td><td>12</td><td>6</td><td>10</td><td>318</td></tr>
        <tr><td>Abril</td><td>275</td><td>18</td><td>9</td><td>13</td><td>315</td></tr>
        <tr><td>Mayo</td><td>295</td><td>10</td><td>5</td><td>8</td><td>318</td></tr>
      </tbody>
    </table>
  </div>
</div>
<div class="row g-2">
  <div class="col-12 col-sm-6 col-lg-3">
    <a class="btn btn-outline-success w-100 fw-bold" href="index.php?route=reportes&action=export&type=asistencia"><i class="fa-solid fa-file-excel me-1"></i>Asistencias (Excel)</a>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <a class="btn btn-outline-primary w-100 fw-bold" href="index.php?route=reportes&action=export&type=docentes"><i class="fa-solid fa-file-excel me-1"></i>Docentes (Excel)</a>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <a class="btn btn-outline-info w-100 fw-bold" href="index.php?route=reportes&action=export&type=estudiantes"><i class="fa-solid fa-file-excel me-1"></i>Estudiantes (Excel)</a>
  </div>
  <div class="col-12 col-sm-6 col-lg-3">
    <a class="btn btn-outline-danger w-100 fw-bold" href="index.php?route=reportes&action=export&type=todos"><i class="fa-solid fa-file-pdf me-1"></i>Todos (PDF)</a>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => renderReportes());
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>
