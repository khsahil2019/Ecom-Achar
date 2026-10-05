    </div> <!-- End admin-content-body -->

    <!-- Modern Admin Footer -->
    <footer class="px-4 py-3 bg-white border-top d-flex flex-wrap justify-content-between align-items-center text-muted small mt-auto">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border">Achar Admin v2.4</span>
            <span>&copy; <?= date('Y') ?> <strong><?= e(get_setting('site_name', 'Achar Heritage')) ?></strong></span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="d-none d-sm-inline"><i class="bi bi-hdd-network me-1"></i> SQLite Online</span>
            <span><i class="bi bi-shield-check text-success me-1"></i> Secure Session</span>
        </div>
    </footer>
</div> <!-- End admin-main-content -->
</div> <!-- End admin-wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin Core Interactions JS -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('adminSidebar');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    const toggleBtn = document.getElementById('adminSidebarToggle');
    const closeBtn = document.getElementById('adminSidebarClose');
    const searchInput = document.getElementById('globalAdminSearch');

    function openSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.add('show');
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSidebar() {
        if (sidebar && backdrop) {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // Global Keyboard Shortcut: Cmd+K or Ctrl+K to focus search
    document.addEventListener('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
    });
});
</script>
</body>
</html>
