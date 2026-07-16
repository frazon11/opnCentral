<?php

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$f = firewall_by_id($id);

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'backup') {
            if (!is_dir(BACKUP_DIR)) {
                mkdir(BACKUP_DIR, 0770, true);
            }

            $safe = preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '_',
                $f['name']
            );

            $filename =
                BACKUP_DIR . '/' .
                $safe . '-' .
                gmdate('Ymd-His') .
                '.xml';

            file_put_contents(
                $filename,
                opn_download(
                    $f,
                    'core/backup/download'
                ),
                LOCK_EX
            );

            $msg = 'Backup saved: ' . basename($filename);
        } elseif ($action === 'firmware_check') {
            $fw = opn_request(
                $f,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $msg = 'Firmware update check completed.';
        } elseif ($action === 'reboot') {
            opn_request(
                $f,
                'core/system/reboot',
                'POST',
                []
            );

            $msg = 'Reboot submitted.';
        } elseif ($action === 'delete') {
            $stmt = db()->prepare(
                'DELETE FROM firewalls WHERE id = ?'
            );

            $stmt->execute([$id]);

            header('Location: /');
            exit;
        }
    } catch (Throwable $exception) {
        $err = $exception->getMessage();
    }
}

$st = null;
$fw = null;
$sv = null;

try {
    $st = opn_request(
        $f,
        'core/system/status'
    );
} catch (Throwable $exception) {
    $err = $err ?: $exception->getMessage();
}

try {
    /*
     * Read the currently cached firmware status.
     * A new check can be triggered with the button below.
     */
    $fw = opn_request(
        $f,
        'core/firmware/status'
    );
} catch (Throwable $exception) {
    $fw = [
        'status_msg' => $exception->getMessage(),
    ];
}

try {
    $sv = opn_request(
        $f,
        'core/service/search'
    );
} catch (Throwable $exception) {
    $sv = [
        'status_msg' => $exception->getMessage(),
    ];
}

require __DIR__ . '/inc/header.php';

?>

<div class="page-title">
    <div>
        <h1><?= h($f['name']) ?></h1>
        <p><?= h($f['base_url']) ?></p>
    </div>

    <a
        class="button secondary"
        target="_blank"
        rel="noopener"
        href="<?= h($f['base_url']) ?>"
    >
        Open WebGUI
    </a>
</div>

<?php if ($msg): ?>

    <div class="alert goodbox">
        <?= h($msg) ?>
    </div>

<?php endif; ?>

<?php if ($err): ?>

    <div class="alert error">
        <?= h($err) ?>
    </div>

<?php endif; ?>

<div class="detail-grid">

    <section class="card">
        <h2>System</h2>

        <pre><?= h(
            json_encode(
                $st,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

    <section class="card">
        <h2>Firmware</h2>

        <pre><?= h(
            json_encode(
                $fw,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

    <section class="card wide">
        <h2>Services</h2>

        <pre><?= h(
            json_encode(
                $sv,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

</div>

<form
    method="post"
    class="actions danger-zone"
>
    <input
        type="hidden"
        name="csrf"
        value="<?= h(csrf_token()) ?>"
    >

    <button
        name="action"
        value="firmware_check"
    >
        Check for firmware updates
    </button>

    <button
        name="action"
        value="backup"
    >
        Download configuration backup
    </button>

    <button
        class="warning"
        name="action"
        value="reboot"
        onclick="return confirm('Really reboot?')"
    >
        Reboot firewall
    </button>

    <button
        class="danger"
        name="action"
        value="delete"
        onclick="return confirm('Delete entry?')"
    >
        Delete entry
    </button>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>