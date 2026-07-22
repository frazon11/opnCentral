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
        .main-nav{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        .nav-group{display:flex;align-items:center;gap:6px;padding-left:12px;border-left:1px solid rgba(127,127,127,.28)}
        .nav-group:first-child{padding-left:0;border-left:0}
        .nav-group-label{font-size:11px;text-transform:uppercase;letter-spacing:.06em;opacity:.55;margin-right:2px}
        @media(max-width:850px){.main-nav,.nav-group{gap:8px}.nav-group{padding-left:8px}}
    </style>
</head>
<body>
<header>
    <div class="brand">
        <div><?= h(app_name()) ?></div>
        <div class="opncentral-version" style="font-size:11px;line-height:1.2;opacity:.65;margin-top:2px;">v0.3.4</div>
    </div>

    <?php if (logged_in()): ?>
        <nav class="main-nav">
            <div class="nav-group">
                <a href="/">Dashboard</a>
            </div>

            <div class="nav-group">
                <span class="nav-group-label">Aliases</span>
                <a href="/aliases.php">Distribute</a>
                <a href="/alias_overview.php">Overview</a>
            </div>

            <div class="nav-group">
                <span class="nav-group-label">Categories</span>
                <a href="/categories.php">Distribute</a>
                <a href="/category_overview.php">Overview</a>
            </div>

            <div class="nav-group">
                <a href="/firewall_edit.php">Add firewall</a>
                <a href="/logout.php">Logout</a>
            </div>
        </nav>
    <?php endif; ?>
</header>
<main>
