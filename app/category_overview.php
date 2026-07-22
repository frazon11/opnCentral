<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/category_central.php';
require_login();
central_category_init();

$categories = db()->query('SELECT * FROM central_categories ORDER BY name')->fetchAll();
$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        foreach ($categories as $category) {
            foreach ($firewalls as $firewall) {
                $status = 'unknown';
                $info = 'Not checked';
                try {
                    $remote = central_category_search($firewall, (string)$category['name']);
                    if ($remote === null) {
                        $status = 'missing';
                        $info = 'Category does not exist.';
                    } else {
                        $remoteColor = strtolower((string)($remote['color'] ?? ''));
                        $localColor = strtolower((string)$category['color']);
                        $remoteAutomatic = (int)($remote['auto'] ?? $remote['automatic'] ?? 0);
                        if ($remoteColor === $localColor && $remoteAutomatic === (int)$category['automatic']) {
                            $status = 'synchronized';
                            $info = 'Remote definition matches.';
                        } else {
                            $status = 'different';
                            $info = 'Color or automatic setting differs.';
                        }
                    }
                } catch (Throwable $exception) {
                    $status = 'unreachable';
                    $info = $exception->getMessage();
                }
                central_category_target_status((int)$category['id'], (int)$firewall['id'], $status, $info);
            }
        }
        $message = 'Category synchronization check completed.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$rows = db()->query(
    'SELECT c.name, c.color, c.updated_at, f.name AS firewall_name,
            COALESCE(t.last_status,"unknown") AS last_status,
            COALESCE(t.last_message,"Not checked") AS last_message,
            t.last_checked_at
     FROM central_categories c
     CROSS JOIN firewalls f
     LEFT JOIN central_category_targets t
       ON t.category_id=c.id AND t.firewall_id=f.id
     ORDER BY c.name, f.name'
)->fetchAll();

require __DIR__ . '/inc/header.php';
?>
<style>.overview-table{width:100%;border-collapse:collapse}.overview-table th,.overview-table td{text-align:left;padding:10px;border-bottom:1px solid rgba(127,127,127,.18);vertical-align:top}.sync{font-weight:700}.sync.synchronized{color:#2aa84a}.sync.different,.sync.missing,.sync.unknown{color:#d58a00}.sync.unreachable{color:#d74747}</style>
<div class="page-title"><div><h1>Distributed categories</h1><p>Shows the last stored synchronization result.</p></div><a class="button" href="/categories.php">Distribute category</a></div>
<?php if ($message): ?><div class="alert goodbox"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="actions"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button name="action" value="check">Check synchronization</button></form>
<?php if (!$categories): ?><div class="empty">No categories have been distributed by opnCentral.</div><?php else: ?>
<section class="card"><table class="overview-table"><thead><tr><th>Category</th><th>Color</th><th>Firewall</th><th>Status</th><th>Information</th><th>Last checked</th></tr></thead><tbody>
<?php foreach ($rows as $row): $class=str_replace(' ','-',(string)$row['last_status']); ?><tr><td><strong><?= h($row['name']) ?></strong></td><td><?= h($row['color'] ?: 'Default') ?></td><td><?= h($row['firewall_name']) ?></td><td><span class="sync <?= h($class) ?>"><?= h(ucfirst((string)$row['last_status'])) ?></span></td><td><?= h($row['last_message']) ?></td><td><?= h($row['last_checked_at'] ?: 'Never') ?></td></tr><?php endforeach; ?>
</tbody></table></section><?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
