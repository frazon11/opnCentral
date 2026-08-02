<?php
declare(strict_types=1);

require_once __DIR__.'/inc/config.php';
require_login();

$firewalls = db()
    ->query('SELECT id,name,base_url FROM firewalls ORDER BY name')
    ->fetchAll();

$selectedId = (int)($_GET['firewall_id'] ?? 0);

if($selectedId < 1 && $firewalls){
    $selectedId = (int)$firewalls[0]['id'];
}

require __DIR__.'/inc/header.php';
?>

<div class="page-title management-page-title">
    <div>
        <h1>Manage OpenVPN</h1>
        <p>Instances and active sessions on one managed OPNsense.</p>
    </div>

    <div class="management-toolbar">
        <select id="firewall-select">
            <?php foreach($firewalls as $firewall): ?>
                <option
                    value="<?=(int)$firewall['id']?>"
                    <?=$selectedId === (int)$firewall['id']
                        ? 'selected'
                        : ''?>
                >
                    <?=h((string)$firewall['name'])?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="button secondary" id="refresh">
            Refresh
        </button>

        <a class="button" href="/openvpn_roadwarrior_create.php">
            Create Roadwarrior Server
        </a>
    </div>
</div>

<div class="management-overview-bar">
    <div>
        <strong>OpenVPN overview</strong>
        <div id="ovpn-summary" class="management-summary">
            Loading OpenVPN instances…
        </div>
    </div>
</div>

<div id="ovpn-error" class="alert error hidden"></div>

<div id="ovpn-list" class="vpn-summary-list">
    <section class="card vpn-summary-card">
        <p class="muted">Loading…</p>
    </section>
</div>

<section class="card vpn-summary-card openvpn-session-card">
    <div class="vpn-summary-main">
        <div class="vpn-summary-identity">
            <h2>Active sessions</h2>
            <span id="session-summary" class="muted">
                Loading…
            </span>
        </div>

        <div class="vpn-summary-metric">
            <span class="vpn-summary-label">Sessions</span>
            <span id="session-count" class="badge neutral">—</span>
        </div>

        <div class="vpn-summary-actions">
            <button
                type="button"
                id="session-details-toggle"
                class="button secondary"
                aria-expanded="false"
            >
                Details
            </button>
        </div>
    </div>

    <div id="session-details-panel" class="vpn-details-panel" hidden>
        <div class="vpn-details-header">
            <div>
                <strong>Connected OpenVPN clients</strong>
                <div class="muted">
                    Current sessions reported by the selected firewall.
                </div>
            </div>
        </div>
        <div id="session-table"></div>
    </div>
</section>

