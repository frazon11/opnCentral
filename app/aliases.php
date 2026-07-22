<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_login();

$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$results = [];
$formError = '';

function alias_lines(string $content): array
{
    $out = [];
    foreach (preg_split('/\R+/', $content) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '' && !in_array($line, $out, true)) {
            $out[] = $line;
        }
    }
    return $out;
}

function alias_uuid_from_response(array $response): ?string
{
    foreach ([$response['uuid'] ?? null, $response['result'] ?? null, $response['alias']['uuid'] ?? null] as $value) {
        if (is_string($value) && preg_match('/^[a-f0-9-]{20,}$/i', $value)) {
            return $value;
        }
    }
    return isset($response['rows'][0]['uuid']) ? (string) $response['rows'][0]['uuid'] : null;
}

function find_alias_uuid(array $firewall, string $name): ?string
{
    try {
        $response = opn_request(
            $firewall,
            'firewall/alias/get_alias_u_u_i_d/' . rawurlencode($name),
            'GET',
            [],
            15
        );
        $uuid = alias_uuid_from_response($response);
        if ($uuid) {
            return $uuid;
        }
    } catch (Throwable $e) {
    }

    $response = opn_request(
        $firewall,
        'firewall/alias/search_item',
        'POST',
        ['current' => 1, 'rowCount' => 100, 'searchPhrase' => $name],
        15
    );

    foreach (($response['rows'] ?? []) as $row) {
        if (isset($row['name'], $row['uuid']) && strcasecmp((string) $row['name'], $name) === 0) {
            return (string) $row['uuid'];
        }
    }

    return null;
}

function get_alias_item(array $firewall, string $uuid): array
{
    $response = opn_request(
        $firewall,
        'firewall/alias/get_item/' . rawurlencode($uuid),
        'GET',
        [],
        15
    );
    return is_array($response['alias'] ?? null) ? $response['alias'] : $response;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        $type = trim((string) ($_POST['type'] ?? 'host'));
        $content = alias_lines((string) ($_POST['content'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? 'Managed by opnCentral'));
        $mode = (string) ($_POST['mode'] ?? 'create');
        $enabled = isset($_POST['enabled']) ? '1' : '0';
        $targetIds = array_values(array_unique(array_map('intval', (array) ($_POST['targets'] ?? []))));

        $types = ['host','network','port','url','urltable','networkgroup','mac','asn'];
        $modes = ['create','replace','merge'];

        if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Alias name may contain only letters, numbers and underscores.');
        }
        if (!in_array($type, $types, true)) {
            throw new RuntimeException('Unsupported alias type.');
        }
        if (!in_array($mode, $modes, true)) {
            throw new RuntimeException('Unsupported mode.');
        }
        if (!$content) {
            throw new RuntimeException('Enter at least one alias value.');
        }
        if (!$targetIds) {
            throw new RuntimeException('Select at least one firewall.');
        }
        if (stripos($description, 'Managed by opnCentral') === false) {
            $description = trim($description . ' | Managed by opnCentral', ' |');
        }

        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $stmt = db()->prepare('SELECT * FROM firewalls WHERE id IN (' . $placeholders . ') ORDER BY name');
        $stmt->execute($targetIds);

        foreach ($stmt->fetchAll() as $firewall) {
            try {
                $uuid = find_alias_uuid($firewall, $name);
                $finalContent = $content;
                $action = 'Created';

                if ($uuid === null) {
                    $response = opn_request(
                        $firewall,
                        'firewall/alias/add_item',
                        'POST',
                        ['alias' => [
                            'enabled' => $enabled,
                            'name' => $name,
                            'type' => $type,
                            'content' => implode("\n", $finalContent),
                            'description' => $description,
                        ]],
                        20
                    );
                } else {
                    if ($mode === 'create') {
                        throw new RuntimeException('Alias already exists.');
                    }

                    $existing = get_alias_item($firewall, $uuid);
                    $existingDescription = (string) ($existing['description'] ?? '');

                    if ($mode === 'replace' && stripos($existingDescription, 'Managed by opnCentral') === false) {
                        throw new RuntimeException('Existing alias is not marked as managed by opnCentral.');
                    }

                    if ($mode === 'merge') {
                        $finalContent = array_values(array_unique(array_merge(
                            alias_lines((string) ($existing['content'] ?? '')),
                            $content
                        )));
                        $action = 'Merged';
                    } else {
                        $action = 'Replaced';
                    }

                    $payload = $existing;
                    unset($payload['uuid']);
                    $payload['enabled'] = $enabled;
                    $payload['name'] = $name;
                    $payload['type'] = $type;
                    $payload['content'] = implode("\n", $finalContent);
                    $payload['description'] = $description;

                    $response = opn_request(
                        $firewall,
                        'firewall/alias/set_item/' . rawurlencode($uuid),
                        'POST',
                        ['alias' => $payload],
                        20
                    );
                }

                if (isset($response['result']) && !in_array((string) $response['result'], ['saved','ok'], true)) {
                    throw new RuntimeException('OPNsense rejected the change: ' . json_encode($response));
                }

                opn_request($firewall, 'firewall/alias/reconfigure', 'POST', [], 30);

                $results[] = ['name' => (string) $firewall['name'], 'ok' => true, 'message' => $action . ' and applied.'];
            } catch (Throwable $e) {
                $results[] = ['name' => (string) $firewall['name'], 'ok' => false, 'message' => $e->getMessage()];
            }
        }
    } catch (Throwable $e) {
        $formError = $e->getMessage();
    }
}

