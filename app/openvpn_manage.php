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

    const opnForm = [
        {header:'General Settings'},
        {key:'vpnid',label:'VPN ID',type:'text'},
        {key:'role',label:'Role',type:'dropdown'},
        {key:'description',label:'Description',type:'text'},
        {key:'enabled',label:'Enabled',type:'checkbox'},
        {key:'proto',label:'Protocol',type:'dropdown'},
        {key:'port',label:'Port number',type:'text'},
        {key:'local',label:'Bind address',type:'text'},
        {key:'port-share',label:'Port share',type:'text',advanced:true,roles:['server']},
        {key:'dev_type',label:'Type',type:'dropdown'},
        {key:'verb',label:'Verbosity',type:'dropdown',advanced:true},
        {key:'maxclients',label:'Concurrent connections',type:'text',advanced:true,roles:['server']},
        {key:'keepalive_interval',label:'Keep alive interval',type:'text',advanced:true},
        {key:'keepalive_timeout',label:'Keep alive timeout',type:'text',advanced:true},
        {key:'server',label:'Server (IPv4)',type:'text',roles:['server'],devices:['tun','ovpn']},
        {key:'server_ipv6',label:'Server (IPv6)',type:'text',roles:['server'],devices:['tun','ovpn']},
        {key:'nopool',label:'No Pool',type:'checkbox',roles:['server'],devices:['tun','ovpn']},
        {key:'bridge_gateway',label:'Bridge gateway',type:'text',roles:['server'],devices:['tap']},
        {key:'bridge_pool',label:'Bridge DHCP pool',type:'text',roles:['server'],devices:['tap']},
        {key:'topology',label:'Topology',type:'dropdown',roles:['server']},
        {key:'remote',label:'Remote',type:'select_multiple',roles:['client']},
        {key:'carp_depend_on',label:'Depend on (CARP)',type:'dropdown',roles:['client']},

        {header:'Trust'},
        {key:'cert',label:'Certificate',type:'dropdown',reference:'certificates'},
        {key:'remote_cert_tls',label:'Verify Remote Certificate',type:'checkbox'},
        {key:'ca',label:'Certificate Authority',type:'dropdown',advanced:true,reference:'cas'},
        {key:'crl',label:'Certificate Revocation List',type:'dropdown',roles:['server']},
        {key:'verify_client_cert',label:'Verify Client Certificate',type:'dropdown',roles:['server']},
        {key:'use_ocsp',label:'Use OCSP (when available)',type:'checkbox',roles:['server']},
        {key:'cert_depth',label:'Certificate Depth',type:'dropdown',roles:['server']},
        {key:'tls_key',label:'TLS static key',type:'dropdown',reference:'static_keys'},
        {key:'auth',label:'Auth',type:'dropdown',advanced:true},
        {key:'data-ciphers',label:'Data Ciphers',type:'select_multiple',advanced:true},
        {key:'data-ciphers-fallback',label:'Data Ciphers Fallback',type:'dropdown',advanced:true},

        {header:'Authentication'},
        {key:'authmode',label:'Authentication',type:'select_multiple',roles:['server'],reference:'providers'},
        {key:'local_group',label:'Enforce local group',type:'dropdown',roles:['server']},
        {key:'username_as_common_name',label:'Username as CN',type:'checkbox',advanced:true,roles:['server']},
        {key:'strictusercn',label:'Strict User/CN Matching',type:'dropdown',roles:['server']},
        {key:'username',label:'Username',type:'text',roles:['client']},
        {key:'password',label:'Password',type:'password',roles:['client'],sensitive:true},
        {key:'reneg-sec',label:'Renegotiate time',type:'text'},
        {key:'auth-gen-token',label:'Auth Token Lifetime',type:'text',roles:['server']},
        {key:'auth-gen-token-renewal',label:'Auth Token Renewal',type:'text',advanced:true,roles:['server']},
        {key:'auth-gen-token-secret',label:'Auth Token secret',type:'textbox',advanced:true,roles:['server'],sensitive:true},
        {key:'provision_exclusive',label:'Require Client Provisioning',type:'checkbox',advanced:true,roles:['server']},

        {header:'Routing'},
        {key:'push_route',label:'Local Network',type:'select_multiple'},
        {key:'route',label:'Remote Network',type:'select_multiple'},
        {key:'push_excluded_routes',label:'Excluded routes',type:'select_multiple',advanced:true},

        {header:'Miscellaneous'},
        {key:'various_flags',label:'Options',type:'select_multiple'},
        {key:'various_push_flags',label:'Push Options',type:'select_multiple',roles:['server']},
        {key:'push_inactive',label:'Push inactivity timeout',type:'text',advanced:true,roles:['server']},
        {key:'redirect_gateway',label:'Redirect gateway',type:'select_multiple',roles:['server']},
        {key:'route_metric',label:'Route-metric (client)',type:'text',advanced:true,roles:['server']},
        {key:'register_dns',label:'Register DNS',type:'checkbox',roles:['server']},
        {key:'dns_domain',label:'DNS Domain list',type:'select_multiple',roles:['server']},
        {key:'dns_domain_search',label:'DNS Domain search list',type:'select_multiple',roles:['server']},
        {key:'dns_servers',label:'DNS Servers',type:'select_multiple',roles:['server']},
        {key:'ntp_servers',label:'NTP Servers',type:'select_multiple',roles:['server']},
        {key:'tun_mtu',label:'TUN device MTU',type:'text',advanced:true},
        {key:'fragment',label:'Fragment size',type:'text',advanced:true},
        {key:'mssfix',label:'MSS fix',type:'checkbox',advanced:true},
        {key:'compress_migrate',label:'Compression migrate',type:'checkbox',advanced:true,roles:['server']},
        {key:'ifconfig-pool-persist',label:'Persist address pool',type:'checkbox',advanced:true,roles:['server']},
        {key:'http-proxy',label:'HTTP Proxy',type:'text',roles:['client']},
        {key:'verify-x509-name',label:'Verify X.509 name',type:'text',advanced:true}
    ];

    const optionLabels = {
        role:{client:'Client',server:'Server'},
        dev_type:{tun:'TUN',tap:'TAP',ovpn:'DCO'},
        proto:{udp:'UDP',udp4:'UDP (IPv4)',udp6:'UDP (IPv6)',tcp:'TCP',tcp4:'TCP (IPv4)',tcp6:'TCP (IPv6)'},
        topology:{net30:'net30',p2p:'p2p',subnet:'subnet'},
        verify_client_cert:{none:'none',require:'require'},
        cert_depth:{
            '':'Do Not Check',
            '1':'One (Client+Server)',
            '2':'Two (Client+Intermediate+Server)',
            '3':'Three (Client+2xIntermediate+Server)',
            '4':'Four (Client+3xIntermediate+Server)',
            '5':'Five (Client+4xIntermediate+Server)'
        },
        strictusercn:{'0':'No','1':'Yes','2':'Yes, case insensitive'},
        auth:{'':'OpenVPN default',none:'None (No Authentication)'},
        verb:{
            '0':'0 (No output except fatal errors.)',
            '1':'1 (Normal)','2':'2 (Normal)','3':'3 (Normal)','4':'4 (Normal)',
            '5':'5 (log packets)','6':'6 (debug)','7':'7 (debug)',
            '8':'8 (debug)','9':'9 (debug)','10':'10 (debug)','11':'11 (debug)'
        }
    };

    const knownKeys = new Set(
        opnForm.filter(item => item.key).map(item => item.key)
    );

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

    function referenceLabel(field, value, references){
        const rows = references?.[field.reference] || [];
        const match = rows.find(row => String(row.id) === String(value));
        return match ? match.label : value;
    }

    function mappedValue(field, value, references){
        if(field.sensitive && !empty(value)){
            return '••••••••';
        }

        if(field.reference){
            if(Array.isArray(value)){
                return value.map(item =>
                    referenceLabel(field, item, references)
                );
            }

            const parts = String(value ?? '')
                .split(/[\n,]+/)
                .map(item => item.trim())
                .filter(Boolean);

            if(parts.length > 1){
                return parts.map(item =>
                    referenceLabel(field, item, references)
                );
            }

            return referenceLabel(field, value, references);
        }

        const map = optionLabels[field.key];
        if(map){
            if(Array.isArray(value)){
                return value.map(item => map[String(item)] ?? item);
            }

            const parts = String(value ?? '')
                .split(/[\n,]+/)
                .map(item => item.trim())
                .filter(Boolean);

            if(parts.length > 1){
                return parts.map(item => map[item] ?? item);
            }

            return map[String(value)] ?? value;
        }

        return value;
    }

    function fieldVisible(field, config, advanced){
        if(field.advanced && !advanced){
            return false;
        }

        const role = String(config.role || 'server');
        const device = String(config.dev_type || 'tun');

        if(field.roles && !field.roles.includes(role)){
            return false;
        }

        if(field.devices && !field.devices.includes(device)){
            return false;
        }

        return true;
    }

    function renderFormRows(config, references, advanced){
        let html = '';
        let currentHeaderOpen = false;
        let sectionHasRows = false;
        let sectionBuffer = '';

        function flushSection(){
            if(!currentHeaderOpen){
                return;
            }

            if(sectionHasRows){
                html += sectionBuffer + '</div></section>';
            }

            currentHeaderOpen = false;
            sectionHasRows = false;
            sectionBuffer = '';
        }

        opnForm.forEach(item => {
            if(item.header){
                flushSection();
                currentHeaderOpen = true;
                sectionBuffer =
                    '<section class="ovpn-opnsense-section">' +
                    '<div class="ovpn-opnsense-section-title">' +
                    escapeHtml(item.header) +
                    '</div><div class="ovpn-opnsense-form">';
                return;
            }

            if(!fieldVisible(item, config, advanced)){
                return;
            }

            sectionHasRows = true;
            const hasValue = Object.prototype.hasOwnProperty.call(
                config,
                item.key
            );
            const rawValue = hasValue ? config[item.key] : '';
            const value = mappedValue(item, rawValue, references);
            const isAdvanced = item.advanced
                ? '<span class="ovpn-advanced-marker">advanced</span>'
                : '';

            sectionBuffer += `
                <div class="ovpn-opnsense-row">
                    <div class="ovpn-opnsense-label">
                        ${escapeHtml(item.label)}
                        ${isAdvanced}
                    </div>
                    <div class="ovpn-opnsense-control ${
                        item.type === 'checkbox'
                            ? 'ovpn-opnsense-checkbox'
                            : ''
                    }">
                        ${
                            item.type === 'checkbox'
                                ? (
                                    String(rawValue) === '1' ||
                                    rawValue === true
                                        ? '<span class="ovpn-checkbox-state on">✓</span><span>Enabled</span>'
                                        : '<span class="ovpn-checkbox-state"> </span><span>Disabled</span>'
                                )
                                : displayValue(value)
                        }
                    </div>
                </div>
            `;
        });

        flushSection();
        return html;
    }

    function unknownConfig(config, advanced){
        if(!advanced){
            return '';
        }

        const keys = Object.keys(config)
            .filter(key => !knownKeys.has(key))
            .sort((a, b) => a.localeCompare(b));

        if(!keys.length){
            return '';
        }

        return `<section class="ovpn-opnsense-section">
            <div class="ovpn-opnsense-section-title">Additional model fields</div>
            <div class="ovpn-opnsense-form">
                ${keys.map(key => `
                    <div class="ovpn-opnsense-row">
                        <div class="ovpn-opnsense-label">${escapeHtml(key)}</div>
                        <div class="ovpn-opnsense-control">${displayValue(config[key])}</div>
                    </div>
                `).join('')}
            </div>
        </section>`;
    }

    function instanceConfig(instance, references, instanceIndex){
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
            <article class="ovpn-instance-config"
                     data-config-instance="${instanceIndex}">
                <div class="ovpn-instance-config-head">
                    <div>
                        <h3>${escapeHtml(instance.description || 'Unnamed')}</h3>
                        <span class="muted">
                            Instance ID ${escapeHtml(instance.vpnid || '—')}
                            · ${escapeHtml(instance.uuid)}
                        </span>
                    </div>
                    <div class="ovpn-instance-config-tools">
                        ${statusBadge(instance.enabled)}
                        <button type="button"
                            class="button secondary ovpn-advanced-toggle"
                            aria-pressed="false">
                            Advanced mode: Off
                        </button>
                    </div>
                </div>
                <div class="ovpn-opnsense-form-wrap"
                     data-advanced="false">
                    ${renderFormRows(config, references, false)}
                </div>
            </article>
        `;
    }

    function configMarkup(instances, references){
        if(!instances.length){
            return '<div class="ovpn-config-empty">No OpenVPN instances found.</div>';
        }

        return instances.map((instance, index) =>
            instanceConfig(instance, references, index)
        ).join('');
    }

    function bindConfigAdvancedToggles(card, result){
        card.querySelectorAll('.ovpn-advanced-toggle').forEach(button => {
            button.addEventListener('click', function(){
                const article = button.closest('.ovpn-instance-config');
                const wrap = article.querySelector('.ovpn-opnsense-form-wrap');
                const index = Number(article.dataset.configInstance);
                const advanced = button.getAttribute('aria-pressed') !== 'true';

                button.setAttribute(
                    'aria-pressed',
                    advanced ? 'true' : 'false'
                );
                button.textContent = advanced
                    ? 'Advanced mode: On'
                    : 'Advanced mode: Off';
                wrap.dataset.advanced = advanced ? 'true' : 'false';
                wrap.innerHTML =
                    renderFormRows(
                        result.instances[index].config || {},
                        result.references || {},
                        advanced
                    ) +
                    unknownConfig(
                        result.instances[index].config || {},
                        advanced
                    );
            });
        });
    }

    async function loadFirewall(firewall){
        try{
            const [dataResponse, optionsResponse] = await Promise.all([
                fetch(
                    '/openvpn_manage_data.php?firewall_id=' +
                    encodeURIComponent(firewall.id),
                    {credentials:'same-origin',cache:'no-store'}
                ),
                fetch(
                    '/openvpn_roadwarrior_options.php?firewall_id=' +
                    encodeURIComponent(firewall.id),
                    {credentials:'same-origin',cache:'no-store'}
                )
            ]);

            const data = await readJson(dataResponse);
            const options = await readJson(optionsResponse);

            if(!dataResponse.ok || data.ok !== true){
                throw new Error(data.error || 'Load failed.');
            }

            return {
                ok:true,
                firewall,
                instances:Array.isArray(data.instances)?data.instances:[],
                sessions:Array.isArray(data.sessions)?data.sessions:[],
                sessions_error:data.sessions_error||null,
                references:
                    optionsResponse.ok && options.ok === true
                        ? {
                            cas:Array.isArray(options.cas)?options.cas:[],
                            certificates:Array.isArray(options.certificates)
                                ? options.certificates
                                : [],
                            static_keys:Array.isArray(options.static_keys)
                                ? options.static_keys
                                : [],
                            providers:Array.isArray(options.providers)
                                ? options.providers
                                : []
                        }
                        : {}
            };
        }catch(error){
            return {
                ok:false,
                firewall,
                error:error.message,
                instances:[],
                sessions:[],
                references:{}
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
                                    ? configMarkup(result.instances, result.references)
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

        list.querySelectorAll('.vpn-summary-card').forEach((card, index) => {
            if(results[index]?.ok){
                bindConfigAdvancedToggles(card, results[index]);
            }
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
