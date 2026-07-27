<?php

require_once __DIR__ . '/inc/config.php';

require_login();

$firewalls = db()
    ->query('SELECT * FROM firewalls ORDER BY name')
    ->fetchAll();

require __DIR__ . '/inc/header.php';

?>

<style>
.view-toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.view-switch{display:inline-flex;gap:4px;padding:4px;border:1px solid rgba(127,127,127,.25);border-radius:10px;background:rgba(127,127,127,.08)}
.view-switch button{border:0;border-radius:7px;padding:8px 12px;cursor:pointer;background:transparent;color:inherit}
.view-switch button.active{background:rgba(127,127,127,.2);font-weight:700}
.firewall-list{display:grid;gap:16px}
.view-cards .firewall-list{grid-template-columns:repeat(auto-fit,minmax(300px,1fr))}
.view-details .firewall-list{grid-template-columns:1fr}
.view-compact .firewall-list{display:grid;grid-template-columns:1fr;gap:8px}
.view-compact .firewall-card{padding:12px}
.status-loading{opacity:.65}
.firmware-versions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0}
.firmware-versions div{padding:8px;border-radius:7px;background:rgba(127,127,127,.08)}
.firmware-versions strong{display:block;font-size:.82rem;margin-bottom:3px}
.card-update-button.hidden{display:none}
.card-message{font-size:.9rem;opacity:.78;margin:8px 0}
</style>

<div class="page-title">
    <div>
        <h1>Firewalls</h1>
        <p>Live status loads in the background.</p>
    </div>

    <div class="view-toolbar">
        <div class="view-switch">
            <button type="button" data-view="cards">Cards</button>
            <button type="button" data-view="compact">Compact</button>
            <button type="button" data-view="details">Details</button>
        </div>

        <button type="button" class="button secondary" id="refresh-all">
            Refresh status
        </button>

        <a class="button" href="/firewall_edit.php">
            Add firewall
        </a>
    </div>
</div>

<div id="firewall-dashboard" class="view-cards">
<?php if (!$firewalls): ?>
    <div class="empty">No firewalls configured.</div>
