<script>
// Applique le thème sauvegardé le plus tôt possible, avant le rendu, pour éviter un flash de mauvais thème.
(function () {
    var saved = localStorage.getItem('admin-theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);
})();
</script>

<style>
:root {
    --admin-bg: #f7f5f1;
    --admin-card-bg: #ffffff;
    --admin-border: #eee;
    --admin-text: #1a1a1a;
    --admin-text-muted: #777;
    --admin-th-bg: #fafafa;
    --admin-row-hover: #faf7f2;
    --admin-erreur-bg: #fdecec;
    --admin-erreur-text: #b3261e;
}
[data-theme="dark"] {
    --admin-bg: #14141a;
    --admin-card-bg: #1e1e26;
    --admin-border: #2c2c36;
    --admin-text: #f2f2f2;
    --admin-text-muted: #9a9aa5;
    --admin-th-bg: #24242e;
    --admin-row-hover: #23232c;
    --admin-erreur-bg: #3a2020;
    --admin-erreur-text: #ff8f87;
}

body { background: var(--admin-bg); color: var(--admin-text); transition: background .2s ease, color .2s ease; }

.admin-wrap { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
.admin-header { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 24px; }
.admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 32px; }
.admin-stat-card { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 12px; padding: 16px 20px; }
.admin-stat-value { font-size: 1.6rem; font-weight: 700; color: var(--admin-text); }
.admin-stat-label { font-size: 0.85rem; color: var(--admin-text-muted); }
.admin-table-wrap { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 12px; overflow-x: auto; margin-bottom: 32px; -webkit-overflow-scrolling: touch; }
.admin-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.admin-table th, .admin-table td { padding: 10px 14px; text-align: left; border-bottom: 1px solid var(--admin-border); white-space: nowrap; color: var(--admin-text); }
.admin-table th { background: var(--admin-th-bg); font-weight: 600; }
.admin-table tbody tr.admin-row-link { cursor: pointer; }
.admin-table tbody tr.admin-row-link:hover { background: var(--admin-row-hover); }
.admin-table tbody tr.admin-row-link:focus-visible { outline: 2px solid #E8531F; outline-offset: -2px; }
.admin-section-title { font-size: 1.1rem; margin-bottom: 12px; font-weight: 700; color: var(--admin-text); }
.admin-nav { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.admin-nav a { margin-right: 0; }

[data-theme="dark"] .admin-nav .btn-outline,
[data-theme="dark"] .admin-nav .btn-outline:link,
[data-theme="dark"] .admin-nav .btn-outline:visited {
    color: #ffffff !important;
    border-color: var(--admin-border) !important;
    background: transparent !important;
}
[data-theme="dark"] .admin-nav .btn-outline:hover {
    color: #ffffff !important;
    background: var(--admin-card-bg) !important;
}

.theme-toggle {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; border-radius: 999px;
    border: 1px solid var(--admin-border); background: var(--admin-card-bg); color: var(--admin-text);
    cursor: pointer; font-size: 18px; line-height: 1; padding: 0;
}
.theme-toggle:hover { opacity: 0.85; }

.admin-detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 32px; }
.admin-detail-item { background: var(--admin-card-bg); border: 1px solid var(--admin-border); border-radius: 12px; padding: 14px 18px; }
.admin-detail-label { font-size: 0.8rem; color: var(--admin-text-muted); margin: 0 0 4px; text-transform: uppercase; letter-spacing: .04em; }
.admin-detail-value { font-size: 1rem; font-weight: 600; color: var(--admin-text); word-break: break-word; }
.admin-retour { display: inline-flex; align-items: center; gap: 6px; color: var(--admin-text-muted); text-decoration: none; margin-bottom: 16px; font-size: 0.9rem; }
.admin-retour:hover { color: var(--admin-text); }

@media (max-width: 640px) {
    .admin-wrap { margin: 20px auto; padding: 0 14px; }
    .admin-header { flex-direction: column; align-items: stretch; gap: 12px; }
    .admin-nav { flex-direction: column; width: 100%; }
    .admin-nav a { width: 100%; text-align: center; box-sizing: border-box; }
    .admin-stats { grid-template-columns: 1fr; }
    .admin-detail-grid { grid-template-columns: 1fr; }
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
});
</script>