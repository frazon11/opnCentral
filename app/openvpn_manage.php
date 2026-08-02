<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_login();

$firewalls = db()
    ->query('SELECT id,name,base_url FROM firewalls ORDER BY name')
    ->fetchAll();

require __DIR__ . '/inc/header.php';
?>

<div class="page-title management-page-title">
    <div>
        <h1>Manage OpenVPN</h1>
        <p>OpenVPN status and instance configuration grouped by managed OPNsense firewall.</p>
    </div>

    <div class="management-toolbar">
        <button class="button secondary" id="refresh">Refresh</button>
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

    const csrf = <?= json_encode(
        csrf_token(),
        JSON_UNESCAPED_SLASHES
    ) ?>;

    const list = document.getElementById('ovpn-list');
    const summary = document.getElementById('ovpn-summary');
    const errorBox = document.getElementById('ovpn-error');
    const refresh = document.getElementById('refresh');

    const configSections = [
        {
            title: 'General',
            fields: [
                ['enabled', 'Enabled'],
                ['role', 'Role'],
                ['description', 'Description'],
                ['vpnid', 'Instance ID'],
                ['dev_type', 'Device type'],
                ['verb', 'Verbosity level']
            ]
        },
        {
            title: 'Connection',
            fields: [
                ['proto', 'Protocol'],
                ['port', 'Port'],
                ['port-share', 'Port share'],
                ['local', 'Bind address'],
                ['remote', 'Remote host'],
                ['topology', 'Topology'],
                ['server', 'IPv4 tunnel network'],
                ['server_ipv6', 'IPv6 tunnel network'],
                ['nopool', 'Do not use address pool'],
                ['bridge_gateway', 'Bridge gateway'],
                ['bridge_pool', 'Bridge pool']
            ]
        },
        {
            title: 'Routing',
            fields: [
                ['route', 'Remote networks'],
                ['push_route', 'Local networks'],
                ['push_excluded_routes', 'Excluded pushed routes'],
                ['redirect_gateway', 'Redirect gateway'],
                ['route_metric', 'Route metric']
            ]
        },
        {
            title: 'Certificates and authentication',
            fields: [
                ['ca', 'Certificate authority'],
                ['cert', 'Certificate'],
                ['crl', 'Certificate revocation list'],
                ['cert_depth', 'Certificate depth'],
                ['remote_cert_tls', 'Verify remote certificate usage'],
                ['verify_client_cert', 'Client certificate'],
                ['use_ocsp', 'Use OCSP'],
                ['authmode', 'Authentication servers'],
                ['local_group', 'Local group'],
                ['username', 'Username'],
                ['password', 'Password'],
                ['username_as_common_name', 'Use username as Common Name'],
                ['strictusercn', 'Strict User/CN matching'],
                ['verify-x509-name', 'Verify X.509 name']
            ]
        },
        {
            title: 'Cryptography',
            fields: [
                ['auth', 'Digest algorithm'],
                ['data-ciphers', 'Data ciphers'],
                ['data-ciphers-fallback', 'Fallback cipher'],
                ['tls_key', 'TLS static key']
            ]
        },
        {
            title: 'Client settings',
            fields: [
                ['maxclients', 'Maximum clients'],
                ['keepalive_interval', 'Keepalive interval'],
                ['keepalive_timeout', 'Keepalive timeout'],
                ['reneg-sec', 'Renegotiation interval'],
                ['auth-gen-token', 'Authentication token lifetime'],
                ['auth-gen-token-renewal', 'Token renewal interval'],
                ['auth-gen-token-secret', 'Authentication token secret'],
                ['provision_exclusive', 'Exclusive client provisioning'],
                ['register_dns', 'Register DNS'],
                ['dns_domain', 'DNS domain'],
                ['dns_domain_search', 'DNS search domains'],
                ['dns_servers', 'DNS servers'],
                ['ntp_servers', 'NTP servers']
            ]
        },
        {
            title: 'Advanced',
            fields: [
                ['various_flags', 'OpenVPN flags'],
                ['various_push_flags', 'Pushed flags'],
                ['push_inactive', 'Inactive push timeout'],
                ['tun_mtu', 'Tunnel MTU'],
                ['fragment', 'Fragment size'],
                ['mssfix', 'MSS fix'],
                ['carp_depend_on', 'CARP dependency'],
                ['compress_migrate', 'Compression migration'],
                ['ifconfig-pool-persist', 'Persist address pool'],
                ['http-proxy', 'HTTP proxy']
            ]
        }
    ];

    const fieldLabels = new Map();
    configSections.forEach(section => {
        section.fields.forEach(([key, label]) => fieldLabels.set(key, label));
    });

    function escapeHtml(value){
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    function readJson(response){
        return response.text().then(raw => {
            try{
                return JSON.parse(raw);
            }catch(error){
                throw new Error(
                    'Invalid JSON: ' +
                    raw.replace(/\s+/g, ' ').slice(0, 700)
                );
            }
        });
    }

    function empty(value){
        return value === null ||
            value === undefined ||
            value === '' ||
            (Array.isArray(value) && value.length === 0);
    }

    function yesNo(value){
        if(value === true || String(value) === '1'){
            return 'Yes';
        }
        if(value === false || String(value) === '0'){
            return 'No';
        }
        return null;
    }

    function displayValue(value){
        if(empty(value)){
            return '<span class="muted">Not set</span>';
        }

        const boolean = yesNo(value);
        if(boolean !== null){
            return escapeHtml(boolean);
        }

        if(Array.isArray(value)){
            return value.length
                ? '<div class="ovpn-config-values">' +
                    value.map(item =>
                        '<code>' + escapeHtml(item) + '</code>'
                    ).join('') +
                  '</div>'
                : '<span class="muted">Not set</span>';
        }

        if(typeof value === 'object'){
            return '<pre class="ovpn-config-json">' +
                escapeHtml(JSON.stringify(value, null, 2)) +
            '</pre>';
        }

        const text = String(value);
        if(text.includes('\n') || text.includes(',')){
            const parts = text.split(/[\n,]+/)
                .map(item => item.trim())
                .filter(Boolean);

            if(parts.length > 1){
                return '<div class="ovpn-config-values">' +
                    parts.map(item =>
                        '<code>' + escapeHtml(item) + '</code>'
                    ).join('') +
                '</div>';
            }
        }

        return escapeHtml(text);
    }

    function statusBadge(enabled){
        return enabled
            ? '<span class="badge good">Enabled</span>'
            : '<span class="badge neutral">Disabled</span>';
    }

    function sessionRows(sessions){
        if(!sessions.length){
            return '<tr><td colspan="4">No active OpenVPN sessions.</td></tr>';
        }

        return sessions.map(session => `
            <tr>
                <td>${displayValue(
                    session.common_name ||
                    session.username ||
                    session.user_name
                )}</td>
                <td>${displayValue(
                    session.virtual_address ||
                    session.virtual_addr ||
                    session.vpn_ip
                )}</td>
                <td>${displayValue(
                    session.real_address ||
                    session.remote_address ||
                    session.remote_host
                )}</td>
                <td>${displayValue(
                    session.connected_since ||
                    session.connect_time ||
                    session.since
                )}</td>
            </tr>
        `).join('');
    }

    function actionButtons(instance, firewallId){
        return `
            <div
                class="management-row-actions"
                data-firewall-id="${firewallId}"
                data-uuid="${escapeHtml(instance.uuid)}"
                data-vpnid="${escapeHtml(instance.vpnid)}"
            >
                <button class="button secondary" data-action="${
                    instance.enabled ? 'disable' : 'enable'
                }">
                    ${instance.enabled ? 'Disable' : 'Enable'}
                </button>
                <button class="button secondary" data-action="start">Start</button>
                <button class="button secondary" data-action="stop">Stop</button>
                <button class="button secondary" data-action="restart">Restart</button>
                <button class="button danger" data-action="delete">Delete</button>
            </div>
        `;
    }

    function instanceRows(instances, firewallId){
        if(!instances.length){
            return '<tr><td colspan="6">No OpenVPN instances found.</td></tr>';
        }

        return instances.map(instance => {
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
                        <br><small>ID ${escapeHtml(instance.vpnid || '—')}</small>
                    </td>
                    <td>${displayValue(instance.role)}</td>
                    <td>${escapeHtml(listener)}</td>
                    <td>${displayValue(instance.server)}</td>
                    <td>${statusBadge(instance.enabled)}</td>
                    <td>${actionButtons(instance, firewallId)}</td>
                </tr>
            `;
        }).join('');
    }

    function configSection(section, config){
        const rows = section.fields
            .filter(([key]) => Object.prototype.hasOwnProperty.call(config, key))
            .map(([key, label]) => `
                <div class="ovpn-config-field">
                    <div class="ovpn-config-label">${escapeHtml(label)}</div>
                    <div class="ovpn-config-value">${displayValue(config[key])}</div>
                </div>
            `).join('');

        return rows
            ? `<section class="ovpn-config-section">
                <h4>${escapeHtml(section.title)}</h4>
                <div class="ovpn-config-grid">${rows}</div>
              </section>`
            : '';
    }

    function unknownConfig(config){
        const known = new Set(fieldLabels.keys());
        const keys = Object.keys(config)
            .filter(key => !known.has(key))
            .sort((a, b) => a.localeCompare(b));

        if(!keys.length){
            return '';
        }

        return `<section class="ovpn-config-section">
            <h4>Additional options</h4>
            <div class="ovpn-config-grid">
                ${keys.map(key => `
                    <div class="ovpn-config-field">
                        <div class="ovpn-config-label">${escapeHtml(key)}</div>
                        <div class="ovpn-config-value">${displayValue(config[key])}</div>
                    </div>
                `).join('')}
            </div>
        </section>`;
    }

    function instanceConfig(instance){
        if(instance.config_error){
            return `<div class="alert error ovpn-config-error">
                ${escapeHtml(instance.config_error)}
            </div>`;
        }

        const config = instance.config || {};
        if(!Object.keys(config).length){
            return '<div class="ovpn-config-empty">No configuration returned.</div>';
        }

        return `
            <article class="ovpn-instance-config">
                <div class="ovpn-instance-config-head">
                    <div>
                        <h3>${escapeHtml(instance.description || 'Unnamed')}</h3>
                        <span class="muted">
                            Instance ID ${escapeHtml(instance.vpnid || '—')}
                            · ${escapeHtml(instance.uuid)}
                        </span>
                    </div>
                    ${statusBadge(instance.enabled)}
                </div>
                ${configSections.map(section =>
                    configSection(section, config)
                ).join('')}
                ${unknownConfig(config)}
            </article>
        `;
    }

    function configMarkup(instances){
        if(!instances.length){
            return '<div class="ovpn-config-empty">No OpenVPN instances found.</div>';
        }

        return instances.map(instanceConfig).join('');
    }

    async function loadFirewall(firewall){
        try{
            const response = await fetch(
                '/openvpn_manage_data.php?firewall_id=' +
                encodeURIComponent(firewall.id),
                {credentials: 'same-origin', cache: 'no-store'}
            );
            const data = await readJson(response);

            if(!response.ok || data.ok !== true){
                throw new Error(data.error || 'Load failed.');
            }

            return {
                ok: true,
                firewall,
                instances: Array.isArray(data.instances) ? data.instances : [],
                sessions: Array.isArray(data.sessions) ? data.sessions : [],
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
            (sum, item) => sum + item.instances.length, 0
        );
        const sessionTotal = results.reduce(
            (sum, item) => sum + item.sessions.length, 0
        );

        summary.textContent =
            results.length + ' firewalls · ' +
            available + ' reachable · ' +
            instanceTotal + ' OpenVPN instances · ' +
            sessionTotal + ' active sessions';

        list.innerHTML = results.length
            ? results.map(result => {
                const enabledCount = result.instances.filter(
                    instance => instance.enabled
                ).length;

                return `
                    <section class="card vpn-summary-card">
                        <div class="vpn-summary-main">
                            <div class="vpn-summary-identity">
                                <h2>${escapeHtml(result.firewall.name)}</h2>
                                <a class="muted"
                                   href="${escapeHtml(result.firewall.base_url)}"
                                   target="_blank" rel="noopener">
                                    ${escapeHtml(result.firewall.base_url)}
                                </a>
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">Instances</span>
                                ${
                                    result.ok
                                        ? `<span class="badge neutral">${
                                            result.instances.length
                                        }</span>`
                                        : '<span class="badge bad">Unavailable</span>'
                                }
                            </div>

                            <div class="vpn-summary-metric">
                                <span class="vpn-summary-label">Summary</span>
                                <span class="muted">
                                    ${
                                        result.ok
                                            ? enabledCount + ' enabled · ' +
                                                result.sessions.length + ' sessions'
                                            : escapeHtml(result.error)
                                    }
                                </span>
                            </div>

                            <div class="vpn-summary-actions ovpn-summary-actions">
                                <button type="button"
                                    class="button secondary ovpn-panel-toggle"
                                    data-panel="details"
                                    aria-expanded="false">
                                    Details
                                </button>
                                <button type="button"
                                    class="button secondary ovpn-panel-toggle"
                                    data-panel="config"
                                    aria-expanded="false">
                                    Config
                                </button>
                            </div>
                        </div>

                        <div class="vpn-details-panel ovpn-details-panel"
                             data-panel-name="details" hidden>
                            ${
                                result.ok
                                    ? `
                                    <div class="vpn-details-header">
                                        <div>
                                            <strong>OpenVPN instances</strong>
                                            <div class="muted">${
                                                escapeHtml(result.firewall.name)
                                            }</div>
                                        </div>
                                    </div>
                                    <div class="table-scroll management-table-wrap">
                                        <table class="management-table">
                                            <thead><tr>
                                                <th>Instance</th>
                                                <th>Role</th>
                                                <th>Listener / Remote</th>
                                                <th>Tunnel</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr></thead>
                                            <tbody>${instanceRows(
                                                result.instances,
                                                result.firewall.id
                                            )}</tbody>
                                        </table>
                                    </div>
                                    <div class="vpn-details-header vpn-session-subheader">
                                        <div>
                                            <strong>Active sessions</strong>
                                            <div class="muted">${
                                                result.sessions_error
                                                    ? escapeHtml(
                                                        result.sessions_error
                                                      )
                                                    : result.sessions.length +
                                                        ' active'
                                            }</div>
                                        </div>
                                    </div>
                                    <div class="table-scroll management-table-wrap">
                                        <table class="management-table">
                                            <thead><tr>
                                                <th>User / Common Name</th>
                                                <th>Virtual address</th>
                                                <th>Remote address</th>
                                                <th>Connected</th>
                                            </tr></thead>
                                            <tbody>${sessionRows(
                                                result.sessions
                                            )}</tbody>
                                        </table>
                                    </div>`
                                    : `<div class="alert error vpn-details-error">
                                        ${escapeHtml(result.error)}
                                      </div>`
                            }
                        </div>

                        <div class="vpn-details-panel ovpn-config-panel"
                             data-panel-name="config" hidden>
                            <div class="vpn-details-header">
                                <div>
                                    <strong>OpenVPN instance configuration</strong>
                                    <div class="muted">
                                        Read-only representation of VPN →
                                        OpenVPN → Instances on ${
                                            escapeHtml(result.firewall.name)
                                        }
                                    </div>
                                </div>
                            </div>
                            ${
                                result.ok
                                    ? configMarkup(result.instances)
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

        list.querySelectorAll('.ovpn-panel-toggle').forEach(button => {
            button.addEventListener('click', function(){
                const card = button.closest('.vpn-summary-card');
                const targetName = button.dataset.panel;
                const panels = card.querySelectorAll('[data-panel-name]');
                const buttons = card.querySelectorAll('.ovpn-panel-toggle');
                const target = card.querySelector(
                    '[data-panel-name="' + targetName + '"]'
                );
                const opening = target.hidden;

                panels.forEach(panel => panel.hidden = true);
                buttons.forEach(item => {
                    item.setAttribute('aria-expanded', 'false');
                    item.textContent =
                        item.dataset.panel === 'details'
                            ? 'Details'
                            : 'Config';
                });

                if(opening){
                    target.hidden = false;
                    button.setAttribute('aria-expanded', 'true');
                    button.textContent =
                        targetName === 'details'
                            ? 'Hide details'
                            : 'Hide config';
                }

                card.classList.toggle('vpn-summary-expanded', opening);
            });
        });

        list.querySelectorAll('[data-action]').forEach(button => {
            button.addEventListener('click', () => runAction(button));
        });
    }

    async function runAction(button){
        const row = button.closest('[data-uuid]');
        const action = button.dataset.action;
        const firewallId = row.dataset.firewallId;
        const uuid = row.dataset.uuid;
        const vpnid = row.dataset.vpnid;
        const destructive = ['delete', 'disable'].includes(action);

        if(!confirm(
            action.toUpperCase() + ' OpenVPN instance ' + vpnid + '?' +
            (destructive
                ? '\n\nA configuration backup will be created first.'
                : '')
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
            const response = await fetch('/openvpn_manage_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: form
            });
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
                    item => item.firewall.name + ': ' + item.error
                ).join(' | ');
                errorBox.classList.remove('hidden');
            }
        }catch(error){
            summary.textContent = 'OpenVPN unavailable';
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">Could not load OpenVPN data.</p>' +
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

<?php require __DIR__ . '/inc/footer.php'; ?>