require __DIR__ . '/inc/header.php';
?>

<style>
.alias-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(280px,.8fr);gap:20px}.alias-form label{display:block;font-weight:700;margin:14px 0 6px}.alias-form input[type=text],.alias-form select,.alias-form textarea{width:100%;box-sizing:border-box}.alias-form textarea{min-height:190px;font-family:monospace}.target-list{display:grid;gap:8px;margin-top:8px}.target-item{display:flex;gap:8px;align-items:center;padding:9px;border-radius:8px;background:rgba(127,127,127,.08)}.mode-help{font-size:.9rem;opacity:.75;margin-top:5px}.result-list{display:grid;gap:10px}.result-item{padding:12px;border-radius:8px;background:rgba(127,127,127,.08)}.result-item.ok{border-left:4px solid #2aa84a}.result-item.bad{border-left:4px solid #d74747}.result-item strong{display:block;margin-bottom:4px}@media(max-width:850px){.alias-layout{grid-template-columns:1fr}}
</style>

<div class="page-title"><div><h1>Central Aliases</h1><p>Create or distribute one alias to multiple OPNsense firewalls.</p></div></div>

<?php if ($formError): ?><div class="alert error"><?= h($formError) ?></div><?php endif; ?>

<div class="alias-layout">
<section class="card">
<h2>Alias definition</h2>
<form method="post" class="alias-form">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

<label for="name">Alias name</label>
<input id="name" name="name" type="text" required pattern="[A-Za-z0-9_]+" value="<?= h((string) ($_POST['name'] ?? '')) ?>" placeholder="Trusted_Admins">

<label for="type">Type</label>
<?php $selectedType = (string) ($_POST['type'] ?? 'host'); ?>
<select id="type" name="type">
<?php foreach (['host'=>'Host(s)','network'=>'Network(s)','port'=>'Port(s)','url'=>'URL','urltable'=>'URL table','networkgroup'=>'Network group','mac'=>'MAC address','asn'=>'ASN'] as $value=>$label): ?>
<option value="<?= h($value) ?>" <?= $selectedType === $value ? 'selected' : '' ?>><?= h($label) ?></option>
<?php endforeach; ?>
</select>

<label for="content">Content</label>
<textarea id="content" name="content" required placeholder="One value per line"><?= h((string) ($_POST['content'] ?? '')) ?></textarea>

<label for="description">Description</label>
<input id="description" name="description" type="text" value="<?= h((string) ($_POST['description'] ?? 'Managed by opnCentral')) ?>">

<label for="mode">If the alias already exists</label>
<?php $selectedMode = (string) ($_POST['mode'] ?? 'create'); ?>
<select id="mode" name="mode">
<option value="create" <?= $selectedMode === 'create' ? 'selected' : '' ?>>Create only</option>
<option value="replace" <?= $selectedMode === 'replace' ? 'selected' : '' ?>>Replace centrally managed alias</option>
<option value="merge" <?= $selectedMode === 'merge' ? 'selected' : '' ?>>Merge entries</option>
</select>
<div class="mode-help">Replace protects locally managed aliases. Merge keeps existing entries.</div>

<label><input type="checkbox" name="enabled" value="1" <?= $_SERVER['REQUEST_METHOD'] !== 'POST' || isset($_POST['enabled']) ? 'checked' : '' ?>> Enabled</label>

<label>Target firewalls</label>
<div class="target-list">
<?php $selectedTargets = array_map('intval', (array) ($_POST['targets'] ?? [])); ?>
<?php foreach ($firewalls as $firewall): ?>
<label class="target-item"><input type="checkbox" name="targets[]" value="<?= (int) $firewall['id'] ?>" <?= in_array((int) $firewall['id'], $selectedTargets, true) ? 'checked' : '' ?>><span><strong><?= h((string) $firewall['name']) ?></strong><br><span class="muted"><?= h((string) $firewall['base_url']) ?></span></span></label>
<?php endforeach; ?>
</div>

<div class="actions"><button type="button" class="secondary" id="select-all-firewalls">Select all</button><button type="submit" onclick="return confirm('Distribute this alias to the selected firewalls?')">Distribute alias</button></div>
</form>
</section>

<section class="card"><h2>Distribution results</h2>
<?php if (!$results): ?><div class="empty">Results will appear here after distribution.</div><?php else: ?><div class="result-list"><?php foreach ($results as $result): ?><div class="result-item <?= $result['ok'] ? 'ok' : 'bad' ?>"><strong><?= h($result['name']) ?></strong><?= h($result['message']) ?></div><?php endforeach; ?></div><?php endif; ?>
</section>
</div>

<script>document.getElementById('select-all-firewalls')?.addEventListener('click',function(){document.querySelectorAll('input[name="targets[]"]').forEach(function(box){box.checked=true;});});</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