<script>
(function(){
    const firewallSelect = document.getElementById('firewall-select');
    const firewallId = () => firewallSelect.value;
    const csrf = <?=json_encode(
        csrf_token(),
        JSON_UNESCAPED_SLASHES
    )?>;

    const list = document.getElementById('ovpn-list');
    const summary = document.getElementById('ovpn-summary');
    const errorBox = document.getElementById('ovpn-error');
    const refresh = document.getElementById('refresh');
    const sessionSummary = document.getElementById('session-summary');
    const sessionCount = document.getElementById('session-count');
    const sessionTable = document.getElementById('session-table');
    const sessionToggle = document.getElementById(
        'session-details-toggle'
    );
    const sessionPanel = document.getElementById(
        'session-details-panel'
    );

    function escapeHtml(value){
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    async function readJson(response){
        const raw = await response.text();

        try{
            return JSON.parse(raw);
        }catch(error){
            throw new Error(
                'Invalid JSON: ' +
                raw.replace(/\s+/g, ' ').slice(0, 700)
            );
        }
    }

    function detailValue(value){
        const text = String(value ?? '').trim();
        return text === '' ? '—' : escapeHtml(text);
    }

    function instanceStatus(instance){
        return instance.enabled
            ? '<span class="badge good">Enabled</span>'
            : '<span class="badge neutral">Disabled</span>';
    }

    function listenerSummary(instance){
        const local = instance.local || '*';
        const port = instance.port
            ? ':' + instance.port
            : '';
        const proto = instance.proto
            ? ' ' + instance.proto
            : '';

        return escapeHtml(local + port + proto);
    }

    function actionButtons(instance, index){
        const toggleAction = instance.enabled
            ? 'disable'
            : 'enable';

        return `
            <div
                class="management-row-actions"
                data-uuid="${escapeHtml(instance.uuid)}"
                data-vpnid="${escapeHtml(instance.vpnid)}"
            >
                <button
                    class="button secondary"
                    data-action="${toggleAction}"
                    data-index="${index}"
                >
                    ${
                        instance.enabled
                            ? 'Disable'
                            : 'Enable'
                    }
                </button>
                <button
                    class="button secondary"
                    data-action="start"
                    data-index="${index}"
                >
                    Start
                </button>
                <button
                    class="button secondary"
                    data-action="stop"
                    data-index="${index}"
                >
                    Stop
                </button>
                <button
                    class="button secondary"
                    data-action="restart"
                    data-index="${index}"
                >
                    Restart
                </button>
                <button
                    class="button danger"
                    data-action="delete"
                    data-index="${index}"
                >
                    Delete
                </button>
            </div>
        `;
    }

    function renderInstanceDetails(instance, index){
        return `
            <div class="vpn-details-header">
                <div>
                    <strong>OpenVPN instance details</strong>
                    <div class="muted">
                        ID ${detailValue(instance.vpnid)}
                        ·
                        <code>${detailValue(instance.uuid)}</code>
                    </div>
                </div>
                ${actionButtons(instance, index)}
            </div>

            <div class="vpn-detail-grid">
                <div class="vpn-detail-side">
                    <dl>
                        <dt>Role</dt>
                        <dd>${detailValue(instance.role)}</dd>
                        <dt>Device type</dt>
                        <dd>${detailValue(instance.dev_type)}</dd>
                        <dt>Protocol</dt>
                        <dd>${detailValue(instance.proto)}</dd>
                        <dt>Port</dt>
                        <dd>${detailValue(instance.port)}</dd>
                    </dl>
                </div>

                <div class="vpn-detail-side">
                    <dl>
                        <dt>Bind address</dt>
                        <dd>${detailValue(instance.local || '*')}</dd>
                        <dt>Remote endpoint</dt>
                        <dd>${detailValue(instance.remote)}</dd>
                        <dt>Tunnel network</dt>
                        <dd>${detailValue(instance.server)}</dd>
                        <dt>UUID</dt>
                        <dd><code>${detailValue(instance.uuid)}</code></dd>
                    </dl>
                </div>
            </div>
        `;
    }

    function renderSessions(data){
        const sessions = Array.isArray(data.sessions)
            ? data.sessions
            : [];

        sessionCount.textContent = String(sessions.length);

        sessionSummary.textContent = data.sessions_error
            ? data.sessions_error
            : sessions.length +
                ' active session' +
                (sessions.length === 1 ? '' : 's');

        if(!sessions.length){
            sessionTable.innerHTML =
                '<div class="vpn-details-empty">' +
                    (
                        data.sessions_error
                            ? escapeHtml(data.sessions_error)
                            : 'No active OpenVPN sessions.'
                    ) +
                '</div>';
            return;
        }

        sessionTable.innerHTML = `
            <div class="table-scroll management-table-wrap">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>User / Common Name</th>
                            <th>Virtual address</th>
                            <th>Remote address</th>
                            <th>Connected</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sessions.map(function(session){
                            return `
                                <tr>
                                    <td>${detailValue(
                                        session.common_name ||
                                        session.username ||
                                        session.user_name
                                    )}</td>
                                    <td>${detailValue(
                                        session.virtual_address ||
                                        session.virtual_addr ||
                                        session.vpn_ip
                                    )}</td>
                                    <td>${detailValue(
                                        session.real_address ||
                                        session.remote_address ||
                                        session.remote_host
                                    )}</td>
                                    <td>${detailValue(
                                        session.connected_since ||
                                        session.connect_time ||
                                        session.since
                                    )}</td>
                                </tr>
                            `;
                        }).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function render(data){
        const instances = Array.isArray(data.instances)
            ? data.instances
            : [];

        const enabled = instances.filter(
            instance => instance.enabled
        ).length;
        const disabled = instances.length - enabled;

        summary.innerHTML =
            '<span class="badge good">' +
                enabled + ' enabled</span> ' +
            '<span class="badge neutral">' +
                disabled + ' disabled</span> ' +
            '<span class="muted">on ' +
                escapeHtml(data.firewall.name) +
            '</span>';

        if(!instances.length){
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'No OpenVPN instances found.' +
                    '</p>' +
                '</section>';
        }else{
            list.innerHTML = instances.map(function(instance, index){
                return `
                    <section class="card vpn-summary-card">
                        <div class="vpn-summary-main">
                            <div class="vpn-summary-identity">
                                <h2>${escapeHtml(
                                    instance.description || 'Unnamed'
                                )}</h2>
                                <span class="muted">
                                    ID ${detailValue(instance.vpnid)}
                                    ·
                                    ${escapeHtml(
                                        instance.role || 'unknown role'
                                    )}
                                </span>
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    Status
                                </span>
                                ${instanceStatus(instance)}
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    Listener / Remote
                                </span>
                                <span class="muted">
                                    ${
                                        instance.remote
                                            ? escapeHtml(instance.remote)
                                            : listenerSummary(instance)
                                    }
                                </span>
                            </div>

                            <div class="vpn-summary-actions">
                                <button
                                    type="button"
                                    class="button secondary vpn-details-toggle"
                                    aria-expanded="false"
                                >
                                    Details
                                </button>
                            </div>
                        </div>

                        <div class="vpn-details-panel" hidden>
                            ${renderInstanceDetails(instance, index)}
                        </div>
                    </section>
                `;
            }).join('');
        }

        list.querySelectorAll('.vpn-details-toggle').forEach(
            function(button){
                button.addEventListener('click', function(){
                    const card = button.closest('.vpn-summary-card');
                    const panel = card.querySelector(
                        '.vpn-details-panel'
                    );
                    const expanded =
                        button.getAttribute('aria-expanded') === 'true';

                    button.setAttribute(
                        'aria-expanded',
                        expanded ? 'false' : 'true'
                    );
                    button.textContent = expanded
                        ? 'Details'
                        : 'Hide details';
                    panel.hidden = expanded;
                    card.classList.toggle(
                        'vpn-summary-expanded',
                        !expanded
                    );
                });
            }
        );

        list.querySelectorAll('[data-action]').forEach(
            function(button){
                button.addEventListener(
                    'click',
                    () => runAction(button)
                );
            }
        );

        renderSessions(data);
    }

    async function load(){
        refresh.disabled = true;
        errorBox.classList.add('hidden');

        try{
            const response = await fetch(
                '/openvpn_manage_data.php?firewall_id=' +
                encodeURIComponent(firewallId()),
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );

            const data = await readJson(response);

            if(!response.ok || data.ok !== true){
                throw new Error(data.error || 'Load failed.');
            }

            render(data);
        }catch(error){
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
            summary.textContent = 'OpenVPN unavailable';
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'Could not load OpenVPN instances.' +
                    '</p>' +
                '</section>';
            sessionSummary.textContent = 'Unavailable';
            sessionCount.textContent = '—';
            sessionTable.innerHTML = '';
        }finally{
            refresh.disabled = false;
        }
    }

    async function runAction(button){
        const action = button.dataset.action;
        const row = button.closest('[data-uuid]');
        const uuid = row.dataset.uuid;
        const vpnid = row.dataset.vpnid;

        const destructive = ['delete', 'disable'].includes(action);

        if(!confirm(
            action.toUpperCase() +
            ' OpenVPN instance ' + vpnid + '?' +
            (
                destructive
                    ? '\n\nA configuration backup will be created first.'
                    : ''
            )
        )){
            return;
        }

        button.disabled = true;

        try{
            const form = new URLSearchParams({
                csrf,
                firewall_id: firewallId(),
                uuid,
                vpnid,
                action
            });

            const response = await fetch(
                '/openvpn_manage_action.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: form
                }
            );

            const data = await readJson(response);

            if(!response.ok || data.ok !== true){
                throw new Error(data.error || 'Action failed.');
            }

            await load();
        }catch(error){
            alert(error.message);
        }finally{
            button.disabled = false;
        }
    }

    sessionToggle.addEventListener('click', function(){
        const expanded =
            sessionToggle.getAttribute('aria-expanded') === 'true';

        sessionToggle.setAttribute(
            'aria-expanded',
            expanded ? 'false' : 'true'
        );
        sessionToggle.textContent = expanded
            ? 'Details'
            : 'Hide details';
        sessionPanel.hidden = expanded;
        document.querySelector('.openvpn-session-card')
            .classList.toggle(
                'vpn-summary-expanded',
                !expanded
            );
    });

    firewallSelect.addEventListener('change', load);
    refresh.addEventListener('click', load);

    load();
})();
</script>

<?php require __DIR__.'/inc/footer.php'; ?>
