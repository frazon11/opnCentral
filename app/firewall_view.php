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
        <h2><?= h(t('common.system')) ?></h2>
        <div id="system-state" class="live-status loading">
            Loading live system status…
        </div>
        <pre id="system-output"><?= h(t('common.loading')) ?></pre>
    </section>

    <section class="card live-card">
        <h2><?= h(t('details.firmware')) ?></h2>
        <div id="firmware-state" class="live-status loading">
            Loading firmware information…
        </div>

        <div class="version-grid">
            <div class="version-box">
                <strong><?= h(t('dashboard.current_version')) ?></strong>
                <span id="current-version"><?= h(t('common.loading')) ?></span>
            </div>
            <div class="version-box">
                <strong><?= h(t('dashboard.available_version')) ?></strong>
                <span id="available-version">Not checked</span>
            </div>
        </div>

        <div id="firmware-message"><?= h(t('common.loading')) ?></div>
        <pre id="firmware-output"><?= h(t('common.loading')) ?></pre>
    </section>


    <section class="card live-card wide">
        <div class="card-head">
            <div>
                <h2><?= h(t('details.site_vpn')) ?></h2>
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
                <div id="wireguard-summary" class="vpn-summary"><?= h(t('common.loading')) ?></div>
                <div id="wireguard-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary><?= h(t('details.raw_api')) ?></summary>
                    <pre id="wireguard-raw"><?= h(t('common.loading')) ?></pre>
                </details>
            </div>

            <div class="vpn-panel">
                <h3>IPsec</h3>
                <div id="ipsec-summary" class="vpn-summary"><?= h(t('common.loading')) ?></div>
                <div id="ipsec-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary><?= h(t('details.raw_api')) ?></summary>
                    <pre id="ipsec-raw"><?= h(t('common.loading')) ?></pre>
                </details>
            </div>

            <div class="vpn-panel">
                <h3>OpenVPN</h3>
                <div id="openvpn-summary" class="vpn-summary"><?= h(t('common.loading')) ?></div>
                <div id="openvpn-list" class="vpn-list"></div>
                <details class="vpn-raw">
                    <summary><?= h(t('details.raw_api')) ?></summary>
                    <pre id="openvpn-raw"><?= h(t('common.loading')) ?></pre>
                </details>
            </div>
        </div>
    </section>


    <section class="card live-card">
        <h2><?= h(t('details.firmware_actions')) ?></h2>

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
    const tr = {"common.loading_short":<?= json_encode(t('common.loading_short'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.loading":<?= json_encode(t('common.loading'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.online":<?= json_encode(t('common.online'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.offline":<?= json_encode(t('common.offline'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.reachable":<?= json_encode(t('common.reachable'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.unavailable":<?= json_encode(t('common.unavailable'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.unknown":<?= json_encode(t('common.unknown'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.not_checked":<?= json_encode(t('common.not_checked'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.no_update":<?= json_encode(t('common.no_update'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.update_available":<?= json_encode(t('common.update_available'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.update_now":<?= json_encode(t('common.update_now'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.upgrade_now":<?= json_encode(t('common.upgrade_now'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.loading_firmware":<?= json_encode(t('dashboard.loading_firmware'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.start_update_confirm":<?= json_encode(t('dashboard.start_update_confirm'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.start_upgrade_confirm":<?= json_encode(t('dashboard.start_upgrade_confirm'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.starting_update":<?= json_encode(t('dashboard.starting_update'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.starting_upgrade":<?= json_encode(t('dashboard.starting_upgrade'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.action_started":<?= json_encode(t('dashboard.action_started'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_system":<?= json_encode(t('details.loading_system'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_firmware":<?= json_encode(t('details.loading_firmware'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_vpn":<?= json_encode(t('details.loading_vpn'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>};
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
            output.textContent = payload?.error || tr['common.unavailable'];
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
            output.textContent = payload?.error || tr['common.unavailable'];
            current.textContent = tr['common.unknown'];
            available.textContent = tr['common.unknown'];
            message.textContent = payload?.error || tr['common.unavailable'];
            return;
        }

        const summary = payload.summary || {};

        state.classList.add('good');
        state.textContent = 'Firmware status loaded.';
        current.textContent = summary.current_version || tr['common.unknown'];
        available.textContent = summary.update_available
            ? (summary.available_version || tr['common.update_available'])
            : (summary.checked ? tr['common.no_update'] : tr['common.not_checked']);
        message.textContent = summary.message || '';
        output.textContent = JSON.stringify(payload.value, null, 2);

        if (summary.update_available && summary.action) {
            firmwareAction = summary.action;
            installButton.textContent = summary.action_label || tr['common.update_now'];
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

    function normalisedStatus(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    function rowIsOnline(row) {
        const peerStatus = normalisedStatus(row?.['peer-status']);
        if (peerStatus) {
            return peerStatus === 'online';
        }

        const status = normalisedStatus(
            textValue(row, ['status', 'state', 'connected', 'active', 'running'])
        );

        return ['up', 'online', 'ok', 'established', 'connected', 'active', 'true', 'running']
            .includes(status);
    }

    function formatBytes(value) {
        const bytes = Number(value);
        if (!Number.isFinite(bytes) || bytes < 0) {
            return '';
        }

        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let amount = bytes;
        let unit = 0;
        while (amount >= 1024 && unit < units.length - 1) {
            amount /= 1024;
            unit += 1;
        }
        return (unit === 0 ? amount.toFixed(0) : amount.toFixed(1)) + ' ' + units[unit];
    }

    function appendVpnRow(container, options) {
        const box = document.createElement('div');
        box.className = 'vpn-row';

        const title = document.createElement('strong');
        title.textContent = options.name;

        const badge = document.createElement('span');
        badge.className = 'badge ' + (options.online ? 'good' : 'bad');
        badge.textContent = options.status;

        box.appendChild(title);
        box.appendChild(badge);

        if (options.meta) {
            const meta = document.createElement('div');
            meta.className = 'vpn-meta';
            meta.textContent = options.meta;
            box.appendChild(meta);
        }

        container.appendChild(box);
    }

    function showVpnErrors(summary, errors) {
        if (!errors.length) {
            return;
        }

        const errorText = document.createElement('span');
        errorText.className = 'vpn-meta';
        errorText.textContent = errors.join(' | ');
        summary.appendChild(errorText);
    }

    function apiErrors(payload) {
        const errors = [];
        Object.entries(payload || {}).forEach(function ([key, result]) {
            if (!result || result.ok !== true) {
                errors.push(key + ': ' + (result?.error || 'Unavailable'));
            }
        });
        return errors;
    }

    function renderWireGuard(payload) {
        const summary = document.getElementById('wireguard-summary');
        const list = document.getElementById('wireguard-list');
        const raw = document.getElementById('wireguard-raw');
        raw.textContent = JSON.stringify(payload, null, 2);
        summary.textContent = '';
        list.textContent = '';

        const errors = apiErrors(payload);
        const rows = payload?.tunnels?.ok === true
            ? firstArray(payload.tunnels.value)
            : [];
        const interfaces = rows.filter(row => row?.type === 'interface');
        const peers = rows.filter(row => row?.type === 'peer');
        const onlinePeers = peers.filter(rowIsOnline).length;
        const interfaceUp = interfaces.some(row => normalisedStatus(row.status) === 'up');

        const badge = document.createElement('span');
        badge.className = 'badge ' + (interfaceUp || onlinePeers > 0 ? 'good' : 'bad');
        badge.textContent = peers.length
            ? onlinePeers + ' online / ' + peers.length + ' peers'
            : (interfaceUp ? 'Interface up · no peers returned' : 'No peers returned');
        summary.appendChild(badge);

        const interfaceText = document.createElement('span');
        interfaceText.className = 'vpn-meta';
        interfaceText.textContent = interfaces.length
            ? 'Interface: ' + (interfaceUp ? 'Up' : textValue(interfaces[0], ['status'], 'Unknown'))
            : 'Interface status unavailable';
        summary.appendChild(interfaceText);
        showVpnErrors(summary, errors);

        peers.forEach(function (row, index) {
            const online = rowIsOnline(row);
            const handshakeAge = row['latest-handshake-age'];
            const handshakeTime = row['latest-handshake-epoch'];
            const rx = formatBytes(row['transfer-rx']);
            const tx = formatBytes(row['transfer-tx']);
            const meta = [
                row.endpoint ? 'Endpoint: ' + row.endpoint : '',
                row['allowed-ips'] ? 'Networks: ' + row['allowed-ips'] : '',
                handshakeTime ? 'Last handshake: ' + handshakeTime : '',
                handshakeAge !== null && handshakeAge !== undefined ? 'Age: ' + handshakeAge + ' s' : '',
                rx ? 'RX: ' + rx : '',
                tx ? 'TX: ' + tx : ''
            ].filter(Boolean).join(' · ');

            appendVpnRow(list, {
                name: textValue(row, ['name', 'ifname'], 'WireGuard peer ' + (index + 1)),
                online: online,
                status: online ? 'Online' : 'Offline',
                meta: meta
            });
        });

        if (!peers.length) {
            list.textContent = 'No WireGuard peers returned.';
            list.className = 'vpn-list vpn-empty';
        } else {
            list.className = 'vpn-list';
        }
    }

    function renderIpsec(payload) {
        const summary = document.getElementById('ipsec-summary');
        const list = document.getElementById('ipsec-list');
        const raw = document.getElementById('ipsec-raw');
        raw.textContent = JSON.stringify(payload, null, 2);
        summary.textContent = '';
        list.textContent = '';
        list.className = 'vpn-list';

        const errors = apiErrors(payload);
        const serviceStatus = normalisedStatus(payload?.service?.value?.status);
        const phase1 = payload?.phase1?.ok === true ? firstArray(payload.phase1.value) : [];
        const phase2 = payload?.phase2?.ok === true ? firstArray(payload.phase2.value) : [];
        const disabled = serviceStatus === 'disabled';
        const establishedP1 = phase1.filter(rowIsOnline).length;
        const establishedP2 = phase2.filter(rowIsOnline).length;

        const badge = document.createElement('span');
        badge.className = 'badge ' + (disabled ? 'neutral' : (establishedP1 || establishedP2 ? 'good' : 'bad'));
        badge.textContent = disabled
            ? 'Disabled'
            : 'Phase 1: ' + establishedP1 + '/' + phase1.length +
              ' · Phase 2: ' + establishedP2 + '/' + phase2.length;
        summary.appendChild(badge);
        showVpnErrors(summary, errors);

        const rows = phase1.map(row => ({...row, _phase: 'Phase 1'}))
            .concat(phase2.map(row => ({...row, _phase: 'Phase 2'})));

        rows.forEach(function (row, index) {
            const online = rowIsOnline(row);
            appendVpnRow(list, {
                name: textValue(row, ['description', 'name', 'connection', 'id'], row._phase + ' ' + (index + 1)),
                online: online,
                status: online ? 'Established' : textValue(row, ['status', 'state'], 'Not established'),
                meta: [
                    row._phase,
                    textValue(row, ['remote', 'remote_host', 'remote_address', 'peer'])
                        ? 'Remote: ' + textValue(row, ['remote', 'remote_host', 'remote_address', 'peer'])
                        : ''
                ].filter(Boolean).join(' · ')
            });
        });

        if (!rows.length) {
            list.className = 'vpn-list vpn-empty';
            list.textContent = disabled
                ? 'IPsec is disabled and no Phase 1 or Phase 2 sessions are active.'
                : 'No active IPsec Phase 1 or Phase 2 sessions returned.';
        }
    }

    function isRoadwarriorSession(row) {
        const description = normalisedStatus(row?.description);
        const username = String(row?.username ?? '').trim();
        return description.includes('roadwarrior') ||
            description.includes('road warrior') ||
            username !== '' ||
            row?.is_client === true;
    }

    function renderOpenVpn(payload) {
        const summary = document.getElementById('openvpn-summary');
        const list = document.getElementById('openvpn-list');
        const raw = document.getElementById('openvpn-raw');
        raw.textContent = JSON.stringify(payload, null, 2);
        summary.textContent = '';
        list.textContent = '';
        list.className = 'vpn-list';

        const errors = apiErrors(payload);
        const sessions = payload?.sessions?.ok === true
            ? firstArray(payload.sessions.value)
            : [];
        const roadwarriors = sessions.filter(isRoadwarriorSession);
        const siteToSite = sessions.filter(row => !isRoadwarriorSession(row));
        const onlineSiteToSite = siteToSite.filter(rowIsOnline).length;

        const badge = document.createElement('span');
        badge.className = 'badge ' + (onlineSiteToSite > 0 ? 'good' : 'neutral');
        badge.textContent = 'Site-to-site: ' + onlineSiteToSite + '/' + siteToSite.length +
            ' · Roadwarriors: ' + roadwarriors.length + ' connected';
        summary.appendChild(badge);
        showVpnErrors(summary, errors);

        siteToSite.forEach(function (row, index) {
            const online = rowIsOnline(row);
            appendVpnRow(list, {
                name: textValue(row, ['description', 'common_name', 'id'], 'OpenVPN tunnel ' + (index + 1)),
                online: online,
                status: online ? 'Connected' : textValue(row, ['status', 'state'], 'Status unknown'),
                meta: [
                    row.real_address ? 'Remote: ' + row.real_address : '',
                    row.virtual_address ? 'Tunnel address: ' + row.virtual_address : '',
                    row.connected_since ? 'Connected since: ' + row.connected_since : ''
                ].filter(Boolean).join(' · ')
            });
        });

        if (!siteToSite.length) {
            const empty = document.createElement('div');
            empty.className = 'vpn-empty';
            empty.textContent = 'No OpenVPN site-to-site sessions detected.';
            list.appendChild(empty);
        }

        if (roadwarriors.length) {
            const rw = document.createElement('div');
            rw.className = 'vpn-row';
            const title = document.createElement('strong');
            title.textContent = 'Roadwarrior sessions';
            const rwBadge = document.createElement('span');
            rwBadge.className = 'badge good';
            rwBadge.textContent = roadwarriors.length + ' connected';
            const meta = document.createElement('div');
            meta.className = 'vpn-meta';
            meta.textContent = 'Displayed as a separate count; route records are not counted as tunnels.';
            rw.appendChild(title);
            rw.appendChild(rwBadge);
            rw.appendChild(meta);
            list.appendChild(rw);
        }
    }

    function renderVpnType(type, payload) {
        if (type === 'wireguard') {
            renderWireGuard(payload);
        } else if (type === 'ipsec') {
            renderIpsec(payload);
        } else if (type === 'openvpn') {
            renderOpenVpn(payload);
        }
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
                    tr['common.unavailable'];
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
