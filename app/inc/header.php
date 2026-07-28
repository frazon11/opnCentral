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
<link rel="icon" type="image/svg+xml" href="/assets/opncentral-favicon.svg">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body class="<?= logged_in() ? 'app-shell' : 'login-shell' ?>">
<?php if (logged_in()): ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="/assets/opncentral-icon.svg" alt="" class="sidebar-logo">
        <div>
            <strong><?= h(app_name()) ?></strong>
            <div class="sidebar-meta">
                <span>v0.4.3.1</span><span>·</span>
                <a href="https://www.paypal.com/paypalme/FrazoN11" target="_blank" rel="noopener noreferrer">♥ <?= h(t('menu.support')) ?></a>
            </div>
        </div>
    </div>

    <nav class="side-nav">
        <a class="<?= nav_active(['/','/index.php']) ?>" href="/">▦ <span><?= h(t('menu.dashboard')) ?></span></a>

        <div class="nav-group">Firewalls</div>
        <a class="<?= nav_active(['/firewall_edit.php']) ?>" href="/firewall_edit.php">＋ <span><?= h(t('menu.add_firewall')) ?></span></a>

        <div class="nav-group">VPN</div>
        <a class="<?= nav_active(['/wireguard_links.php']) ?>" href="/?view=vpn">⇄ <span>Managed WireGuard</span></a>

        <div class="nav-group"><?= h(t('menu.actions')) ?></div>
        <a class="<?= nav_active(['/aliases.php','/alias_overview.php']) ?>" href="/alias_overview.php">≡ <span><?= h(t('menu.aliases')) ?></span></a>
        <a class="<?= nav_active(['/categories.php','/category_overview.php']) ?>" href="/category_overview.php">▤ <span><?= h(t('menu.categories')) ?></span></a>
        <a class="<?= nav_active(['/backups.php']) ?>" href="/backups.php">⬇ <span>Backups</span></a>

        <div class="nav-group"><?= h(t('menu.settings')) ?></div>
        <a class="<?= nav_active(['/notifications.php']) ?>" href="/notifications.php">● <span><?= h(t('menu.notifications')) ?></span></a>
        <label class="sidebar-language">
            <span><?= h(t('language')) ?></span>
            <select id="language-selector">
                <?php foreach (supported_languages() as $code => $label): ?>
                    <option value="<?= h($code) ?>" <?= current_language()===$code?'selected':'' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
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
document.getElementById('language-selector')?.addEventListener('change',function(){
 const url=new URL(window.location.href);url.searchParams.set('lang',this.value);window.location.href=url.toString();
});
document.getElementById('sidebar-toggle')?.addEventListener('click',function(){
 document.body.classList.toggle('sidebar-open');
});
</script>
