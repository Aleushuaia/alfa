{{--
    _scripts.blade.php
    Scripts base del layout dashboard (Bootstrap + Chart.js + lógica del sidebar).
    Incluir con: @include('layouts.dashboard._scripts')
--}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
    /* ── Sidebar mobile ─────────────────────────────────────────── */
    const sidebar  = document.getElementById('sidebar');
    const overlay  = document.getElementById('sidebar-overlay');
    const toggler  = document.getElementById('sidebarToggle');

    function openSidebar()  { sidebar.classList.add('show');  overlay.classList.add('show'); }
    function closeSidebar() { sidebar.classList.remove('show'); overlay.classList.remove('show'); }

    toggler?.addEventListener('click', () =>
        sidebar.classList.contains('show') ? closeSidebar() : openSidebar()
    );
    overlay?.addEventListener('click', closeSidebar);

    /* ── Fullscreen ─────────────────────────────────────────────── */
    function toggleFullscreen() {
        if (!document.fullscreenElement) document.documentElement.requestFullscreen();
        else document.exitFullscreen();
    }

    /* ── Chart.js defaults elegantes ───────────────────────────── */
    Chart.defaults.font.family  = "'Inter', sans-serif";
    Chart.defaults.font.size    = 12;
    Chart.defaults.color        = '#64748b';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.padding       = 16;
    Chart.defaults.plugins.tooltip.padding             = 10;
    Chart.defaults.plugins.tooltip.cornerRadius        = 8;
    Chart.defaults.plugins.tooltip.titleFont           = { weight: '700' };
    Chart.defaults.scale.grid.color                    = 'rgba(0,0,0,.05)';
    Chart.defaults.scale.border.dash                   = [4,4];
</script>
