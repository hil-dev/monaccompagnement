<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700;800&display=swap">

<script>
// Applique le thème sauvegardé le plus tôt possible, avant le rendu, pour éviter un flash de mauvais thème.
(function () {
    var saved = localStorage.getItem('admin-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();
</script>

<style>
:root {
    --admin-bg: #fbf6f4;
    --admin-card-bg: #ffffff;
    --admin-border: #ecdad7;
    --admin-text: #2a1416;
    --admin-text-muted: #6b5658;
    --admin-th-bg: #fdece9;
    --admin-row-hover: #fff5f3;
    --admin-erreur-bg: #fbe3e0;
    --admin-erreur-text: #5c0a14;
    --admin-succes-bg: #e9f5ec;
    --admin-succes-text: #1c6b3a;
    --admin-accent: #b3121f;
    --admin-accent-deep: #5c0a14;
    --admin-accent-on: #f4b41a;
    --admin-display: 'Bricolage Grotesque', 'Figtree', system-ui, sans-serif;
}
[data-theme="dark"] {
    --admin-bg: #1a1013;
    --admin-card-bg: #241318;
    --admin-border: #3a1f24;
    --admin-text: #f5ece9;
    --admin-text-muted: #b99a96;
    --admin-th-bg: #2c161b;
    --admin-row-hover: #2c161b;
    --admin-erreur-bg: #3a1418;
    --admin-erreur-text: #ff9a90;
    --admin-succes-bg: #163326;
    --admin-succes-text: #7fd9a3;
    --admin-accent: #e0475a;
    --admin-accent-deep: #f4b41a;
    --admin-accent-on: #f4b41a;
}

body {
    background: var(--admin-bg);
    color: var(--admin-text);
    font-family: 'Figtree', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    transition: background .2s ease, color .2s ease;
}

.admin-wrap { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

.admin-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }

.admin-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    font-family: var(--admin-display);
    font-weight: 800;
    color: var(--admin-text);
}
.admin-brand strong { color: var(--admin-accent); }
.admin-brand-tag {
    padding: 3px 10px;
    border-radius: 999px;
    background: var(--admin-accent);
    color: #fff;
    font-family: 'Figtree', system-ui, sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}

.admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
.admin-stat-card { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 14px; padding: 16px 20px; }
.admin-stat-value { font-family: var(--admin-display); font-size: 1.7rem; font-weight: 800; color: var(--admin-text); }
.admin-stat-value.is-accent { color: var(--admin-accent); }
.admin-stat-label { font-size: 0.85rem; color: var(--admin-text-muted); }

.admin-table-wrap { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 14px; overflow-x: auto; margin-bottom: 32px; -webkit-overflow-scrolling: touch; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.admin-table th, .admin-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--admin-border); white-space: nowrap; color: var(--admin-text); }
.admin-table th { background: var(--admin-th-bg); font-weight: 700; }
.admin-table tbody tr.admin-row-link { cursor: pointer; }
.admin-table tbody tr.admin-row-link:hover { background: var(--admin-row-hover); }
.admin-table tbody tr.admin-row-link:focus-visible { outline: 2px solid var(--admin-accent); outline-offset: -2px; }

.statut-reussi { color: var(--admin-succes-text); font-weight: 700; }
.statut-echoue, .statut-annule { color: var(--admin-erreur-text); font-weight: 700; }
.statut-en_attente { color: var(--admin-accent-on); font-weight: 700; }

.admin-section-title { font-family: var(--admin-display); font-size: 1.1rem; margin-bottom: 12px; font-weight: 800; color: var(--admin-text); }

.admin-nav { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.admin-nav a { margin-right: 0; }

.admin-nav .btn-outline,
.admin-nav .btn-outline:link,
.admin-nav .btn-outline:visited {
    border-color: var(--admin-border) !important;
    color: var(--admin-text) !important;
    background: transparent !important;
}
.admin-nav .btn-outline:hover {
    background: var(--admin-row-hover) !important;
}

.theme-toggle {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 999px;
    border: 1px solid var(--admin-border); background: var(--admin-card-bg); color: var(--admin-text);
    cursor: pointer; font-size: 18px; line-height: 1; padding: 0;
}
.theme-toggle:hover { opacity: 0.85; }

.admin-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px; }
.admin-detail-item { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 14px; padding: 14px 18px; }
.admin-detail-label { font-size: 0.8rem; color: var(--admin-text-muted); margin: 0 0 4px; text-transform: uppercase; letter-spacing: .04em; }
.admin-detail-value { font-size: 1rem; font-weight: 700; color: var(--admin-text); word-break: break-word; }
.admin-retour { display: inline-flex; align-items: center; gap: 6px; color: var(--admin-text-muted); text-decoration: none; margin-bottom: 16px; font-size: 0.9rem; }
.admin-retour:hover { color: var(--admin-text); }

.admin-btn-primary {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 12px 22px; border: 0; border-radius: 12px;
    background: var(--admin-accent); color: #fff;
    font-family: inherit; font-size: 0.95rem; font-weight: 700;
    text-decoration: none; cursor: pointer;
    transition: background .15s ease;
}
.admin-btn-primary:hover { background: var(--admin-accent-deep); }

/* Formulaires admin (ex. gestion des formules) */
.admin-card { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 14px; padding: 20px 22px; margin-bottom: 20px; }
.admin-card h3 { margin: 0 0 14px; font-family: var(--admin-display); font-weight: 800; color: var(--admin-text); }
.admin-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 14px; margin-bottom: 14px; }
.admin-form-grid label { display: block; font-size: 0.82rem; margin-bottom: 5px; color: var(--admin-text-muted); font-weight: 600; }
.admin-form-grid input[type="number"] {
    width: 100%; padding: 10px 12px; border: 1.5px solid var(--admin-border); border-radius: 10px;
    background: var(--admin-bg); color: var(--admin-text); font-family: inherit; font-size: 0.95rem;
}
.admin-form-grid input[type="number"]:focus {
    outline: none; border-color: var(--admin-accent); box-shadow: 0 0 0 3px rgba(179, 18, 31, 0.15);
}
.admin-checkbox-row { display: flex; align-items: center; gap: 8px; margin-top: 8px; color: var(--admin-text); }
.admin-checkbox-row input { width: auto; }

