{{--
    _scripts.blade.php
    Scripts base del layout dashboard (Bootstrap + Chart.js + lógica del sidebar).
    Incluir con: @include('layouts.dashboard._scripts')
--}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

<script>
    /* ── Theme toggle (switch) ──────────────────────────────────────── */
    (function() {
        const html = document.documentElement;
        const stored = localStorage.getItem('alfa-theme') || 'light';
        html.setAttribute('data-theme', stored);

        const sw = document.getElementById('darkModeSwitch');
        if (sw) {
            sw.checked = (stored === 'dark');
            sw.addEventListener('change', () => {
                const next = sw.checked ? 'dark' : 'light';
                html.setAttribute('data-theme', next);
                localStorage.setItem('alfa-theme', next);
            });
        }
    })();

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

    /* ── Sidebar collapse (desktop) ─────────────────────────────── */
    (function () {
        const COLLAPSED_W = '80px';
        const EXPANDED_W  = '270px';
        const LS_KEY      = 'sidebar-collapsed';

        function applyCollapse(collapsed) {
            const sb  = document.getElementById('sidebar');
            const pw  = document.getElementById('page-wrapper');
            const tb  = document.getElementById('topbar');
            const btn = document.getElementById('btn-collapse');
            if (!sb) return;

            if (collapsed) {
                // Sync html class for CSS-only pre-render support
                document.documentElement.classList.add('sidebar-is-collapsed');
                // Add collapsed class to trigger CSS rules
                sb.classList.add('collapsed');
                // Update CSS variable for width
                document.documentElement.style.setProperty('--sidebar-w', COLLAPSED_W);
                // Also force margin-left on page-wrapper and left on topbar for reliability
                if (pw) pw.style.marginLeft = COLLAPSED_W;
                if (tb) tb.style.left = COLLAPSED_W;
            } else {
                // Sync html class
                document.documentElement.classList.remove('sidebar-is-collapsed');
                // Remove collapsed class
                sb.classList.remove('collapsed');
                // Update CSS variable for width
                document.documentElement.style.setProperty('--sidebar-w', EXPANDED_W);
                // Reset inline styles to use CSS variables
                if (pw) pw.style.marginLeft = '';
                if (tb) tb.style.left = '';
            }

            // Update button UI
            if (btn) {
                const icon  = document.getElementById('collapse-icon');
                const label = btn.querySelector('.collapse-label');
                if (label) label.textContent = collapsed ? 'Expandir' : 'Colapsar';
                btn.dataset.sidebarTooltip = collapsed ? 'Expandir panel' : 'Colapsar panel';
            }
        }

        // Restore state immediately to avoid layout flash
        const savedState = localStorage.getItem(LS_KEY) === 'true';
        applyCollapse(savedState);

        document.addEventListener('DOMContentLoaded', function () {
            // Re-apply after DOM is ready to ensure all elements are initialized
            const savedState = localStorage.getItem(LS_KEY) === 'true';
            applyCollapse(savedState);

            const btn = document.getElementById('btn-collapse');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Toggle: if currently collapsed, expand; if expanded, collapse
                    const currentlyCollapsed = document.getElementById('sidebar').classList.contains('collapsed');
                    const nextState = !currentlyCollapsed;
                    // Store new state
                    localStorage.setItem(LS_KEY, nextState);
                    // Apply changes
                    applyCollapse(nextState);
                });
            }
        });
    })();

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
