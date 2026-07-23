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
        } elseif ($action === 'firmware_check') {
            opn_request(
                $firewall,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $message = 'Firmware update check completed.';
        } elseif ($action === 'firmware_update') {
            opn_request(
                $firewall,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $updateResult = opn_request(
                $firewall,
                'core/firmware/update',
                'POST',
                [],
                30
            );

            $updateMessage =
                $updateResult['status_msg']
                ?? $updateResult['message']
                ?? $updateResult['status']
                ?? 'Firmware update command accepted.';

            $message =
                'Firmware update started: ' .
                (string) $updateMessage .
                ' The firewall may reboot and temporarily become unavailable.';
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
        <pre id="firmware-output">Loading…</pre>
    </section>

    <section class="card live-card">
        <h2>Update check</h2>
        <div id="check-state" class="live-status">
            Ready. Click “Check for updates”.
        </div>
        <pre id="check-output">No update check started.</pre>
    </section>
</div>

<form method="post" class="actions danger-zone">
    <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

    <button type="button" id="firmware-check-button">
        Check for updates
    </button>

    <button
        class="warning"
        name="action"
        value="firmware_update"
        onclick="return confirm(
            'Install available firmware updates now? ' +
            'The firewall may reboot and temporarily become unavailable.'
        )"
    >
        Update now
    </button>

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

    function showResult(section, payload) {
        const state = document.getElementById(section + '-state');
        const output = document.getElementById(section + '-output');

        state.classList.remove('loading', 'good', 'bad');

        if (!payload || payload.ok !== true) {
            state.classList.add('bad');
            state.textContent = 'Could not load live status.';
            output.textContent = payload && payload.error
                ? payload.error
                : 'Unavailable';
            return;
        }

        state.classList.add('good');
        state.textContent = 'Live status loaded.';
        output.textContent = JSON.stringify(payload.value, null, 2);
    }

    async function loadSection(section, type) {
        try {
            const response = await fetch(
                '/firewall_status.php?id=' + encodeURIComponent(firewallId) +
                '&type=' + encodeURIComponent(type),
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );

            const result = await response.json();

            if (!response.ok || result.ok !== true) {
                throw new Error(result.error || 'HTTP ' + response.status);
            }

            showResult(section, result.data[type]);
        } catch (error) {
            showResult(section, {
                ok: false,
                error: error.message
            });
        }
    }

    async function checkForUpdates() {
        const state = document.getElementById('check-state');
        const output = document.getElementById('check-output');
        const body = new URLSearchParams();

        body.set('csrf', csrfToken);
        body.set('id', String(firewallId));
        body.set('action', 'firmware_check');

        checkButton.disabled = true;
        state.className = 'live-status loading';
        state.textContent = 'Checking OPNsense repositories…';
        output.textContent = 'This may take a while. The page remains usable.';

        try {
            const response = await fetch('/firewall_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString()
            });

            const result = await response.json();

            if (!response.ok || result.ok !== true) {
                throw new Error(result.error || 'HTTP ' + response.status);
            }

            state.className = 'live-status good';
            state.textContent = 'Update check completed.';
            output.textContent = JSON.stringify(result.value, null, 2);

            await loadSection('firmware', 'firmware');
        } catch (error) {
            state.className = 'live-status bad';
            state.textContent = 'Update check failed.';
            output.textContent = error.message;
        } finally {
            checkButton.disabled = false;
        }
    }

    checkButton?.addEventListener('click', checkForUpdates);

    loadSection('system', 'system');
    loadSection('firmware', 'firmware');
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
