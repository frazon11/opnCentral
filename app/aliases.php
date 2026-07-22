<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/alias_central.php';
require_login();
central_alias_init();

$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'host'));
        $lines = central_alias_lines((string) ($_POST['content'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $mode = (string) ($_POST['mode'] ?? 'create');
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        $targetIds = array_values(array_unique(array_map('intval', (array) ($_POST['targets'] ?? []))));

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Alias name may contain only letters, numbers and underscores.');
        }
        if (!$lines) {
            throw new RuntimeException('Enter at least one alias value.');
        }
        if (!$targetIds) {
            throw new RuntimeException('Select at least one firewall.');
        }
        if (!in_array($mode, ['create', 'replace', 'merge'], true)) {
            throw new RuntimeException('Invalid distribution mode.');
        }

        $content = implode("\n", $lines);
        $aliasId = central_alias_save_definition($name, $type, $content, $description, $enabled);

        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $statement = db()->prepare('SELECT * FROM firewalls WHERE id IN (' . $placeholders . ') ORDER BY name');
        $statement->execute($targetIds);

        foreach ($statement->fetchAll() as $firewall) {
            try {
                $categoryUuid = central_alias_category_uuid($firewall);
                if ($categoryUuid === null) {
                    throw new RuntimeException('Category opnCentral is missing on this firewall. Create it under Firewall > Categories.');
                }

                $existing = central_alias_find($firewall, $name);
                $finalLines = $lines;
                $action = 'Created';

                if ($existing !== null) {
                    if ($mode === 'create') {
                        throw new RuntimeException('Alias already exists; create-only mode made no change.');
                    }

                    if (!central_alias_has_category($existing, $categoryUuid)) {
                        throw new RuntimeException('Existing alias is not in category opnCentral and was protected.');
                    }

                    if ($mode === 'merge') {
                        $finalLines = array_values(array_unique(array_merge(
                            central_alias_lines((string) ($existing['content'] ?? '')),
                            $lines
                        )));
                        sort($finalLines, SORT_NATURAL | SORT_FLAG_CASE);
                        $action = 'Merged';
                    } else {
                        $action = 'Replaced';
                    }

                    $payload = $existing;
                    unset($payload['uuid']);
                    $payload['enabled'] = (string) $enabled;
                    $payload['name'] = $name;
                    $payload['type'] = $type;
                    $payload['content'] = implode("\n", $finalLines);
                    $payload['description'] = $description;
                    $payload['categories'] = $categoryUuid;

                    opn_request(
                        $firewall,
                        'firewall/alias/set_item/' . rawurlencode((string) $existing['uuid']),
                        'POST',
                        ['alias' => $payload],
                        25
                    );
                } else {
                    opn_request(
                        $firewall,
                        'firewall/alias/add_item',
                        'POST',
                        ['alias' => [
                            'enabled' => (string) $enabled,
                            'name' => $name,
                            'type' => $type,
                            'content' => implode("\n", $finalLines),
                            'description' => $description,
                            'categories' => $categoryUuid,
                        ]],
                        25
                    );
                }

                opn_request($firewall, 'firewall/alias/reconfigure', 'POST', [], 30);
                central_alias_target_status($aliasId, (int) $firewall['id'], 'synchronized', $action . ' and applied.');
                $results[] = ['ok' => true, 'name' => $firewall['name'], 'message' => $action . ' and applied.'];
            } catch (Throwable $exception) {
                central_alias_target_status($aliasId, (int) $firewall['id'], 'error', $exception->getMessage());
                $results[] = ['ok' => false, 'name' => $firewall['name'], 'message' => $exception->getMessage()];
            }
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

require __DIR__ . '/inc/header.php';
?>
<style>
.alias-grid{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:20px}.alias-form label{display:block;font-weight:700;margin:14px 0 6px}.alias-form input[type=text],.alias-form select,.alias-form textarea{width:100%;box-sizing:border-box}.alias-form textarea{min-height:180px;font-family:monospace}.targets,.results{display:grid;gap:8px}.target,.result{padding:10px;border-radius:8px;background:rgba(127,127,127,.08)}.result.good{border-left:4px solid #2aa84a}.result.bad{border-left:4px solid #d74747}@media(max-width:850px){.alias-grid{grid-template-columns:1fr}}
</style>
<div class="page-title"><div><h1>Distribute alias</h1><p>Category opnCentral protects centrally managed aliases.</p></div><a class="button secondary" href="/alias_overview.php">Overview</a></div>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>
<div class="alias-grid">
<section class="card"><h2>Alias</h2><form method="post" class="alias-form"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
<label>Name</label><input type="text" name="name" required pattern="[A-Za-z0-9_]+" value="<?= h((string)($_POST['name'] ?? '')) ?>" placeholder="Trusted_Admins">
<label>Type</label><select name="type"><?php foreach (['host'=>'Host(s)','network'=>'Network(s)','port'=>'Port(s)','url'=>'URL','urltable'=>'URL table','networkgroup'=>'Network group','mac'=>'MAC','asn'=>'ASN'] as $value=>$label): ?><option value="<?= h($value) ?>" <?= (($_POST['type'] ?? 'host') === $value) ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select>
<label>Content</label><textarea name="content" required placeholder="One value per line"><?= h((string)($_POST['content'] ?? '')) ?></textarea>
<label>Description</label><input type="text" name="description" value="<?= h((string)($_POST['description'] ?? '')) ?>">
<label>Existing alias</label><select name="mode"><option value="create">Create only</option><option value="replace">Replace</option><option value="merge">Merge</option></select>
<label><input type="checkbox" name="enabled" value="1" checked> Enabled</label>
<label>Target firewalls</label><div class="targets"><?php foreach ($firewalls as $firewall): ?><label class="target"><input type="checkbox" name="targets[]" value="<?= (int)$firewall['id'] ?>"> <strong><?= h($firewall['name']) ?></strong><br><span class="muted"><?= h($firewall['base_url']) ?></span></label><?php endforeach; ?></div>
<div class="actions"><button type="button" id="all">Select all</button><button type="submit" onclick="return confirm('Distribute this alias?')">Distribute</button></div></form></section>
<section class="card"><h2>Results</h2><?php if (!$results): ?><div class="empty">No distribution performed yet.</div><?php else: ?><div class="results"><?php foreach ($results as $result): ?><div class="result <?= $result['ok'] ? 'good' : 'bad' ?>"><strong><?= h($result['name']) ?></strong><br><?= h($result['message']) ?></div><?php endforeach; ?></div><?php endif; ?></section>
</div>
<script>document.getElementById('all')?.addEventListener('click',()=>document.querySelectorAll('input[name="targets[]"]').forEach(x=>x.checked=true));</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
