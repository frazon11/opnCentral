<?php
require_once __DIR__ . '/config.php';
start_session_secure();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
function nav_active(array $paths): string {
    global $currentPath;
    return in_array($currentPath, $paths, true) ? ' active' : '';
}
?>
<!doctype html>
<html lang="<?= h(current_language()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>opnCentral</title>
<link rel="icon" href="/assets/favicon.ico?v=0436" sizes="any">
<link rel="icon" type="image/svg+xml" href="/assets/opncentral-icon.svg?v=0436">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png?v=0436">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png?v=0436">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=0436">
<link rel="manifest" href="/assets/site.webmanifest?v=0436">
<meta name="theme-color" content="#26313a" id="browser-theme-color">
<script>
(function(){
    const saved=localStorage.getItem('opncentral-theme');
    const theme=saved==='dark'?'dark':'light';
    document.documentElement.dataset.theme=theme;
})();
</script>
<link rel="stylesheet" href="/assets/style.css?v=0436">
</head>
<body class="<?= logged_in() ? 'app-shell' : 'login-shell' ?>">
<?php if (logged_in()): ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="/assets/opncentral-icon.svg" alt="" class="sidebar-logo">
        <div>
            <strong><?= h(app_name()) ?></strong>
            <div class="sidebar-meta">
                <span>v0.4.3.6</span><span>·</span>
                <a href="https://www.paypal.com/paypalme/FrazoN11" target="_blank" rel="noopener noreferrer">♥ <?= h(t('menu.support')) ?></a>
            </div>
        </div>
    </div>

    <nav class="side-nav">
        <a class="<?= nav_active(['/','/index.php']) ?>" href="/">▦ <span><?= h(t('menu.dashboard')) ?></span></a>

        <div class="nav-group">Firewalls</div>
        <a class="<?= nav_active(['/firewall_edit.php']) ?>" href="/firewall_edit.php">＋ <span><?= h(t('menu.add_firewall')) ?></span></a>

        <div class="nav-group">VPN</div>
        <a class="<?= nav_active(['/wireguard_overview.php']) ?>" href="/wireguard_overview.php">⇄ <span>Managed WireGuard</span></a>

        <div class="nav-group"><?= h(t('menu.actions')) ?></div>
        <a class="<?= nav_active(['/aliases.php','/alias_overview.php']) ?>" href="/alias_overview.php">≡ <span><?= h(t('menu.aliases')) ?></span></a>
        <a class="<?= nav_active(['/categories.php','/category_overview.php']) ?>" href="/category_overview.php">▤ <span><?= h(t('menu.categories')) ?></span></a>
        <a class="<?= nav_active(['/backups.php']) ?>" href="/backups.php">⬇ <span>Backups</span></a>

        <div class="nav-group"><?= h(t('menu.settings')) ?></div>
        <a class="<?= nav_active(['/settings.php']) ?>" href="/settings.php">⚙ <span><?= h(t('menu.settings')) ?></span></a>
        <a class="<?= nav_active(['/notifications.php']) ?>" href="/notifications.php">● <span><?= h(t('menu.notifications')) ?></span></a>
    </nav>

    <a class="sidebar-logout" href="/logout.php">⇥ <?= h(t('menu.logout')) ?></a>
</aside>
<div class="page-shell">
<header class="topbar">
    <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle navigation">☰</button>
    <div class="topbar-title"><?= h(app_name()) ?></div>
    <div class="topbar-right"><span class="status-dot"></span> Central management</div>
</header>
<main class="content">
<?php else: ?>
<header class="login-header"><img src="/assets/opncentral-icon.svg" alt="" class="sidebar-logo"><strong><?= h(app_name()) ?></strong></header>
<main class="content login-content">
<?php endif; ?>
<script>
window.opnCentralSetTheme=function(theme){
    const selected=theme==='dark'?'dark':'light';
    document.documentElement.dataset.theme=selected;
    localStorage.setItem('opncentral-theme',selected);
    const meta=document.getElementById('browser-theme-color');
    if(meta) meta.setAttribute('content',selected==='dark'?'#1b2228':'#26313a');
};
window.opnCentralSetTheme(document.documentElement.dataset.theme||'light');
document.getElementById('sidebar-toggle')?.addEventListener('click',function(){
    document.body.classList.toggle('sidebar-open');
});
</script>
