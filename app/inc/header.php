<?php
require_once __DIR__ . '/config.php';
start_session_secure();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= h(app_name()) ?></title>
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .main-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .main-nav>a,.nav-menu>summary{display:inline-flex;align-items:center;min-height:34px;padding:0 10px;border-radius:7px;text-decoration:none;cursor:pointer;list-style:none}
        .main-nav>a:hover,.nav-menu>summary:hover,.nav-menu[open]>summary{background:rgba(127,127,127,.14)}
        .nav-menu{position:relative}
        .nav-menu>summary::-webkit-details-marker{display:none}
        .nav-menu>summary::after{content:"▾";font-size:10px;margin-left:7px;opacity:.65}
        .nav-dropdown{position:absolute;z-index:1000;top:calc(100% + 6px);left:0;display:grid;min-width:180px;padding:6px;border:1px solid rgba(127,127,127,.24);border-radius:10px;background:var(--card-bg,#fff);box-shadow:0 10px 28px rgba(0,0,0,.18)}
        .nav-dropdown a{display:block;padding:9px 10px;border-radius:7px;text-decoration:none;white-space:nowrap}
        .nav-dropdown a:hover{background:rgba(127,127,127,.14)}
        .nav-separator{width:1px;height:24px;background:rgba(127,127,127,.28);margin:0 3px}
        @media(max-width:850px){.main-nav{gap:4px}.nav-dropdown{position:fixed;left:12px;right:12px;top:auto;min-width:0}.nav-separator{display:none}}
    </style>
</head>
<body>
<header>
    <div class="brand">
        <div><?= h(app_name()) ?></div>
        <div class="opncentral-version" style="font-size:11px;line-height:1.2;opacity:.65;margin-top:2px;">v0.3.6</div>
    </div>

    <?php if (logged_in()): ?>
        <nav class="main-nav">
            <a href="/">Dashboard</a>

            <details class="nav-menu">
                <summary>Firewalls</summary>
                <div class="nav-dropdown">
                    <a href="/">Overview</a>
                    <a href="/firewall_edit.php">Add firewall</a>
                </div>
            </details>

            <details class="nav-menu">
                <summary>Aliases</summary>
                <div class="nav-dropdown">
                    <a href="/aliases.php">Distribute alias</a>
                    <a href="/alias_overview.php">Alias overview</a>
                </div>
            </details>

            <details class="nav-menu">
                <summary>Categories</summary>
                <div class="nav-dropdown">
                    <a href="/categories.php">Distribute category</a>
                    <a href="/category_overview.php">Category overview</a>
                </div>
            </details>

            <span class="nav-separator" aria-hidden="true"></span>
            <a href="/logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>
<main>
<script>
document.addEventListener('click', function (event) {
    document.querySelectorAll('.nav-menu[open]').forEach(function (menu) {
        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});
</script>