.admin-alert { padding: 12px 14px; border-radius: 10px; font-size: 0.92rem; font-weight: 600; margin-bottom: 18px; }
.admin-alert-erreur { background: var(--admin-erreur-bg); color: var(--admin-erreur-text); border-left: 4px solid var(--admin-erreur-text); }
.admin-alert-succes { background: var(--admin-succes-bg); color: var(--admin-succes-text); border-left: 4px solid var(--admin-succes-text); }

.admin-row-hidden{ display:none; }
.admin-voir-plus-wrap{ display:flex; justify-content:center; margin:.75rem 0 1.75rem; }
.admin-voir-plus{ min-width:140px; }

.drawer-toggle{
    display:none;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    gap:4px;
    width:40px;
    height:40px;
    border-radius:50%;
    border:1.5px solid currentColor;
    background:transparent;
    color: var(--admin-text);
    cursor:pointer;
}
.drawer-toggle-bar{
    display:block;
    width:18px;
    height:2px;
    background:currentColor;
    border-radius:2px;
}
.drawer-close{
    display:none;
    align-self:flex-end;
    width:32px;
    height:32px;
    border-radius:50%;
    border:1.5px solid currentColor;
    background:transparent;
    color: var(--admin-text);
    cursor:pointer;
    font-size:.95rem;
    line-height:1;
    margin-bottom:.5rem;
}
.admin-drawer-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.35);
    z-index:1000;
}
.admin-drawer-overlay.open{ display:block; }

@media (max-width: 640px) {
    .admin-wrap { margin: 20px auto; padding: 0 14px; }
    .admin-header { flex-direction: column; align-items: stretch; gap: 12px; }
    .admin-nav { flex-direction: column; width: 100%; }
    .admin-nav a { width: 100%; text-align: center; box-sizing: border-box; }
    .admin-stats { grid-template-columns: 1fr; }
    .admin-detail-grid { grid-template-columns: 1fr; }

    .admin-nav{
        position:fixed;
        top:0;
        right:0;
        height:100vh;
        width:min(78vw, 300px);
        background: var(--admin-bg);
        color: var(--admin-text);
        flex-direction:column;
        align-items:stretch;
        justify-content:flex-start;
        padding:1.25rem;
        gap:.75rem;
        transform:translateX(100%);
        transition:transform .28s ease;
        box-shadow:-8px 0 24px rgba(0,0,0,.18);
        z-index:1001;
        overflow-y:auto;
    }
    .admin-nav.open{ transform:translateX(0); }
    .admin-nav .btn{ width:100%; text-align:center; }
    .drawer-toggle{ display:flex; }
    .drawer-close{ display:inline-flex; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('themeToggle');
    if (btn) {
        var updateIcon = function () {
            var theme = document.documentElement.getAttribute('data-theme');
            btn.textContent = theme === 'dark' ? '☀️' : '🌙';
            btn.setAttribute('aria-label', theme === 'dark' ? 'Passer au thème clair' : 'Passer au thème sombre');
        };
        updateIcon();
        btn.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme');
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('admin-theme', next);
            updateIcon();
        });
    }

    // Rend les lignes du tableau utilisateurs cliquables (souris + clavier)
    document.querySelectorAll('tr.admin-row-link').forEach(function (row) {
        row.addEventListener('click', function () {
            var url = row.getAttribute('data-href');
            if (url) { window.location = url; }
        });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var url = row.getAttribute('data-href');
                if (url) { window.location = url; }
            }
        });
    });

    // "Voir plus / Voir moins" sur les tableaux longs
    document.querySelectorAll('.admin-voir-plus').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tbody = document.getElementById(btn.dataset.target);
            if (!tbody) return;
            var estOuvert = btn.dataset.open === '1';
            tbody.querySelectorAll('.admin-row-hidden').forEach(function (tr) {
                tr.classList.toggle('admin-row-hidden', estOuvert);
            });
            btn.dataset.open = estOuvert ? '0' : '1';
            btn.textContent = estOuvert ? 'Voir plus' : 'Voir moins';
        });
    });

    // Menu burger mobile
    var toggleBtn = document.getElementById('drawerToggle');
    var closeBtn = document.getElementById('drawerClose');
    var drawer = document.getElementById('adminDrawer');
    var overlay = document.getElementById('adminDrawerOverlay');
    if (toggleBtn && drawer && overlay) {
        var openDrawer = function () {
            drawer.classList.add('open');
            overlay.classList.add('open');
            toggleBtn.setAttribute('aria-expanded', 'true');
        };
        var closeDrawer = function () {
            drawer.classList.remove('open');
            overlay.classList.remove('open');
            toggleBtn.setAttribute('aria-expanded', 'false');
        };
        toggleBtn.addEventListener('click', function () {
            drawer.classList.contains('open') ? closeDrawer() : openDrawer();
        });
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
        window.addEventListener('resize', function () { if (window.innerWidth > 640) closeDrawer(); });
    }
});
</script>