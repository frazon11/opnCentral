<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/category_central.php';
require_login();
central_category_init();

$categories = db()->query('SELECT * FROM central_categories ORDER BY name')->fetchAll();
$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$rows = [];

foreach ($categories as $category) {
    foreach ($firewalls as $firewall) {
        $status = 'unknown';
        $message = 'Not checked';

        try {
            $remote = central_category_search($firewall, (string)$category['name']);

            if ($remote === null) {
                $status = 'missing';
                $message = 'Category does not exist.';
            } else {
                $remoteColor = strtolower((string)($remote['color'] ?? ''));
                $localColor = strtolower((string)$category['color']);
                $remoteAutomatic = (int)($remote['auto'] ?? $remote['automatic'] ?? 0);

                if (
                    $remoteColor === $localColor
                    && $remoteAutomatic === (int)$category['automatic']
                ) {
                    $status = 'synchronized';
                    $message = 'Remote definition matches.';
                } else {
                    $status = 'different';
                    $message = 'Color or automatic setting differs.';
                }
            }
        } catch (Throwable $exception) {
            $status = 'unreachable';
            $message = $exception->getMessage();
        }

        central_category_target_status(
            (int)$category['id'],
            (int)$firewall['id'],
            $status,
            $message
        );

        $rows[] = [
            'category' => $category,
            'firewall' => $firewall,
            'status' => $status,
            'message' => $message,
        ];
    }
}

require __DIR__ . '/inc/header.php';
?>
<style>
.overview-table{width:100%;border-collapse:collapse}.overview-table th,.overview-table td{text-align:left;padding:10px;border-bottom:1px solid rgba(127,127,127,.18);vertical-align:top}.sync{font-weight:700}.sync.synchronized{color:#2aa84a}.sync.different,.sync.missing{color:#d58a00}.sync.unreachable{color:#d74747}.color-chip{display:inline-block;width:16px;height:16px;border-radius:4px;vertical-align:middle;margin-right:6px;border:1px solid rgba(127,127,127,.35)}@media(max-width:750px){.overview-table,.overview-table tbody,.overview-table tr,.overview-table td{display:block}.overview-table thead{display:none}.overview-table tr{padding:12px 0}.overview-table td{border:0;padding:3px 0}}
</style>
<div class="page-title"><div><h1>Distributed categories</h1><p>Live comparison with all configured firewalls.</p></div><a class="button" href="/categories.php">Distribute category</a></div>
<?php if (!$categories): ?><div class="empty">No categories have been distributed by opnCentral.</div><?php else: ?>
<section class="card"><table class="overview-table"><thead><tr><th>Category</th><th>Color</th><th>Firewall</th><th>Status</th><th>Information</th><th>Last definition update</th></tr></thead><tbody>
<?php foreach ($rows as $row): $class = str_replace(' ', '-', $row['status']); ?><tr><td><strong><?= h($row['category']['name']) ?></strong></td><td><?php if ($row['category']['color'] !== ''): ?><span class="color-chip" style="background:<?= h($row['category']['color']) ?>"></span><?php endif; ?><?= h($row['category']['color'] ?: 'Default') ?></td><td><?= h($row['firewall']['name']) ?></td><td><span class="sync <?= h($class) ?>"><?= h(ucfirst($row['status'])) ?></span></td><td><?= h($row['message']) ?></td><td><?= h($row['category']['updated_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></section><?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
