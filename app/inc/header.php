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
<link rel="icon" href="/assets/favicon.ico?v=0641" sizes="any">
<link rel="icon" type="image/svg+xml" href="/assets/opncentral-icon.svg?v=0641">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png?v=0641">
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png?v=0641">
<link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png?v=0641">
<link rel="manifest" href="/assets/site.webmanifest?v=0641">
<meta name="theme-color" content="#26313a" id="browser-theme-color">
<script>
(function(){
    const saved=localStorage.getItem('opncentral-theme');
    const theme=saved==='dark'?'dark':'light';
    document.documentElement.dataset.theme=theme;
})();
</script>
<link rel="stylesheet" href="/assets/style.css?v=0641">
</head>
<body class="<?= logged_in() ? 'app-shell' : 'login-shell' ?><?= logged_in() && !configuration_unlocked() ? ' configuration-locked' : ' configuration-unlocked' ?>">
<?php if (logged_in()): ?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="/assets/opncentral-icon.svg" alt="" class="sidebar-logo">
        <div>
            <strong><?= h(app_name()) ?></strong>
            <div class="sidebar-meta">
                <span>v0.6.4.1</span><span>·</span>
                <a
                    href="https://buymeacoffee.com/frazon11"
                    target="_blank"
                    rel="noopener noreferrer"
                    title="Buy me a coffee"
                >☕ Buy me a coffee</a>
            </div>
        </div>
    </div>

    <nav class="side-nav">
        <a class="<?= nav_active(['/','/index.php']) ?>" href="/">▦ <span><?= h(t('menu.dashboard')) ?></span></a>

        <div class="nav-group">Firewalls</div>
        <a class="<?= nav_active(['/firewall_edit.php']) ?>" href="/firewall_edit.php">＋ <span><?= h(t('menu.add_firewall')) ?></span></a>

        <a class="<?= nav_active(['/services.php']) ?>" href="/services.php">⚙ <span>Services</span></a>
        <a class="<?= nav_active(['/agents.php']) ?>" href="/agents.php">⇄ <span>Agents</span></a>

        <div class="nav-group">VPN</div>

        <div class="nav-section-label">WireGuard</div>
        <a class="nav-child<?= nav_active(['/wireguard_overview.php']) ?>" href="/wireguard_overview.php">
            <span>Manage</span>
        </a>
        <a class="nav-child<?= nav_active(['/wireguard_create.php']) ?>" href="/wireguard_create.php">
            <span>Create Site-to-Site VPN</span>
        </a>

        <div class="nav-section-label">OpenVPN</div>
        <a
            class="nav-child<?= nav_active(['/openvpn_manage.php']) ?>"
            href="/openvpn_manage.php"
        >
            <span>Manage</span>
        </a>
        <span class="nav-child nav-disabled" aria-disabled="true">
            <span>Create Site-to-Site VPN</span>
            <small>coming soon</small>
        </span>
        <a
            class="nav-child<?= nav_active(['/openvpn_roadwarrior_create.php']) ?>"
            href="/openvpn_roadwarrior_create.php"
        >
            <span>Create Roadwarrior Server</span>
        </a>

        <div class="nav-group"><?= h(t('menu.actions')) ?></div>
        <a class="<?= nav_active(['/aliases.php','/alias_overview.php']) ?>" href="/alias_overview.php">≡ <span><?= h(t('menu.aliases')) ?></span></a>
        <a class="<?= nav_active(['/categories.php','/category_overview.php']) ?>" href="/category_overview.php">▤ <span><?= h(t('menu.categories')) ?></span></a>
        <a class="<?= nav_active(['/backups.php']) ?>" href="/backups.php">⬇ <span>Backups</span></a>

        <div class="nav-group"><?= h(t('menu.settings')) ?></div>
        <a class="<?= nav_active(['/settings.php']) ?>" href="/settings.php">⚙ <span><?= h(t('menu.settings')) ?></span></a>
        <a class="<?= nav_active(['/notifications.php']) ?>" href="/notifications.php">● <span><?= h(t('menu.notifications')) ?></span></a>
    </nav>

    <div class="sidebar-footer-actions">
        <a class="sidebar-logout" href="/logout.php">
            ⇥ <?= h(t('menu.logout')) ?>
        </a>

        <a
            class="sidebar-paypal"
            href="https://www.paypal.com/paypalme/FrazoN11"
            target="_blank"
            rel="noopener noreferrer"
            title="Support via PayPal"
        >
            ♥ PayPal
        </a>
    </div>
