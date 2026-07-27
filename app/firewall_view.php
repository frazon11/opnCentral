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
.vpn-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px}
.vpn-panel{padding:14px;border-radius:9px;background:rgba(127,127,127,.07)}
.vpn-panel h3{margin:0 0 10px}
.vpn-summary{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:10px}
.vpn-list{display:grid;gap:8px}
.vpn-row{padding:9px;border-radius:7px;background:rgba(127,127,127,.08)}
.vpn-row strong{display:block;margin-bottom:3px}
.vpn-meta{font-size:.88rem;opacity:.78;word-break:break-word}
.vpn-empty{opacity:.7}
.vpn-raw{margin-top:10px}
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


    <section class="card live-card wide">
        <div class="card-head">
            <div>
                <h2>Site-to-site VPN</h2>
                <div id="vpn-state" class="live-status loading">
                    Loading WireGuard, IPsec and OpenVPN status…
                </div>
            </div>

            <button type="button" id="vpn-refresh-button" class="secondary">
                Refresh VPN status
            </button>
        </div>

        <div class="vpn-grid">
            <div class="vpn-panel">
                <h3>WireGuard</h3>
                <div id="wireguard-summary" class="vpn-summary">Loading…</div>
                <div id="wireguard-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary>Raw API data</summary>
                    <pre id="wireguard-raw">Loading…</pre>
                </details>
            </div>

            <div class="vpn-panel">
                <h3>IPsec</h3>
                <div id="ipsec-summary" class="vpn-summary">Loading…</div>
                <div id="ipsec-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary>Raw API data</summary>
                    <pre id="ipsec-raw">Loading…</pre>
                </details>
            </div>

            <div class="vpn-panel">
                <h3>OpenVPN</h3>
                <div id="openvpn-summary" class="vpn-summary">Loading…</div>
                <div id="openvpn-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary>Raw API data</summary>
                    <pre id="openvpn-raw">Loading…</pre>
                </details>
            </div>
        </div>
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

        const responseText = await response.text();
        let result;

        try {
            result = JSON.parse(responseText);
        } catch (error) {
            throw new Error(
                'Server returned invalid JSON: ' +
                responseText.replace(/\s+/g, ' ').trim().slice(0, 500)
            );
        }

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

        const responseText = await response.text();
        let result;

        try {
            result = JSON.parse(responseText);
        } catch (error) {
            throw new Error(
                'Server returned invalid JSON: ' +
                responseText.replace(/\s+/g, ' ').trim().slice(0, 500)
            );
        }

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


    function firstArray(value) {
        if (Array.isArray(value)) {
            return value;
        }

        if (!value || typeof value !== 'object') {
            return [];
        }

        for (const key of ['rows', 'items', 'data', 'sessions', 'tunnels', 'peers']) {
            if (Array.isArray(value[key])) {
                return value[key];
            }
        }

        return [];
    }

    function textValue(row, keys, fallback = '') {
        for (const key of keys) {
            if (
                row &&
                row[key] !== undefined &&
                row[key] !== null &&
                String(row[key]).trim() !== ''
            ) {
                return String(row[key]);
            }
        }

        return fallback;
    }

    function isConnected(row) {
        const combined = [
            textValue(row, ['status', 'state']),
            textValue(row, ['connected', 'active', 'running']),
            textValue(row, ['latest_handshake', 'last_handshake', 'handshake'])
        ].join(' ').toLowerCase();

        return (
            combined.includes('up') ||
            combined.includes('established') ||
            combined.includes('connected') ||
            combined.includes('active') ||
            combined.includes('true') ||
            combined.includes('running')
        );
    }

    function renderRows(containerId, rows, type) {
        const container = document.getElementById(containerId);
        container.textContent = '';

        if (!rows.length) {
            const empty = document.createElement('div');
            empty.className = 'vpn-empty';
            empty.textContent = 'No active or configured sessions returned.';
            container.appendChild(empty);
            return;
        }

        rows.forEach(function (row, index) {
            const box = document.createElement('div');
            box.className = 'vpn-row';

            const name = textValue(
                row,
                ['name', 'description', 'instance', 'id', 'common_name', 'remote'],
                type + ' tunnel ' + (index + 1)
            );

            const status = textValue(
                row,
                ['status', 'state', 'connected', 'active', 'running'],
                isConnected(row) ? 'Connected' : 'Status unknown'
            );

            const endpoint = textValue(
                row,
                [
                    'endpoint',
                    'remote',
                    'remote_host',
                    'remote_address',
                    'peer',
                    'src',
                    'dst'
                ]
            );

            const handshake = textValue(
                row,
                [
                    'latest_handshake',
                    'last_handshake',
                    'handshake',
                    'established'
                ]
            );

            const title = document.createElement('strong');
            title.textContent = name;

            const badge = document.createElement('span');
            badge.className = 'badge ' + (isConnected(row) ? 'good' : 'bad');
            badge.textContent = status;

            const meta = document.createElement('div');
            meta.className = 'vpn-meta';
            meta.textContent = [
                endpoint ? 'Endpoint: ' + endpoint : '',
                handshake ? 'Handshake/established: ' + handshake : ''
            ].filter(Boolean).join(' · ');

            box.appendChild(title);
            box.appendChild(badge);

            if (meta.textContent) {
                box.appendChild(meta);
            }

            container.appendChild(box);
        });
    }

    function renderVpnType(type, payload) {
        const summary = document.getElementById(type + '-summary');
        const raw = document.getElementById(type + '-raw');

        raw.textContent = JSON.stringify(payload, null, 2);

        let rows = [];
        let errors = [];

        Object.entries(payload || {}).forEach(function ([key, result]) {
            if (!result || result.ok !== true) {
                if (result?.error) {
                    errors.push(key + ': ' + result.error);
                }
                return;
            }

            rows = rows.concat(firstArray(result.value));
        });

        const connected = rows.filter(isConnected).length;

        summary.textContent = '';

        const statusBadge = document.createElement('span');
        statusBadge.className = 'badge ' + (
            errors.length && !rows.length ? 'bad' : 'good'
        );
        statusBadge.textContent = rows.length
            ? connected + ' connected / ' + rows.length + ' returned'
            : (errors.length ? 'Unavailable' : 'No sessions');

        summary.appendChild(statusBadge);

        if (errors.length) {
            const errorText = document.createElement('span');
            errorText.className = 'vpn-meta';
            errorText.textContent = errors.join(' | ');
            summary.appendChild(errorText);
        }

        renderRows(type + '-list', rows, type);
    }

    async function loadVpn() {
        const state = document.getElementById('vpn-state');
        const button = document.getElementById('vpn-refresh-button');

        state.className = 'live-status loading';
        state.textContent = 'Loading WireGuard, IPsec and OpenVPN status…';
        button.disabled = true;

        try {
            const response = await fetch(
                '/vpn_status.php?id=' + encodeURIComponent(firewallId) +
                '&type=all',
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );

            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch (error) {
                throw new Error(
                    'Server returned invalid JSON: ' +
                    responseText.replace(/\s+/g, ' ').trim().slice(0, 500)
                );
            }

            if (!response.ok || result.ok !== true) {
                throw new Error(result.error || 'HTTP ' + response.status);
            }

            renderVpnType('wireguard', result.data.wireguard || {});
            renderVpnType('ipsec', result.data.ipsec || {});
            renderVpnType('openvpn', result.data.openvpn || {});

            state.className = 'live-status good';
            state.textContent = 'VPN status loaded.';
        } catch (error) {
            state.className = 'live-status bad';
            state.textContent = error.message;

            ['wireguard', 'ipsec', 'openvpn'].forEach(function (type) {
                document.getElementById(type + '-summary').textContent =
                    'Unavailable';
                document.getElementById(type + '-list').textContent = '';
                document.getElementById(type + '-raw').textContent =
                    error.message;
            });
        } finally {
            button.disabled = false;
        }
    }

    document.getElementById('vpn-refresh-button')
        ?.addEventListener('click', loadVpn);


    loadSystem();
    loadFirmware();
    loadVpn();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
