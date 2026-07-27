<?php
require_once __DIR__ . '/config.php';
start_session_secure();
?>
<!doctype html>
<html lang="<?= h(current_language()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>opnCentral</title>
    <link rel="icon" type="image/svg+xml" href="/assets/opncentral-favicon.svg">
    <link rel="stylesheet" href="/assets/style.css">
    <style>
        .brand-with-icon{display:flex;align-items:center;gap:10px}
        .brand-icon{width:34px;height:34px;display:block;flex:0 0 auto}
        .brand-text{display:flex;flex-direction:column;justify-content:center}.brand-meta{display:flex;align-items:center;gap:6px;font-size:11px;line-height:1.2;opacity:.72;margin-top:2px}.support-link{color:inherit;text-decoration:none;white-space:nowrap}.support-link:hover,.support-link:focus{text-decoration:underline;opacity:1}
        .main-nav{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
        .main-nav>a,.nav-menu>summary{display:inline-flex;align-items:center;min-height:34px;padding:0 10px;border-radius:7px;text-decoration:none;cursor:pointer;list-style:none}
        .main-nav>a:hover,.nav-menu>summary:hover,.nav-menu[open]>summary{background:rgba(127,127,127,.14)}
        .nav-menu{position:relative}
        .nav-menu>summary::-webkit-details-marker{display:none}
        .nav-menu>summary::after{content:"▾";font-size:10px;margin-left:7px;opacity:.65}
        .nav-dropdown{position:absolute;z-index:1000;top:calc(100% + 6px);left:0;display:grid;min-width:180px;padding:6px;border:1px solid rgba(127,127,127,.24);border-radius:10px;background:#fff;color:#17202a;box-shadow:0 10px 28px rgba(0,0,0,.18)}
        .nav-dropdown a{display:block;margin:0;padding:9px 10px;border-radius:7px;color:#17202a!important;text-decoration:none;white-space:nowrap}
        .nav-dropdown a:hover,.nav-dropdown a:focus{background:#e7edf2;color:#17202a!important;outline:none}
        .nav-separator{width:1px;height:24px;background:rgba(127,127,127,.28);margin:0 3px}.nav-menu-right .nav-dropdown{left:auto;right:0}.language-dropdown{display:grid;gap:8px;padding:8px 10px}.language-dropdown select{width:100%;margin:0}.dropdown-separator{height:1px;background:#dce1e5;margin:4px 0}
        @media(max-width:850px){.main-nav{gap:4px}.nav-dropdown{position:fixed;left:12px;right:12px;top:auto;min-width:0}.nav-separator{display:none}}
    </style>
</head>
<body>
<header>
    <div class="brand brand-with-icon">
        <img src="/assets/opncentral-icon.svg" alt="" class="brand-icon" aria-hidden="true">
        <div class="brand-text">
            <div><?= h(app_name()) ?></div>
            <div class="brand-meta">
                <span class="opncentral-version">v0.4.2.6</span>
                <span aria-hidden="true">·</span>
                <a
                    class="support-link"
                    href="https://www.paypal.com/paypalme/FrazoN11"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="<?= h(t('menu.support')) ?>"
                >♥ <?= h(t('menu.support')) ?></a>
            </div>
        </div>
    </div>

    <?php if (logged_in()): ?>
        <nav class="main-nav">
            <a href="/"><?= h(t('menu.dashboard')) ?></a>

            <details class="nav-menu">
                <summary><?= h(t('menu.firewalls')) ?></summary>
                <div class="nav-dropdown">
                    <a href="/"><?= h(t('menu.overview')) ?></a>
                    <a href="/firewall_edit.php"><?= h(t('menu.add_firewall')) ?></a>
                </div>
            </details>

            <details class="nav-menu">
                <summary><?= h(t('menu.actions')) ?></summary>
                <div class="nav-dropdown">
                    <a href="/aliases.php"><?= h(t('menu.distribute_alias')) ?></a>
                    <a href="/alias_overview.php"><?= h(t('menu.alias_overview')) ?></a>
                    <div class="dropdown-separator" aria-hidden="true"></div>
                    <a href="/categories.php"><?= h(t('menu.distribute_category')) ?></a>
                    <a href="/category_overview.php"><?= h(t('menu.category_overview')) ?></a>
                </div>
            </details>

            <span class="nav-separator" aria-hidden="true"></span>

            <details class="nav-menu nav-menu-right">
                <summary><?= h(t('menu.settings')) ?></summary>
                <div class="nav-dropdown language-dropdown">
                    <a href="/notifications.php"><?= h(t('menu.notifications')) ?></a>
                    <div class="dropdown-separator" aria-hidden="true"></div>
                    <label>
                        <span><?= h(t('language')) ?></span>
                        <select id="language-selector" aria-label="<?= h(t('language')) ?>">
                            <?php foreach (supported_languages() as $code => $label): ?>
                                <option value="<?= h($code) ?>" <?= current_language() === $code ? 'selected' : '' ?>><?= h($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            </details>

            <a href="/logout.php"><?= h(t('menu.logout')) ?></a>
        </nav>
    <?php endif; ?>
</header>
<main>
<script>
document.getElementById('language-selector')?.addEventListener('change', function(){const url=new URL(window.location.href);url.searchParams.set('lang',this.value);window.location.href=url.toString();});
document.addEventListener('click', function (event) {
    document.querySelectorAll('.nav-menu[open]').forEach(function (menu) {
        if (!menu.contains(event.target)) {
            menu.removeAttribute('open');
        }
    });
});
</script>