</aside>
<div class="page-shell">
<header class="topbar">
    <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle navigation">☰</button>
    <div class="topbar-title"><?= h(app_name()) ?></div>
    <div class="topbar-right">
        <span class="configuration-lock-state <?= configuration_unlocked()
            ? 'is-unlocked'
            : 'is-locked' ?>">
            <?= configuration_unlocked()
                ? 'Configuration unlocked'
                : 'Read-only mode' ?>
        </span>
        <button
            type="button"
            id="configuration-lock-button"
            class="button <?= configuration_unlocked()
                ? 'warning'
                : 'secondary' ?>"
            data-unlocked="<?= configuration_unlocked() ? '1' : '0' ?>"
        >
            <?= configuration_unlocked() ? 'Lock' : 'Unlock' ?>
        </button>
    </div>
</header>

<div id="configuration-unlock-dialog"
     class="configuration-unlock-dialog"
     hidden>
    <div class="configuration-unlock-backdrop"></div>
    <section class="configuration-unlock-card"
             role="dialog"
             aria-modal="true"
             aria-labelledby="configuration-unlock-title">
        <h2 id="configuration-unlock-title">
            Unlock configuration changes
        </h2>
        <p>
            Enter the configuration password to enable changes on managed
            OPNsense firewalls for this login session.
        </p>
        <label for="configuration-unlock-password">Password</label>
        <input
            type="password"
            id="configuration-unlock-password"
            autocomplete="current-password"
        >
        <div id="configuration-unlock-error"
             class="alert error hidden"></div>
        <div class="actions">
            <button type="button"
                    class="button secondary"
                    id="configuration-unlock-cancel">
                Cancel
            </button>
            <button type="button"
                    class="button"
                    id="configuration-unlock-submit">
                Unlock
            </button>
        </div>
    </section>
</div>

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


window.opnCentralConfigurationUnlocked =
    document.body.classList.contains('configuration-unlocked');

function markRemoteChangeControls(){
    const locked = !window.opnCentralConfigurationUnlocked;
    const currentPath = window.location.pathname;
    const mutatingPages = new Set([
        '/aliases.php',
        '/categories.php',
        '/wireguard_create.php',
        '/openvpn_roadwarrior_create.php'
    ]);

    const selectors = [
        '[data-action]:not([data-action="firmware_check"])',
        '.wg-state-action',
        '.vpn-state-action',
        '.plugin-action',
        '.remote-change-control'
    ];

    if(mutatingPages.has(currentPath)){
        selectors.push(
            'form button[type="submit"]',
            'form input[type="submit"]'
        );
    }

    document.querySelectorAll(selectors.join(',')).forEach(element => {
        element.classList.add('remote-change-control');
        element.dataset.configurationLocked = locked ? '1' : '0';

        if('disabled' in element){
            element.disabled = locked;
        }

        element.setAttribute(
            'aria-disabled',
            locked ? 'true' : 'false'
        );
        element.title = locked
            ? 'Unlock configuration changes first.'
            : '';
    });

    const changeLinks = [
        'a[href="/wireguard_create.php"]',
        'a[href="/openvpn_roadwarrior_create.php"]',
        'a[href="/aliases.php"]',
        'a[href^="/aliases.php?"]',
        'a[href="/categories.php"]',
        'a[href^="/categories.php?"]',
        'a[href^="/backup_download.php"]',
        'a[href^="/backup_zip_download.php"]',
        'form[action="/self_backup_download.php"] button[type="submit"]',
        '.backup-download-control'
    ];

    document.querySelectorAll(changeLinks.join(',')).forEach(link => {
        link.classList.add('remote-change-control');
        link.dataset.configurationLocked = locked ? '1' : '0';
        link.setAttribute(
            'aria-disabled',
            locked ? 'true' : 'false'
        );
        link.title = locked
            ? 'Unlock configuration changes first.'
            : '';
    });
}

