<?php

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$firewall = firewall_by_id($id);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'backup') {
            if (
                !is_dir(BACKUP_DIR)
                && !mkdir(BACKUP_DIR, 0770, true)
                && !is_dir(BACKUP_DIR)
            ) {
                throw new RuntimeException('Cannot create the backup directory.');
            }

            $safeName = preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '_',
                (string) $firewall['name']
            );

            $filename =
                BACKUP_DIR . '/' .
                $safeName . '-' .
                gmdate('Ymd-His') .
                '.xml';

            $backupData = opn_download(
                $firewall,
                'core/backup/download/this'
            );

            if ($backupData === '') {
                throw new RuntimeException(
                    'OPNsense returned an empty configuration backup.'
                );
            }

            if (file_put_contents($filename, $backupData, LOCK_EX) === false) {
                throw new RuntimeException(
                    'The configuration backup could not be saved.'
                );
            }

            $message = 'Backup saved: ' . basename($filename);
        } elseif ($action === 'reboot') {
            opn_request(
                $firewall,
                'core/system/reboot',
                'POST',
                []
            );

            $message = 'Reboot command submitted.';
        } elseif ($action === 'delete') {
            $statement = db()->prepare(
                'DELETE FROM firewalls WHERE id = ?'
            );
            $statement->execute([$id]);

            header('Location: /');
            exit;
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

require __DIR__ . '/inc/header.php';

?>

<style>
.live-card pre{min-height:110px}
.live-status{font-size:.9rem;opacity:.72;margin-bottom:8px}
.live-status.loading::before{content:"● ";animation:pulse 1s infinite}
.live-status.good{color:#35a853}
.live-status.bad{color:#d74747}
.version-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;margin:12px 0}
.version-box{padding:10px;border-radius:8px;background:rgba(127,127,127,.08)}
.version-box strong{display:block;margin-bottom:4px}
.hidden{display:none!important}
@keyframes pulse{0%,100%{opacity:.25}50%{opacity:1}}
</style>

<div class="page-title">
    <div>
        <h1><?= h((string) $firewall['name']) ?></h1>
        <p><?= h((string) $firewall['base_url']) ?></p>
    </div>

    <a
        class="button secondary"
        target="_blank"
        rel="noopener"
        href="<?= h((string) $firewall['base_url']) ?>"
    >
        Open WebGUI
    </a>
</div>

<?php if ($message): ?>
    <div class="alert goodbox"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<div id="ajax-message" class="alert goodbox hidden"></div>
<div id="ajax-error" class="alert error hidden"></div>

<div class="detail-grid">
    <section class="card live-card">
        <h2>System</h2>
        <div id="system-state" class="live-status loading">
            Loading live system status…
        </div>
        <pre id="system-output">Loading…</pre>
    </section>

    <section class="card live-card">
        <h2>Firmware</h2>
        <div id="firmware-state" class="live-status loading">
            Loading firmware information…
        </div>

        <div class="version-grid">
            <div class="version-box">
                <strong>Current version</strong>
                <span id="current-version">Loading…</span>
            </div>
            <div class="version-box">
                <strong>Available version</strong>
                <span id="available-version">Not checked</span>
            </div>
        </div>

        <div id="firmware-message">Loading…</div>
        <pre id="firmware-output">Loading…</pre>
    </section>

    <section class="card live-card">
        <h2>Firmware actions</h2>

        <div id="check-state" class="live-status">
            Ready. Click “Check for updates”.
        </div>

        <button type="button" id="firmware-check-button">
            Check for updates
        </button>

        <button
            type="button"
            id="firmware-install-button"
            class="warning hidden"
        >
            Update now
        </button>
    </section>
</div>

<form method="post" class="actions danger-zone">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <button name="action" value="backup">
        Download configuration backup
    </button>

    <button
        class="warning"
        name="action"
        value="reboot"
        onclick="return confirm('Really reboot this firewall?')"
    >
        Reboot firewall
    </button>

    <button
        class="danger"
        name="action"
        value="delete"
        onclick="return confirm(
            'Delete this firewall from OpnCentral?'
        )"
    >
        Delete entry
    </button>
</form>

<script>
(function () {
    const firewallId = <?= (int) $firewall['id'] ?>;
    const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    const checkButton = document.getElementById('firmware-check-button');
    const installButton = document.getElementById('firmware-install-button');
    let firmwareAction = null;

    function setNotice(message, isError) {
        const good = document.getElementById('ajax-message');
        const bad = document.getElementById('ajax-error');

        good.classList.add('hidden');
        bad.classList.add('hidden');

        const target = isError ? bad : good;
        target.textContent = message;
        target.classList.remove('hidden');
    }

    function showSystem(payload) {
        const state = document.getElementById('system-state');
        const output = document.getElementById('system-output');

        state.classList.remove('loading', 'good', 'bad');

        if (!payload || payload.ok !== true) {
            state.classList.add('bad');
            state.textContent = 'Could not load live status.';
            output.textContent = payload?.error || 'Unavailable';
            return;
        }

        state.classList.add('good');
        state.textContent = 'Live status loaded.';
        output.textContent = JSON.stringify(payload.value, null, 2);
    }

    function showFirmware(payload) {
        const state = document.getElementById('firmware-state');
        const output = document.getElementById('firmware-output');
        const current = document.getElementById('current-version');
        const available = document.getElementById('available-version');
        const message = document.getElementById('firmware-message');

        state.classList.remove('loading', 'good', 'bad');
        installButton.classList.add('hidden');
        firmwareAction = null;

        if (!payload || payload.ok !== true) {
            state.classList.add('bad');
            state.textContent = 'Could not load firmware status.';
            output.textContent = payload?.error || 'Unavailable';
            current.textContent = 'Unknown';
            available.textContent = 'Unknown';
            message.textContent = payload?.error || 'Unavailable';
            return;
        }

        const summary = payload.summary || {};

        state.classList.add('good');
        state.textContent = 'Firmware status loaded.';
        current.textContent = summary.current_version || 'Unknown';
        available.textContent = summary.update_available
            ? (summary.available_version || 'Update available')
            : (summary.checked ? 'No update available' : 'Not checked');
        message.textContent = summary.message || '';
        output.textContent = JSON.stringify(payload.value, null, 2);

        if (summary.update_available && summary.action) {
            firmwareAction = summary.action;
            installButton.textContent = summary.action_label || 'Update now';
            installButton.classList.remove('hidden');
        }
    }

    async function fetchStatus(type) {
        const response = await fetch(
            '/firewall_status.php?id=' +
            encodeURIComponent(firewallId) +
            '&type=' +
            encodeURIComponent(type),
            {
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );

        const result = await response.json();

        if (!response.ok || result.ok !== true) {
            throw new Error(result.error || 'HTTP ' + response.status);
        }

        return result.data[type];
    }

    async function runAction(action) {
        const body = new URLSearchParams();
        body.set('csrf', csrfToken);
        body.set('id', String(firewallId));
        body.set('action', action);

        const response = await fetch('/firewall_action.php', {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
            },
            body
        });

        const result = await response.json();

        if (!response.ok || result.ok !== true) {
            throw new Error(result.error || 'HTTP ' + response.status);
        }

        return result;
    }

    async function loadSystem() {
        try {
            showSystem(await fetchStatus('system'));
        } catch (error) {
            showSystem({ok: false, error: error.message});
        }
    }

    async function loadFirmware() {
        try {
            showFirmware(await fetchStatus('firmware'));
        } catch (error) {
            showFirmware({ok: false, error: error.message});
        }
    }

    checkButton.addEventListener('click', async function () {
        checkButton.disabled = true;
        checkButton.textContent = 'Checking…';
        document.getElementById('check-state').textContent =
            'OPNsense is checking its firmware mirror…';

        try {
            const result = await runAction('firmware_check');

            showFirmware({
                ok: true,
                value: result.value,
                summary: result.summary
            });

            document.getElementById('check-state').textContent =
                'Firmware check completed.';
            setNotice('Firmware check completed.', false);
        } catch (error) {
            document.getElementById('check-state').textContent =
                'Firmware check failed.';
            setNotice(error.message, true);
        } finally {
            checkButton.disabled = false;
            checkButton.textContent = 'Check for updates';
        }
    });

    installButton.addEventListener('click', async function () {
        if (!firmwareAction) {
            return;
        }

        const isUpgrade = firmwareAction === 'firmware_upgrade';
        const question = isUpgrade
            ? 'Start the major OPNsense upgrade now? The firewall will reboot and may be unavailable for an extended period.'
            : 'Install the available OPNsense update now? The firewall may reboot and temporarily become unavailable.';

        if (!confirm(question)) {
            return;
        }

        installButton.disabled = true;
        installButton.textContent = isUpgrade
            ? 'Starting upgrade…'
            : 'Starting update…';

        try {
            const result = await runAction(firmwareAction);
            setNotice(result.message || 'Firmware action started.', false);
            installButton.classList.add('hidden');
        } catch (error) {
            setNotice(error.message, true);
        } finally {
            installButton.disabled = false;
        }
    });

    loadSystem();
    loadFirmware();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
