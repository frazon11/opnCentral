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
.firewall-list{display:grid;gap:16px;align-items:stretch}
.view-cards .firewall-list{grid-template-columns:repeat(3,minmax(0,1fr))}
.view-details .firewall-list{grid-template-columns:1fr}
.view-compact .firewall-list{display:grid;grid-template-columns:1fr;gap:8px}
.view-compact .firewall-card{padding:12px}
.firewall-card{display:flex;flex-direction:column;min-width:0}
.firewall-card .card-head{align-items:flex-start}
.firewall-card .card-head>div{min-width:0}
.firewall-card .card-head h2{overflow-wrap:anywhere}
.firewall-card .card-head a{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.firewall-card .actions{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:auto;align-items:stretch}
.firewall-card .actions button,.firewall-card .actions .button{width:100%;min-width:0;padding:8px 6px;text-align:center;white-space:normal;line-height:1.15}
.status-loading{opacity:.65}
.firmware-versions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:10px 0}
.firmware-versions div{padding:8px;border-radius:7px;background:rgba(127,127,127,.08);min-width:0}
.firmware-versions strong{display:block;font-size:.82rem;margin-bottom:3px}
.card-update-button.hidden{display:none}
.card-message{font-size:.9rem;opacity:.78;margin:8px 0 14px;min-height:3.4em}
@media(min-width:2100px){
    .view-cards .firewall-list{grid-template-columns:repeat(4,minmax(0,1fr))}
}
@media(max-width:1250px){
    .view-cards .firewall-list{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:760px){
    .view-cards .firewall-list{grid-template-columns:1fr}
    .firewall-card .actions{grid-template-columns:repeat(2,minmax(0,1fr))}
}
</style>

<div class="page-title">
    <div>
        <h1><?= h(t('dashboard.title')) ?></h1>
        <p><?= h(t('dashboard.subtitle')) ?></p>
    </div>

    <div class="view-toolbar">
        <div class="view-switch">
            <button type="button" data-view="cards"><?= h(t('common.cards')) ?></button>
            <button type="button" data-view="compact"><?= h(t('common.compact')) ?></button>
            <button type="button" data-view="details"><?= h(t('common.details')) ?></button>
        </div>

        <button type="button" class="button secondary" id="refresh-all">
            <?= h(t('common.refresh_status')) ?>
        </button>

        <a class="button" href="/firewall_edit.php">
            <?= h(t('menu.add_firewall')) ?>
        </a>
    </div>
</div>

<div id="firewall-dashboard" class="view-cards">
<?php if (!$firewalls): ?>
    <div class="empty"><?= h(t('dashboard.none')) ?></div>
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
                    <dt><?= h(t('common.system')) ?></dt>
                    <dd class="system-value status-loading"><?= h(t('common.loading')) ?></dd>
                </dl>

                <div class="firmware-versions">
                    <div>
                        <strong><?= h(t('dashboard.current_version')) ?></strong>
                        <span class="current-version status-loading"><?= h(t('common.loading')) ?></span>
                    </div>
                    <div>
                        <strong><?= h(t('dashboard.available_version')) ?></strong>
                        <span class="available-version status-loading"><?= h(t('common.loading')) ?></span>
                    </div>
                </div>

                <div class="card-message firmware-message">
                    Loading firmware status…
                </div>

                <div class="actions">
                    <button type="button" class="button secondary refresh-one">
                        Refresh
                    </button>

                    <button type="button" class="button secondary backup-one">
                        Backup now
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
    const tr = {"common.loading_short":<?= json_encode(t('common.loading_short'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.loading":<?= json_encode(t('common.loading'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.online":<?= json_encode(t('common.online'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.offline":<?= json_encode(t('common.offline'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.reachable":<?= json_encode(t('common.reachable'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.unavailable":<?= json_encode(t('common.unavailable'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.unknown":<?= json_encode(t('common.unknown'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.not_checked":<?= json_encode(t('common.not_checked'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.no_update":<?= json_encode(t('common.no_update'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.update_available":<?= json_encode(t('common.update_available'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.update_now":<?= json_encode(t('common.update_now'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"common.upgrade_now":<?= json_encode(t('common.upgrade_now'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.loading_firmware":<?= json_encode(t('dashboard.loading_firmware'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.start_update_confirm":<?= json_encode(t('dashboard.start_update_confirm'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.start_upgrade_confirm":<?= json_encode(t('dashboard.start_upgrade_confirm'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.starting_update":<?= json_encode(t('dashboard.starting_update'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.starting_upgrade":<?= json_encode(t('dashboard.starting_upgrade'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"dashboard.action_started":<?= json_encode(t('dashboard.action_started'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_system":<?= json_encode(t('details.loading_system'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_firmware":<?= json_encode(t('details.loading_firmware'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>,"details.loading_vpn":<?= json_encode(t('details.loading_vpn'), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>};
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
        card.querySelector('.status-badge').textContent = tr['common.loading_short'];
        card.querySelector('.status-badge').className = 'badge status-badge';
        card.querySelector('.system-value').textContent = tr['common.loading'];
        card.querySelector('.current-version').textContent = tr['common.loading'];
        card.querySelector('.available-version').textContent = tr['common.loading'];
        card.querySelector('.firmware-message').textContent =
            tr['dashboard.loading_firmware'];
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
            badge.textContent = tr['common.online'];
            system.textContent =
                value.status || value.result || value.message || tr['common.reachable'];
        } else {
            const error = systemResult.status === 'rejected'
                ? systemResult.reason.message
                : (systemResult.value?.error || tr['common.unavailable']);

            badge.className = 'badge status-badge bad';
            badge.textContent = tr['common.offline'];
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

            current.textContent = summary.current_version || tr['common.unknown'];
            available.textContent = summary.update_available
                ? (summary.available_version || tr['common.update_available'])
                : (summary.checked ? tr['common.no_update'] : tr['common.not_checked']);
            message.textContent = summary.message || '';

            if (summary.update_available && summary.action) {
                card.dataset.firmwareAction = summary.action;
                updateButton.textContent =
                    summary.action_label || tr['common.update_now'];
                updateButton.classList.remove('hidden');
            }
        } else {
            const error = firmwareResult.status === 'rejected'
                ? firmwareResult.reason.message
                : (firmwareResult.value?.error || tr['common.unavailable']);

            current.textContent = tr['common.unknown'];
            available.textContent = tr['common.unknown'];
            message.textContent = error;
        }
    }

    async function backupFromCard(card) {
        const id = card.dataset.firewallId;
        const button = card.querySelector('.backup-one');

        if (!confirm('Create a configuration backup for this firewall now?')) {
            return;
        }

        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Backing up…';

        try {
            const body = new URLSearchParams();
            body.set('csrf', csrfToken);
            body.set('action', 'backup_one');
            body.set('firewall_id', String(id));

            const response = await fetch('/backups_action.php', {
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
                throw new Error(result.error || 'Backup failed.');
            }

            const download = confirm(
                result.message + '\n\nDownload this backup now?'
            );

            if (download && result.download_url) {
                window.location.href = result.download_url;
            }
        } catch (error) {
            alert(error.message);
        } finally {
            button.disabled = false;
            button.textContent = originalText;
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

        card.querySelector('.backup-one')
            ?.addEventListener('click', function () {
                backupFromCard(card);
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
