<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/alias_central.php';
require_login();
central_alias_init();

$aliases = db()
    ->query('SELECT * FROM central_aliases ORDER BY name')
    ->fetchAll();
$firewalls = db()
    ->query('SELECT * FROM firewalls ORDER BY name')
    ->fetchAll();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        foreach ($aliases as $alias) {
            foreach ($firewalls as $firewall) {
                $status = 'unknown';
                $info = 'Not checked';

                try {
                    $categoryUuid = central_alias_category_uuid($firewall);

                    if ($categoryUuid === null) {
                        $status = 'category missing';
                        $info = 'Category opnCentral is missing.';
                    } else {
                        $remote = central_alias_find(
                            $firewall,
                            (string) $alias['name']
                        );

                        if ($remote === null) {
                            $status = 'missing';
                            $info = 'Alias does not exist.';
                        } elseif (
                            !central_alias_has_category(
                                $remote,
                                $categoryUuid
                            )
                        ) {
                            $status = 'different';
                            $info =
                                'Alias exists but is not in category ' .
                                'opnCentral.';
                        } else {
                            $sameType =
                                (string) ($remote['type'] ?? '') ===
                                (string) $alias['type'];
                            $sameEnabled =
                                (int) ($remote['enabled'] ?? 0) ===
                                (int) $alias['enabled'];
                            $sameContent =
                                central_alias_lines(
                                    (string) ($remote['content'] ?? '')
                                ) ===
                                central_alias_lines(
                                    (string) $alias['content']
                                );

                            if (
                                $sameType &&
                                $sameEnabled &&
                                $sameContent
                            ) {
                                $status = 'synchronized';
                                $info = 'Remote definition matches.';
                            } else {
                                $status = 'different';
                                $info =
                                    'Type, enabled state or content differs.';
                            }
                        }
                    }
                } catch (Throwable $exception) {
                    $status = 'unreachable';
                    $info = $exception->getMessage();
                }

                central_alias_target_status(
                    (int) $alias['id'],
                    (int) $firewall['id'],
                    $status,
                    $info
                );
            }
        }

        $message = 'Alias synchronization check completed.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$rows = db()->query(
    'SELECT f.id AS firewall_id, f.name AS firewall_name,
            f.base_url, a.name, a.type, a.enabled,
            COALESCE(t.last_status,"unknown") AS last_status,
            COALESCE(t.last_message,"Not checked") AS last_message,
            t.last_checked_at
     FROM firewalls f
     CROSS JOIN central_aliases a
     LEFT JOIN central_alias_targets t
       ON t.alias_id=a.id AND t.firewall_id=f.id
     ORDER BY f.name, a.name'
)->fetchAll();

$grouped = [];

foreach ($firewalls as $firewall) {
    $grouped[(int) $firewall['id']] = [
        'firewall' => $firewall,
        'items' => [],
    ];
}

foreach ($rows as $row) {
    $grouped[(int) $row['firewall_id']]['items'][] = $row;
}

require __DIR__ . '/inc/header.php';
?>

<div class="page-title management-page-title">
    <div>
        <h1><?= h(t('aliases.distributed')) ?></h1>
        <p><?= h(t('aliases.stored_result')) ?></p>
    </div>

    <div class="management-toolbar">
        <form method="post">
            <input
                type="hidden"
                name="csrf"
                value="<?= h(csrf_token()) ?>"
            >
            <button name="action" value="check">
                <?= h(t('aliases.check_sync')) ?>
            </button>
        </form>

        <a class="button" href="/aliases.php">
            Add alias
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert goodbox"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error"><?= h($error) ?></div>
<?php endif; ?>

<div class="management-overview-bar">
    <div>
        <strong>Alias overview</strong>
        <div class="management-summary">
            <?= count($firewalls) ?> firewalls ·
            <?= count($aliases) ?> centrally managed aliases
        </div>
    </div>
</div>

<div class="vpn-summary-list">
<?php if (!$firewalls): ?>
    <section class="card vpn-summary-card">
        <p class="muted"><?= h(t('dashboard.none')) ?></p>
    </section>
<?php endif; ?>

<?php foreach ($grouped as $group):
    $items = $group['items'];
    $syncCount = count(array_filter(
        $items,
        static fn(array $item): bool =>
            $item['last_status'] === 'synchronized'
    ));
    $issueCount = count($items) - $syncCount;
?>
    <section class="card vpn-summary-card">
        <div class="vpn-summary-main">
            <div class="vpn-summary-identity">
                <h2><?= h((string)$group['firewall']['name']) ?></h2>
                <a
                    class="muted"
                    href="<?= h((string)$group['firewall']['base_url']) ?>"
                    target="_blank"
                    rel="noopener"
                >
                    <?= h((string)$group['firewall']['base_url']) ?>
                </a>
            </div>

            <div class="vpn-summary-metric">
                <span class="vpn-summary-label">Aliases</span>
                <span class="badge neutral"><?= count($items) ?></span>
            </div>

            <div class="vpn-summary-metric">
                <span class="vpn-summary-label">Summary</span>
                <span class="muted">
                    <?= $syncCount ?> synchronized
                    <?php if ($issueCount): ?>
                        · <?= $issueCount ?> issues
                    <?php endif; ?>
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
                    <strong>Managed aliases</strong>
                    <div class="muted">
                        <?= h((string)$group['firewall']['name']) ?>
                    </div>
                </div>

                <a
                    class="button"
                    href="/aliases.php?firewall_id=<?=
                        (int)$group['firewall']['id']
                    ?>"
                >
                    Add to this OPNsense
                </a>
            </div>

            <div class="table-scroll management-table-wrap">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Alias</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Information</th>
                            <th>Last checked</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$items): ?>
                        <tr>
                            <td colspan="5">
                                <?= h(t('aliases.none')) ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($items as $item):
                        $class = str_replace(
                            ' ',
                            '-',
                            (string)$item['last_status']
                        );
                    ?>
                        <tr>
                            <td>
                                <strong><?= h($item['name']) ?></strong>
                            </td>
                            <td><?= h($item['type']) ?></td>
                            <td>
                                <span class="sync <?= h($class) ?>">
                                    <?= h(ucfirst(
                                        (string)$item['last_status']
                                    )) ?>
                                </span>
                            </td>
                            <td><?= h($item['last_message']) ?></td>
                            <td>
                                <?= h($item['last_checked_at'] ?: 'Never') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endforeach; ?>
</div>

<script>
document.querySelectorAll('.vpn-details-toggle').forEach(function(button){
    button.addEventListener('click',function(){
        const card=button.closest('.vpn-summary-card');
        const panel=card.querySelector('.vpn-details-panel');
        const expanded=button.getAttribute('aria-expanded')==='true';

        button.setAttribute(
            'aria-expanded',
            expanded ? 'false' : 'true'
        );
        button.textContent=expanded ? 'Details' : 'Hide details';
        panel.hidden=expanded;
        card.classList.toggle('vpn-summary-expanded',!expanded);
    });
});
</script>

<?php require __DIR__ . '/inc/footer.php'; ?>
