<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/alias_central.php';
require_login();
central_alias_init();

$aliases = db()->query('SELECT * FROM central_aliases ORDER BY name')->fetchAll();
$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$rows = [];

foreach ($aliases as $alias) {
    foreach ($firewalls as $firewall) {
        $status = 'unknown';
        $message = 'Not checked';

        try {
            $categoryUuid = central_alias_category_uuid($firewall);
            if ($categoryUuid === null) {
                $status = 'category missing';
                $message = 'Category opnCentral is missing.';
            } else {
                $remote = central_alias_find($firewall, $alias['name']);
                if ($remote === null) {
                    $status = 'missing';
                    $message = 'Alias does not exist.';
                } elseif (!central_alias_has_category($remote, $categoryUuid)) {
                    $status = 'different';
                    $message = 'Alias exists but is not in category opnCentral.';
                } else {
                    $sameType = (string)($remote['type'] ?? '') === (string)$alias['type'];
                    $sameEnabled = (int)($remote['enabled'] ?? 0) === (int)$alias['enabled'];
                    $sameContent = central_alias_lines((string)($remote['content'] ?? '')) === central_alias_lines((string)$alias['content']);

                    if ($sameType && $sameEnabled && $sameContent) {
                        $status = 'synchronized';
                        $message = 'Remote definition matches.';
                    } else {
                        $status = 'different';
                        $message = 'Type, enabled state or content differs.';
                    }
                }
            }
        } catch (Throwable $exception) {
            $status = 'unreachable';
            $message = $exception->getMessage();
        }

        central_alias_target_status((int)$alias['id'], (int)$firewall['id'], $status, $message);
        $rows[] = ['alias'=>$alias, 'firewall'=>$firewall, 'status'=>$status, 'message'=>$message];
    }
}

require __DIR__ . '/inc/header.php';
?>
<style>
.overview-table{width:100%;border-collapse:collapse}.overview-table th,.overview-table td{text-align:left;padding:10px;border-bottom:1px solid rgba(127,127,127,.18);vertical-align:top}.sync{font-weight:700}.sync.synchronized{color:#2aa84a}.sync.different,.sync.missing,.sync.category-missing{color:#d58a00}.sync.unreachable{color:#d74747}@media(max-width:750px){.overview-table,.overview-table tbody,.overview-table tr,.overview-table td{display:block}.overview-table thead{display:none}.overview-table tr{padding:12px 0}.overview-table td{border:0;padding:3px 0}}
</style>
<div class="page-title"><div><h1>Distributed aliases</h1><p>Live comparison with all configured firewalls.</p></div><a class="button" href="/aliases.php">Distribute alias</a></div>
<?php if (!$aliases): ?><div class="empty">No aliases have been distributed by opnCentral.</div><?php else: ?>
<section class="card"><table class="overview-table"><thead><tr><th>Alias</th><th>Type</th><th>Firewall</th><th>Status</th><th>Information</th><th>Last definition update</th></tr></thead><tbody>
<?php foreach ($rows as $row): $class = str_replace(' ', '-', $row['status']); ?><tr><td><strong><?= h($row['alias']['name']) ?></strong></td><td><?= h($row['alias']['type']) ?></td><td><?= h($row['firewall']['name']) ?></td><td><span class="sync <?= h($class) ?>"><?= h(ucfirst($row['status'])) ?></span></td><td><?= h($row['message']) ?></td><td><?= h($row['alias']['updated_at']) ?></td></tr><?php endforeach; ?>
</tbody></table></section><?php endif; ?>
<?php require __DIR__ . '/inc/footer.php'; ?>
