<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/alias_central.php';
require_login();
central_alias_init();

$aliases = db()->query('SELECT * FROM central_aliases ORDER BY name')->fetchAll();
$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
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
                        $remote = central_alias_find($firewall, (string)$alias['name']);
                        if ($remote === null) {
                            $status = 'missing';
                            $info = 'Alias does not exist.';
                        } elseif (!central_alias_has_category($remote, $categoryUuid)) {
                            $status = 'different';
                            $info = 'Alias exists but is not in category opnCentral.';
                        } else {
                            $sameType = (string)($remote['type'] ?? '') === (string)$alias['type'];
                            $sameEnabled = (int)($remote['enabled'] ?? 0) === (int)$alias['enabled'];
                            $sameContent = central_alias_lines((string)($remote['content'] ?? '')) === central_alias_lines((string)$alias['content']);
                            if ($sameType && $sameEnabled && $sameContent) {
                                $status = 'synchronized';
                                $info = 'Remote definition matches.';
                            } else {
                                $status = 'different';
                                $info = 'Type, enabled state or content differs.';
                            }
                        }
                    }
                } catch (Throwable $exception) {
                    $status = 'unreachable';
                    $info = $exception->getMessage();
                }
                central_alias_target_status((int)$alias['id'], (int)$firewall['id'], $status, $info);
            }
        }
        $message = 'Alias synchronization check completed.';
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$rows = db()->query(
    'SELECT a.name, a.type, a.updated_at, f.name AS firewall_name,
            COALESCE(t.last_status,"unknown") AS last_status,
            COALESCE(t.last_message,"Not checked") AS last_message,
            t.last_checked_at
     FROM central_aliases a
     CROSS JOIN firewalls f
     LEFT JOIN central_alias_targets t
       ON t.alias_id=a.id AND t.firewall_id=f.id
     ORDER BY a.name, f.name'
)->fetchAll();

require __DIR__ . '/inc/header.php';
?>
<style>.overview-table{width:100%;border-collapse:collapse}.overview-table th,.overview-table td{text-align:left;padding:10px;border-bottom:1px solid rgba(127,127,127,.18);vertical-align:top}.sync{font-weight:700}.sync.synchronized{color:#2aa84a}.sync.different,.sync.missing,.sync.category-missing,.sync.unknown{color:#d58a00}.sync.unreachable{color:#d74747}</style>
<div class="page-title"><div><h1>Distributed aliases</h1><p>Shows the last stored synchronization result.</p></div><a class="button" href="/aliases.php">Distribute alias</a></div>
<?php if ($message): ?><div class="alert goodbox"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<form method="post" class="actions"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button name="action" value="check">Check synchronization</button></form>
<?php if (!$aliases): ?><div class="empty">No aliases have been distributed by opnCentral.</div><?php else: ?>
<section class="card"><table class="overview-table"><thead><tr><th>Alias</th><th>Type</th><th>Firewall</th><th>Status</th><th>Information</th><th>Last checked</th></tr></thead><tbody>
<?php foreach ($rows as $row): $class=str_replace(' ','-',(string)$row['last_status']); ?><tr><td><strong><?= h($row['name']) ?></strong></td><td><?= h($row['type']) ?></td><td><?= h($row['firewall_name']) ?></td><td><span class="sync <?= h($class) ?>"><?= h(ucfirst((string)$row['last_status'])) ?></span></td><td><?= h($row['last_message']) ?></td><td><?= h($row['last_checked_at'] ?: 'Never') ?></td></tr><?php endforeach; ?>
</tbody></table></section><?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
