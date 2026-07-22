<?php
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_once __DIR__ . '/inc/category_central.php';
require_login();
central_category_init();

$firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $name = trim((string) ($_POST['name'] ?? ''));
        $color = central_category_normalize_color((string) ($_POST['color'] ?? ''));
        $automatic = isset($_POST['automatic']) ? 1 : 0;
        $mode = (string) ($_POST['mode'] ?? 'create');
        $targetIds = array_values(array_unique(array_map(
            'intval',
            (array) ($_POST['targets'] ?? [])
        )));

        if ($name === '' || mb_strlen($name) > 255) {
            throw new RuntimeException('Enter a category name with at most 255 characters.');
        }

        if (!in_array($mode, ['create', 'replace'], true)) {
            throw new RuntimeException('Invalid distribution mode.');
        }

        if (!$targetIds) {
            throw new RuntimeException('Select at least one firewall.');
        }

        $categoryId = central_category_save_definition($name, $color, $automatic);
        $placeholders = implode(',', array_fill(0, count($targetIds), '?'));
        $statement = db()->prepare(
            'SELECT * FROM firewalls WHERE id IN (' . $placeholders . ') ORDER BY name'
        );
        $statement->execute($targetIds);

        foreach ($statement->fetchAll() as $firewall) {
            try {
                $existing = central_category_search($firewall, $name);

                if ($existing === null) {
                    $response = opn_request(
                        $firewall,
                        'firewall/category/add_item',
                        'POST',
                        central_category_payload($name, $color, $automatic),
                        20
                    );
                    $message = 'Created.';
                } else {
                    if ($mode === 'create') {
                        throw new RuntimeException(
                            'Category already exists; create-only mode made no change.'
                        );
                    }

                    $uuid = (string) ($existing['uuid'] ?? '');
                    if ($uuid === '') {
                        throw new RuntimeException('Existing category has no UUID.');
                    }

                    $response = opn_request(
                        $firewall,
                        'firewall/category/set_item/' . rawurlencode($uuid),
                        'POST',
                        central_category_payload($name, $color, $automatic, $existing),
                        20
                    );
                    $message = 'Replaced.';
                }

                if (
                    isset($response['result'])
                    && !in_array((string) $response['result'], ['saved', 'ok'], true)
                    && !isset($response['uuid'])
                ) {
                    throw new RuntimeException(
                        'OPNsense rejected the category: ' . json_encode($response)
                    );
                }

                central_category_target_status(
                    $categoryId,
                    (int) $firewall['id'],
                    'synchronized',
                    $message
                );

                $results[] = [
                    'name' => (string) $firewall['name'],
                    'ok' => true,
                    'message' => $message,
                ];
            } catch (Throwable $exception) {
                central_category_target_status(
                    $categoryId,
                    (int) $firewall['id'],
                    'error',
                    $exception->getMessage()
                );

                $results[] = [
                    'name' => (string) $firewall['name'],
                    'ok' => false,
                    'message' => $exception->getMessage(),
                ];
            }
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

require __DIR__ . '/inc/header.php';
?>
<style>
.category-layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr);gap:20px}.category-form label{display:block;font-weight:700;margin:14px 0 6px}.category-form input[type=text],.category-form select{width:100%;box-sizing:border-box}.target-list,.result-list{display:grid;gap:8px;margin-top:8px}.target-item,.result-item{display:flex;gap:8px;align-items:center;padding:10px;border-radius:8px;background:rgba(127,127,127,.08)}.result-item{display:block}.result-item.ok{border-left:4px solid #2aa84a}.result-item.bad{border-left:4px solid #d74747}.result-item strong{display:block;margin-bottom:4px}.help{font-size:.9rem;opacity:.75;margin-top:5px}@media(max-width:850px){.category-layout{grid-template-columns:1fr}}
</style>

<div class="page-title">
    <div>
        <h1>Central Categories</h1>
        <p>Create or update one firewall category on multiple OPNsense systems.</p>
    </div>
    <a class="button secondary" href="/category_overview.php">Category overview</a>
</div>

<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

<div class="category-layout">
<section class="card">
<h2>Category definition</h2>
<form method="post" class="category-form">
<input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

<label for="name">Category name</label>
<input id="name" name="name" type="text" required maxlength="255" value="<?= h((string)($_POST['name'] ?? 'opnCentral')) ?>">

<label for="color">Color</label>
<input id="color" name="color" type="text" value="<?= h((string)($_POST['color'] ?? '#f0ad4e')) ?>" placeholder="#f0ad4e">
<div class="help">Optional six-digit hexadecimal color.</div>

<label for="mode">If the category already exists</label>
<?php $selectedMode = (string)($_POST['mode'] ?? 'create'); ?>
<select id="mode" name="mode">
<option value="create" <?= $selectedMode === 'create' ? 'selected' : '' ?>>Create only</option>
<option value="replace" <?= $selectedMode === 'replace' ? 'selected' : '' ?>>Replace settings</option>
</select>

<label><input type="checkbox" name="automatic" value="1" <?= isset($_POST['automatic']) ? 'checked' : '' ?>> Automatic category</label>
<div class="help">Automatic categories may be removed by OPNsense when no longer used. Leave this disabled for centrally managed categories.</div>

<label>Target firewalls</label>
<div class="target-list">
<?php $selectedTargets = array_map('intval', (array)($_POST['targets'] ?? [])); ?>
<?php if (!$firewalls): ?><div class="empty">No firewalls configured.</div><?php endif; ?>
<?php foreach ($firewalls as $firewall): ?>
<label class="target-item">
<input type="checkbox" name="targets[]" value="<?= (int)$firewall['id'] ?>" <?= in_array((int)$firewall['id'], $selectedTargets, true) ? 'checked' : '' ?>>
<span><strong><?= h((string)$firewall['name']) ?></strong><br><span class="muted"><?= h((string)$firewall['base_url']) ?></span></span>
</label>
<?php endforeach; ?>
</div>

<div class="actions">
<button type="button" class="secondary" id="select-all-firewalls">Select all</button>
<button type="submit" onclick="return confirm('Distribute this category to the selected firewalls?')">Distribute category</button>
</div>
</form>
</section>

<section class="card">
<h2>Distribution results</h2>
<?php if (!$results): ?><div class="empty">Results will appear here after distribution.</div><?php else: ?>
<div class="result-list">
<?php foreach ($results as $result): ?>
<div class="result-item <?= $result['ok'] ? 'ok' : 'bad' ?>"><strong><?= h($result['name']) ?></strong><?= h($result['message']) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</div>

<script>
document.getElementById('select-all-firewalls')?.addEventListener('click',function(){document.querySelectorAll('input[name="targets[]"]').forEach(function(box){box.checked=true;});});
</script>
<?php require __DIR__ . '/inc/footer.php'; ?>