document.addEventListener('click', function(event){
    const target = event.target.closest(
        '.remote-change-control[data-configuration-locked="1"]'
    );

    if(target){
        event.preventDefault();
        event.stopImmediatePropagation();

        document.getElementById(
            'configuration-lock-button'
        )?.click();
    }
}, true);

const lockButton = document.getElementById(
    'configuration-lock-button'
);
const unlockDialog = document.getElementById(
    'configuration-unlock-dialog'
);
const unlockPassword = document.getElementById(
    'configuration-unlock-password'
);
const unlockError = document.getElementById(
    'configuration-unlock-error'
);

async function submitConfigurationLock(action, password = ''){
    const form = new URLSearchParams({
        csrf: <?= json_encode(
            csrf_token(),
            JSON_UNESCAPED_SLASHES
        ) ?>,
        action,
        password
    });

    const response = await fetch('/configuration_lock.php', {
        method:'POST',
        credentials:'same-origin',
        cache:'no-store',
        headers:{
            'Content-Type':
                'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body:form
    });

    const raw = await response.text();
    let data;

    try{
        data = JSON.parse(raw);
    }catch(error){
        throw new Error(
            'Invalid server response: ' +
            raw.replace(/\s+/g, ' ').slice(0, 500)
        );
    }

    if(!response.ok || data.ok !== true){
        throw new Error(data.error || 'Lock action failed.');
    }

    window.location.reload();
}

lockButton?.addEventListener('click', async function(){
    const unlocked = lockButton.dataset.unlocked === '1';

    if(unlocked){
        lockButton.disabled = true;

        try{
            await submitConfigurationLock('lock');
        }catch(error){
            alert(error.message);
            lockButton.disabled = false;
        }

        return;
    }

    unlockError?.classList.add('hidden');
    if(unlockError) unlockError.textContent = '';
    if(unlockPassword) unlockPassword.value = '';
    if(unlockDialog) unlockDialog.hidden = false;
    window.setTimeout(() => unlockPassword?.focus(), 0);
});

document.getElementById(
    'configuration-unlock-cancel'
)?.addEventListener('click', function(){
    if(unlockDialog) unlockDialog.hidden = true;
});

document.getElementById(
    'configuration-unlock-submit'
)?.addEventListener('click', async function(){
    const submit = this;
    submit.disabled = true;
    unlockError?.classList.add('hidden');

    try{
        await submitConfigurationLock(
            'unlock',
            unlockPassword?.value || ''
        );
    }catch(error){
        if(unlockError){
            unlockError.textContent = error.message;
            unlockError.classList.remove('hidden');
        }
        submit.disabled = false;
        unlockPassword?.focus();
        unlockPassword?.select();
    }
});

unlockPassword?.addEventListener('keydown', function(event){
    if(event.key === 'Enter'){
        event.preventDefault();
        document.getElementById(
            'configuration-unlock-submit'
        )?.click();
    }

    if(event.key === 'Escape'){
        if(unlockDialog) unlockDialog.hidden = true;
    }
});

markRemoteChangeControls();

const remoteChangeObserver = new MutationObserver(function(){
    markRemoteChangeControls();
});

if(document.body.classList.contains('app-shell')){
    remoteChangeObserver.observe(
        document.body,
        {
            childList:true,
            subtree:true
        }
    );
}

if(document.body.classList.contains('app-shell')){
    window.setTimeout(function(){
        fetch('/update_check.php',{credentials:'same-origin',cache:'no-store'}).catch(function(){});
    },1500);
    window.setTimeout(function(){
        fetch('/telemetry_background.php',{credentials:'same-origin',cache:'no-store'}).catch(function(){});
    },3000);
}
</script>
