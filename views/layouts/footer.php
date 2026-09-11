  </div>
</main>

<script src="<?php echo BASE_URL; ?>/public/vendor/jquery/js/jquery.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/sweetalert2/js/sweetalert2.min.js"></script>
<script>
(function(){
  var _fire = Swal.fire;
  Swal.fire = function(){
    var a = arguments;
    if(a.length===1 && typeof a[0]==='object' && !a[0].target){
      a[0]=Object.assign({},a[0],{target:document.body});
    }
    return _fire.apply(this,a);
  };
})();
</script>
<script src="<?php echo BASE_URL; ?>/public/vendor/chartjs/js/chart.umd.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables/js/jquery.dataTables.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables/js/dataTables.bootstrap5.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables-buttons/js/buttons.bootstrap5.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/datatables-buttons/js/buttons.print.min.js"></script>
<script src="<?php echo BASE_URL; ?>/public/vendor/select2/js/select2.min.js"></script>
<script>
  const BASE_URL = '<?php echo BASE_URL; ?>';
  const CSRF_TOKEN = '<?php echo csrfToken(); ?>';

  function toggleSidebar() {
    if (window.innerWidth > 992) {
      toggleCollapseSidebar();
    } else {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
    }
  }
  function closeSidebar() {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (sb) sb.classList.remove('open');
    if (ov) ov.classList.remove('show');
  }

  function toggleCollapseSidebar() {
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
  }

  function toggleSection(el) {
    if (document.body.classList.contains('sidebar-collapsed') && window.innerWidth > 992) return;
    const items = el.nextElementSibling;
    if (!items || !items.classList.contains('nav-section-items')) return;
    const isCollapsed = el.classList.toggle('collapsed');
    items.classList.toggle('collapsed', isCollapsed);
    el.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
  }

  function initSidebar() {
    if (localStorage.getItem('sidebarCollapsed') === 'true' && window.innerWidth > 992) {
      document.body.classList.add('sidebar-collapsed');
    }
  }
  initSidebar();

  function toggleUserMenu() {
    document.getElementById('userMenu').classList.toggle('show');
    document.getElementById('topChevron').classList.toggle('open');
  }
  function closeUserMenu() {
    const menu = document.getElementById('userMenu');
    const chevron = document.getElementById('topChevron');
    if (menu) menu.classList.remove('show');
    if (chevron) chevron.classList.remove('open');
  }

  document.addEventListener('click', function(e) {
    const dd = document.getElementById('userDropdown');
    if (dd && !dd.contains(e.target)) closeUserMenu();
  });

  function showAlert(type, message) {
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
    Toast.fire({ icon: type, title: message });
  }

  function confirmDelete(callback) {
    Swal.fire({
      title: '¿Estás seguro?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => { if (result.isConfirmed) callback(); });
  }

  function updateInternetStatus(online) {
    const el = document.getElementById('internetStatus');
    if (!el) return;
    el.className = 'icon-btn-top ' + (online ? 'online' : 'offline');
    el.innerHTML = online ? '<i class="fa-solid fa-wifi"></i>' : '<i class="fa-solid fa-exclamation-triangle"></i>';
    el.title = online ? 'Conectado a Internet' : 'Sin conexión a Internet';
  }

  async function checkInternet() {
    if (!navigator.onLine) { updateInternetStatus(false); return; }
    for (const url of [
      'https://www.google.com/favicon.ico',
      'https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bulma@4/bulma.css'
    ]) {
      try { await fetch(url, { mode: 'no-cors', cache: 'no-store', signal: AbortSignal.timeout(3000) }); updateInternetStatus(true); return; }
      catch (_) { updateInternetStatus(false); }
    }
  }

  function showSyncNotification(count) {
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000, icon: 'success' });
    Toast.fire({ title: count + ' registro' + (count === 1 ? '' : 's') + ' sincronizado' + (count === 1 ? '' : 's') });
  }

  document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('online', function() { setTimeout(checkInternet, 500); });
    window.addEventListener('offline', function() { updateInternetStatus(false); });
    checkInternet();
    setInterval(checkInternet, 10000);
  });
</script>
<script src="<?php echo BASE_URL; ?>/public/js/app.js?v=<?php echo filemtime(__DIR__.'/../../public/js/app.js'); ?>"></script>
</body>
</html>
