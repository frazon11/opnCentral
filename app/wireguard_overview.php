<?php
require_once __DIR__ . '/inc/config.php';
require_login();
require __DIR__ . '/inc/header.php';
?>
<div class="page-title management-page-title">
    <div>
        <h1>Managed WireGuard</h1>
        <p>Paired site-to-site connections between managed OPNsense firewalls.</p>
    </div>
    <div class="management-toolbar">
        <a class="button" href="/wireguard_create.php">
            Create site-to-site tunnel
        </a>
        <button type="button" id="refresh-links" class="button secondary">
            Refresh
        </button>
    </div>
</div>

<div class="management-overview-bar">
    <div>
        <strong>WireGuard overview</strong>
        <div id="wg-overview-summary" class="management-summary">
            Loading managed connections…
        </div>
    </div>
</div>

<div id="wg-overview-error" class="alert error hidden"></div>

<div id="wg-overview-list" class="vpn-summary-list">
    <section class="card vpn-summary-card">
        <p class="muted">Loading…</p>
    </section>
</div>

<script>
(function(){
    const csrfToken = <?= json_encode(
        csrf_token(),
        JSON_UNESCAPED_SLASHES
    ) ?>;

    const list = document.getElementById('wg-overview-list');
    const summary = document.getElementById('wg-overview-summary');
    const errorBox = document.getElementById('wg-overview-error');
    const refresh = document.getElementById('refresh-links');

    function escapeHtml(value){
        const node = document.createElement('div');
        node.textContent = String(value ?? '');
        return node.innerHTML;
    }

    function statusBadge(status){
        if(status === 'enabled'){
            return '<span class="badge good">Enabled</span>';
        }
        if(status === 'disabled'){
            return '<span class="badge neutral">Disabled</span>';
        }
        return '<span class="badge bad">Partial state</span>';
    }

    function sideState(side){
        return side.enabled
            ? '<span class="badge good">Enabled</span>'
            : '<span class="badge neutral">Disabled</span>';
    }

    function detailValue(value){
        const text = String(value ?? '').trim();
        return text === '' ? '—' : escapeHtml(text);
    }

    async function changeState(connection, enable, button){
        const verb = enable ? 'enable' : 'disable';

        if(!confirm(
            'Really ' + verb +
            ' this WireGuard connection on both managed firewalls?\n\n' +
            connection.local.firewall_name + ': ' +
            (connection.local.client_name || 'peer') + '\n' +
            connection.remote.firewall_name + ': ' +
            (connection.remote.client_name || 'peer') +
            '\n\nAutomatic backups will be created before the change.'
        )){
            return;
        }

        const original = button.textContent;
        button.disabled = true;
        button.textContent = enable ? 'Enabling…' : 'Disabling…';

        try{
            const params = new URLSearchParams();
            params.set('csrf', csrfToken);
            params.set(
                'local_firewall_id',
                String(connection.local.firewall_id)
            );
            params.set(
                'remote_firewall_id',
                String(connection.remote.firewall_id)
            );
            params.set(
                'local_client_uuid',
                connection.local.client_uuid
            );
            params.set(
                'remote_client_uuid',
                connection.remote.client_uuid
            );
            params.set(
                'local_expected_peer_key',
                connection.local.expected_peer_key
            );
            params.set(
                'remote_expected_peer_key',
                connection.remote.expected_peer_key
            );
            params.set('enable', enable ? '1' : '0');

            const response = await fetch('/wireguard_link_action.php', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type':
                        'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: params
            });

            const raw = await response.text();
            let result;

            try{
                result = JSON.parse(raw);
            }catch(error){
                throw new Error(
                    'Invalid server response: ' +
                    raw.replace(/\s+/g, ' ').slice(0, 500)
                );
            }

            if(!response.ok || result.ok !== true){
                throw new Error(result.error || 'Action failed.');
            }

            await load();
        }catch(error){
            alert(error.message);
            button.disabled = false;
            button.textContent = original;
        }
    }

    function renderDetails(connection){
        return `
            <div class="vpn-detail-grid">
                <div class="vpn-detail-side">
                    <div class="vpn-detail-heading">
                        <strong>${escapeHtml(
                            connection.local.firewall_name
                        )}</strong>
                        ${sideState(connection.local)}
                    </div>
                    <dl>
                        <dt>Peer</dt>
                        <dd>${detailValue(
                            connection.local.client_name || 'peer'
                        )}</dd>
                        <dt>Client UUID</dt>
                        <dd><code>${detailValue(
                            connection.local.client_uuid
                        )}</code></dd>
                        <dt>Expected peer key</dt>
                        <dd><code>${detailValue(
                            connection.local.expected_peer_key
                        )}</code></dd>
                    </dl>
                </div>

                <div class="vpn-detail-side">
                    <div class="vpn-detail-heading">
                        <strong>${escapeHtml(
                            connection.remote.firewall_name
                        )}</strong>
                        ${sideState(connection.remote)}
                    </div>
                    <dl>
                        <dt>Peer</dt>
                        <dd>${detailValue(
                            connection.remote.client_name || 'peer'
                        )}</dd>
                        <dt>Client UUID</dt>
                        <dd><code>${detailValue(
                            connection.remote.client_uuid
                        )}</code></dd>
                        <dt>Expected peer key</dt>
                        <dd><code>${detailValue(
                            connection.remote.expected_peer_key
                        )}</code></dd>
                    </dl>
                </div>
            </div>
        `;
    }

    function render(data){
        const connections = Array.isArray(data.connections)
            ? data.connections
            : [];

        const enabled = connections.filter(
            connection => connection.status === 'enabled'
        ).length;
        const disabled = connections.filter(
            connection => connection.status === 'disabled'
        ).length;
        const partial = connections.filter(
            connection => connection.status === 'partial'
        ).length;

        summary.innerHTML =
            '<span class="badge good">' +
                enabled + ' enabled</span> ' +
            '<span class="badge neutral">' +
                disabled + ' disabled</span> ' +
            '<span class="badge bad">' +
                partial + ' partial</span>';

        if(!connections.length){
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'No reciprocally matched managed WireGuard ' +
                        'connections found.' +
                    '</p>' +
                '</section>';
            return;
        }

        list.innerHTML = connections.map(function(connection, index){
            const enable = connection.status !== 'enabled';
            const title =
                escapeHtml(connection.local.firewall_name) +
                ' ↔ ' +
                escapeHtml(connection.remote.firewall_name);

            return `
                <section class="card vpn-summary-card">
                    <div class="vpn-summary-main">
                        <div class="vpn-summary-identity">
                            <h2>${title}</h2>
                            <span class="muted">
                                ${
                                    escapeHtml(
                                        connection.local.client_name ||
                                        'peer'
                                    )
                                }
                                ↔
                                ${
                                    escapeHtml(
                                        connection.remote.client_name ||
                                        'peer'
                                    )
                                }
                            </span>
                        </div>

                        <div class="vpn-summary-metric">
                            <span class="vpn-summary-label">Status</span>
                            ${statusBadge(connection.status)}
                        </div>

                        <div class="vpn-summary-metric">
                            <span class="vpn-summary-label">Sides</span>
                            <span class="muted">
                                ${
                                    connection.local.enabled
                                        ? 'A enabled'
                                        : 'A disabled'
                                }
                                ·
                                ${
                                    connection.remote.enabled
                                        ? 'B enabled'
                                        : 'B disabled'
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
                        <div class="vpn-details-header">
                            <div>
                                <strong>WireGuard connection details</strong>
                                <div class="muted">${title}</div>
                            </div>
                            <button
                                type="button"
                                class="${
                                    enable
                                        ? 'button secondary'
                                        : 'button warning'
                                } vpn-state-action"
                                data-index="${index}"
                            >
                                ${
                                    enable
                                        ? 'Enable both sides'
                                        : 'Disable both sides'
                                }
                            </button>
                        </div>
                        ${renderDetails(connection)}
                    </div>
                </section>
            `;
        }).join('');

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

        list.querySelectorAll('.vpn-state-action').forEach(
            function(button){
                button.addEventListener('click', function(){
                    const connection = connections[
                        Number(button.dataset.index)
                    ];
                    const enable =
                        connection.status !== 'enabled';
                    changeState(connection, enable, button);
                });
            }
        );
    }

    let hasRendered = false;
    let backgroundRefreshRunning = false;

    function showErrors(data){
        if(Array.isArray(data.errors) && data.errors.length){
            errorBox.textContent = data.errors.join(' | ');
            errorBox.classList.remove('hidden');
        }else{
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }
    }

    async function requestData(force){
        const response = await fetch(
            '/wireguard_overview_data.php' +
            (force ? '?force=1' : ''),
            {
                credentials: 'same-origin',
                cache: 'no-store'
            }
        );

        const raw = await response.text();
        let data;

        try{
            data = JSON.parse(raw);
        }catch(error){
            throw new Error(
                'Server returned invalid JSON: ' +
                raw.replace(/\s+/g, ' ').trim().slice(0, 500)
            );
        }

        if(!response.ok || data.ok !== true){
            throw new Error(data.error || 'Loading failed.');
        }

        return data;
    }

    async function refreshLive(manual){
        if(backgroundRefreshRunning){
            return;
        }

        backgroundRefreshRunning = true;

        const previousText = refresh.textContent;
        refresh.disabled = true;
        refresh.textContent = manual
            ? 'Refreshing…'
            : 'Updating…';

        try{
            const data = await requestData(true);
            render(data);
            showErrors(data);
            hasRendered = true;
        }catch(error){
            if(manual || !hasRendered){
                errorBox.textContent = error.message;
                errorBox.classList.remove('hidden');
            }
        }finally{
            backgroundRefreshRunning = false;
            refresh.disabled = false;
            refresh.textContent = previousText;
        }
    }

    async function load(){
        refresh.disabled = true;
        errorBox.classList.add('hidden');

        try{
            const data = await requestData(false);
            render(data);
            showErrors(data);
            hasRendered = true;

            if(data.cache?.refresh_recommended){
                window.setTimeout(
                    () => refreshLive(false),
                    150
                );
            }
        }catch(error){
            summary.textContent = 'Unavailable';
            list.innerHTML =
                '<section class="card vpn-summary-card">' +
                    '<p class="muted">' +
                        'Could not load managed WireGuard connections.' +
                    '</p>' +
                '</section>';
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
        }finally{
            refresh.disabled = false;
        }
    }

    refresh.addEventListener(
        'click',
        () => refreshLive(true)
    );

    load();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
