<?php
require_once __DIR__ . '/config.php';

start_session_secure();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title><?= h(app_name()) ?></title>

    <link
        rel="stylesheet"
        href="/assets/style.css"
    >
</head>
<body>

<header>
    <div class="brand">
        <div><?= h(app_name()) ?></div>
        <div
            class="opncentral-version"
            style="
                font-size: 11px;
                line-height: 1.2;
                opacity: 0.65;
                margin-top: 2px;
            "
        >
            v0.1.9.1
        </div>
    </div>

    <?php if (logged_in()): ?>
        <nav>
            <a href="/">Dashboard</a>
            <a href="/firewall_edit.php">Add firewall</a>
            <a href="/logout.php">Logout</a>
        </nav>
    <?php endif; ?>
</header>

<main>