<?php else: ?>
    <div class="firewall-list">
        <?php foreach ($firewalls as $firewall): ?>
            <article
                class="card firewall-card"
                data-firewall-id="<?= (int) $firewall['id'] ?>"
            >
                <div class="card-head">
                    <div>
                        <h2><?= h((string) $firewall['name']) ?></h2>
                        <a
                            class="muted"
                            target="_blank"
                            rel="noopener"
                            href="<?= h((string) $firewall['base_url']) ?>"
                        >
                            <?= h((string) $firewall['base_url']) ?>
                        </a>
                    </div>

                    <span class="badge status-badge">Loading</span>
                </div>

                <dl>
                    <dt>System</dt>
                    <dd class="system-value status-loading">Loading…</dd>
                </dl>

                <div class="firmware-versions">
                    <div>
                        <strong>Current version</strong>
                        <span class="current-version status-loading">Loading…</span>
                    </div>
                    <div>
                        <strong>Available version</strong>
                        <span class="available-version status-loading">Loading…</span>
                    </div>
                </div>

                <div class="card-message firmware-message">
                    Loading firmware status…
                </div>

                <div class="actions">
                    <button type="button" class="button secondary refresh-one">
                        Refresh
                    </button>

                    <button
                        type="button"
                        class="warning card-update-button hidden"
                    >
                        Update now
                    </button>

                    <a
                        class="button secondary"
                        href="/firewall_view.php?id=<?= (int) $firewall['id'] ?>"
                    >
                        Details
                    </a>

                    <a
                        class="button secondary"
                        href="/firewall_edit.php?id=<?= (int) $firewall['id'] ?>"
                    >
                        Edit
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<script>
(function () {
    const csrfToken = <?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>;
    const dashboard = document.getElementById('firewall-dashboard');
    const cards = [...document.querySelectorAll('.firewall-card')];
    const viewButtons = document.querySelectorAll('[data-view]');
    const viewKey = 'opncentral-dashboard-view';

    function applyView(view) {
        if (!['cards', 'compact', 'details'].includes(view)) {
            view = 'cards';
        }

        dashboard.className = 'view-' + view;

        viewButtons.forEach(function (button) {
            button.classList.toggle('active', button.dataset.view === view);
        });

        localStorage.setItem(viewKey, view);
    }

    async function fetchType(id, type) {
        const response = await fetch(
            '/firewall_status.php?id=' +
            encodeURIComponent(id) +
            '&type=' +
            encodeURIComponent(type),
            {credentials: 'same-origin', cache: 'no-store'}
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

    async function runAction(id, action) {
        const body = new URLSearchParams();
        body.set('csrf', csrfToken);
        body.set('id', String(id));
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

    function setLoading(card) {
        card.querySelector('.status-badge').textContent = 'Loading';
        card.querySelector('.status-badge').className = 'badge status-badge';
        card.querySelector('.system-value').textContent = 'Loading…';
        card.querySelector('.current-version').textContent = 'Loading…';
        card.querySelector('.available-version').textContent = 'Loading…';
        card.querySelector('.firmware-message').textContent =
            'Loading firmware status…';
        card.querySelector('.card-update-button').classList.add('hidden');
        card.dataset.firmwareAction = '';
    }

    async function loadCard(card) {
        const id = card.dataset.firewallId;
        setLoading(card);

        const [systemResult, firmwareResult] = await Promise.allSettled([
            fetchType(id, 'system'),
            fetchType(id, 'firmware')
        ]);

        const badge = card.querySelector('.status-badge');
        const system = card.querySelector('.system-value');

        if (
            systemResult.status === 'fulfilled' &&
            systemResult.value?.ok === true
        ) {
            const value = systemResult.value.value || {};
            badge.className = 'badge status-badge good';
            badge.textContent = 'Online';
            system.textContent =
                value.status || value.result || value.message || 'Reachable';
        } else {
            const error = systemResult.status === 'rejected'
                ? systemResult.reason.message
                : (systemResult.value?.error || 'Unavailable');

            badge.className = 'badge status-badge bad';
            badge.textContent = 'Offline';
            system.textContent = error;
        }

        const current = card.querySelector('.current-version');
        const available = card.querySelector('.available-version');
        const message = card.querySelector('.firmware-message');
        const updateButton = card.querySelector('.card-update-button');

        if (
            firmwareResult.status === 'fulfilled' &&
            firmwareResult.value?.ok === true
        ) {
            const summary = firmwareResult.value.summary || {};

            current.textContent = summary.current_version || 'Unknown';
            available.textContent = summary.update_available
                ? (summary.available_version || 'Update available')
                : (summary.checked ? 'No update available' : 'Not checked');
            message.textContent = summary.message || '';

            if (summary.update_available && summary.action) {
                card.dataset.firmwareAction = summary.action;
                updateButton.textContent =
                    summary.action_label || 'Update now';
                updateButton.classList.remove('hidden');
            }
        } else {
            const error = firmwareResult.status === 'rejected'
                ? firmwareResult.reason.message
                : (firmwareResult.value?.error || 'Unavailable');

            current.textContent = 'Unknown';
            available.textContent = 'Unknown';
            message.textContent = error;
        }
    }

    async function installFromCard(card) {
        const id = card.dataset.firewallId;
        const action = card.dataset.firmwareAction;

        if (!action) {
            return;
        }

        const major = action === 'firmware_upgrade';

        if (!confirm(
            major
                ? 'Start the major OPNsense upgrade now? The firewall will reboot and may be unavailable for an extended period.'
                : 'Install the available OPNsense update now? The firewall may reboot.'
        )) {
            return;
        }

        const button = card.querySelector('.card-update-button');
        button.disabled = true;
        button.textContent = major ? 'Starting upgrade…' : 'Starting update…';

        try {
            const result = await runAction(id, action);
            alert(result.message || 'Firmware action started.');
            button.classList.add('hidden');
        } catch (error) {
            alert(error.message);
        } finally {
            button.disabled = false;
        }
    }

    function loadAll() {
        cards.forEach(function (card, index) {
            setTimeout(function () {
                loadCard(card);
            }, index * 150);
        });
    }

    viewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            applyView(button.dataset.view);
        });
    });

    cards.forEach(function (card) {
        card.querySelector('.refresh-one')
            ?.addEventListener('click', function () {
                loadCard(card);
            });

        card.querySelector('.card-update-button')
            ?.addEventListener('click', function () {
                installFromCard(card);
            });
    });

    document.getElementById('refresh-all')
        ?.addEventListener('click', loadAll);

    applyView(localStorage.getItem(viewKey) || 'cards');
    loadAll();
})();
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
