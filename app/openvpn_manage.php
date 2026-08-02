<?php
declare(strict_types=1);

require_once __DIR__.'/inc/config.php';
require_login();

$firewalls = db()
    ->query('SELECT id,name,base_url FROM firewalls ORDER BY name')
    ->fetchAll();

require __DIR__.'/inc/header.php';
?>

<div class="page-title management-page-title">
    <div>
        <h1>Manage OpenVPN</h1>
        <p>OpenVPN status grouped by managed OPNsense firewall.</p>
    </div>

    <div class="management-toolbar">
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
            Loading OpenVPN data…
        </div>
    </div>
</div>

<div id="ovpn-error" class="alert error hidden"></div>

<div id="ovpn-list" class="vpn-summary-list">
    <section class="card vpn-summary-card">
        <p class="muted">Loading…</p>
    </section>
</div>

<script>
(function(){
    const firewalls = <?= json_encode(
        array_map(
            static fn(array $firewall): array => [
                'id' => (int) $firewall['id'],
                'name' => (string) $firewall['name'],
                'base_url' => (string) $firewall['base_url'],
            ],
            $firewalls
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) ?>;

    const csrf = <?=json_encode(
        csrf_token(),
        JSON_UNESCAPED_SLASHES
    )?>;

    const list = document.getElementById('ovpn-list');
    const summary = document.getElementById('ovpn-summary');
    const errorBox = document.getElementById('ovpn-error');
    const refresh = document.getElementById('refresh');

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

    function statusBadge(enabled){
        return enabled
            ? '<span class="badge good">Enabled</span>'
            : '<span class="badge neutral">Disabled</span>';
    }

    function sessionRows(sessions){
        if(!sessions.length){
            return `<tr>
                <td colspan="4">No active OpenVPN sessions.</td>
            </tr>`;
        }

        return sessions.map(function(session){
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
        }).join('');
    }

    function actionButtons(instance, firewallId){
        const toggleAction = instance.enabled
            ? 'disable'
            : 'enable';

        return `
            <div
                class="management-row-actions"
                data-firewall-id="${firewallId}"
                data-uuid="${escapeHtml(instance.uuid)}"
                data-vpnid="${escapeHtml(instance.vpnid)}"
            >
                <button
                    class="button secondary"
                    data-action="${toggleAction}"
                >
                    ${instance.enabled ? 'Disable' : 'Enable'}
                </button>
                <button
                    class="button secondary"
                    data-action="start"
                >
                    Start
                </button>
                <button
                    class="button secondary"
                    data-action="stop"
                >
                    Stop
                </button>
                <button
                    class="button secondary"
                    data-action="restart"
                >
                    Restart
                </button>
                <button
                    class="button danger"
                    data-action="delete"
                >
                    Delete
                </button>
            </div>
        `;
    }

    function instanceRows(instances, firewallId){
        if(!instances.length){
            return `<tr>
                <td colspan="6">No OpenVPN instances found.</td>
            </tr>`;
        }

        return instances.map(function(instance){
            const listener = instance.remote
                ? instance.remote
                : (instance.local || '*') +
                    (instance.port ? ':' + instance.port : '') +
                    (instance.proto ? ' ' + instance.proto : '');

            return `
                <tr>
                    <td>
                        <strong>${escapeHtml(
                            instance.description || 'Unnamed'
                        )}</strong>
                        <br>
                        <small>
                            ID ${detailValue(instance.vpnid)}
                        </small>
                    </td>
                    <td>${detailValue(instance.role)}</td>
                    <td>${escapeHtml(listener)}</td>
                    <td>${detailValue(instance.server)}</td>
                    <td>${statusBadge(instance.enabled)}</td>
                    <td>${actionButtons(instance, firewallId)}</td>
                </tr>
            `;
        }).join('');
    }

    async function loadFirewall(firewall){
        try{
            const response = await fetch(
                '/openvpn_manage_data.php?firewall_id=' +
                encodeURIComponent(firewall.id),
                {
                    credentials: 'same-origin',
                    cache: 'no-store'
                }
            );

            const data = await readJson(response);

            if(!response.ok || data.ok !== true){
                throw new Error(data.error || 'Load failed.');
            }

            return {
                ok: true,
                firewall,
                instances: Array.isArray(data.instances)
                    ? data.instances
                    : [],
                sessions: Array.isArray(data.sessions)
                    ? data.sessions
                    : [],
                sessions_error: data.sessions_error || null
            };
        }catch(error){
            return {
                ok: false,
                firewall,
                error: error.message,
                instances: [],
                sessions: []
            };
        }
    }

    function render(results){
        const available = results.filter(item => item.ok).length;
        const instanceTotal = results.reduce(
            (sum, item) => sum + item.instances.length,
            0
        );
        const sessionTotal = results.reduce(
            (sum, item) => sum + item.sessions.length,
            0
        );

        summary.textContent =
            results.length + ' firewalls · ' +
            available + ' reachable · ' +
            instanceTotal + ' OpenVPN instances · ' +
            sessionTotal + ' active sessions';

        list.innerHTML = results.length
            ? results.map(function(result){
                const enabledCount = result.instances.filter(
                    instance => instance.enabled
                ).length;

                return `
                    <section class="card vpn-summary-card">
                        <div class="vpn-summary-main">
                            <div class="vpn-summary-identity">
                                <h2>${escapeHtml(
                                    result.firewall.name
                                )}</h2>
                                <a
                                    class="muted"
                                    href="${escapeHtml(
                                        result.firewall.base_url
                                    )}"
                                    target="_blank"
                                    rel="noopener"
                                >${escapeHtml(
                                    result.firewall.base_url
                                )}</a>
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    Instances
                                </span>
                                ${
                                    result.ok
                                        ? `<span class="badge neutral">
                                            ${result.instances.length}
                                        </span>`
                                        : '<span class="badge bad">Unavailable</span>'
                                }
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">
                                    Summary
                                </span>
                                <span class="muted">
                                    ${
                                        result.ok
                                            ? enabledCount +
                                                ' enabled · ' +
                                                result.sessions.length +
                                                ' sessions'
                                            : escapeHtml(result.error)
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
                            ${
                                result.ok
                                    ? `
                                        <div class="vpn-details-header">
                                            <div>
                                                <strong>OpenVPN instances</strong>
                                                <div class="muted">
                                                    ${escapeHtml(
                                                        result.firewall.name
                                                    )}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-scroll management-table-wrap">
                                            <table class="management-table">
                                                <thead>
                                                    <tr>
                                                        <th>Instance</th>
                                                        <th>Role</th>
                                                        <th>Listener / Remote</th>
                                                        <th>Tunnel</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    ${instanceRows(
                                                        result.instances,
                                                        result.firewall.id
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="vpn-details-header vpn-session-subheader">
                                            <div>
                                                <strong>Active sessions</strong>
                                                <div class="muted">
                                                    ${
                                                        result.sessions_error
                                                            ? escapeHtml(
                                                                result.sessions_error
                                                            )
                                                            : result.sessions.length +
                                                                ' active'
                                                    }
                                                </div>
                                            </div>
                                        </div>

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
                                                    ${sessionRows(
                                                        result.sessions
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                    `
                                    : `<div class="alert error vpn-details-error">
                                        ${escapeHtml(result.error)}
                                    </div>`
                            }
                        </div>
                    </section>
                `;
            }).join('')
            : '<section class="card vpn-summary-card">' +
                '<p class="muted">No firewalls configured.</p>' +
              '</section>';

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
    }

    async function runAction(button){
        const row = button.closest('[data-uuid]');
        const action = button.dataset.action;
        const firewallId = row.dataset.firewallId;
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
                firewall_id: firewallId,
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

    async function load(){
        refresh.disabled = true;
        refresh.textContent = 'Loading…';
        errorBox.classList.add('hidden');

        try{
            const results = await Promise.all(
                firewalls.map(loadFirewall)
            );

            render(results);

            const failed = results.filter(item => !item.ok);

            if(failed.length){
                errorBox.textContent = failed.map(
                    item =>
                        item.firewall.name + ': ' + item.error
                ).join(' | ');
                errorBox.classList.remove('hidden');
            }
        }catch(error){
            summary.textContent = 'OpenVPN unavailable';
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'Could not load OpenVPN data.' +
                    '</p>' +
                '</section>';
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        }finally{
            refresh.disabled = false;
            refresh.textContent = 'Refresh';
        }
    }

    refresh.addEventListener('click', load);
    load();
})();
</script>

<?php require __DIR__.'/inc/footer.php'; ?>
