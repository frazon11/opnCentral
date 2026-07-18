<?php

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

$id = (int) ($_GET['id'] ?? 0);
$firewall = firewall_by_id($id);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    try {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'backup') {
            if (
                !is_dir(BACKUP_DIR)
                && !mkdir(BACKUP_DIR, 0770, true)
                && !is_dir(BACKUP_DIR)
            ) {
                throw new RuntimeException(
                    'Cannot create the backup directory.'
                );
            }

            $safeName = preg_replace(
                '/[^A-Za-z0-9._-]+/',
                '_',
                (string) $firewall['name']
            );

            $filename =
                BACKUP_DIR . '/' .
                $safeName . '-' .
                gmdate('Ymd-His') .
                '.xml';

            $backupData = opn_download(
                $firewall,
                'core/backup/download/this'
            );

            if ($backupData === '') {
                throw new RuntimeException(
                    'OPNsense returned an empty configuration backup.'
                );
            }

            $written = file_put_contents(
                $filename,
                $backupData,
                LOCK_EX
            );

            if ($written === false) {
                throw new RuntimeException(
                    'The configuration backup could not be saved.'
                );
            }

            $message = 'Backup saved: ' . basename($filename);
        } elseif ($action === 'firmware_check') {
            opn_request(
                $firewall,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $message = 'Firmware update check completed.';
        } elseif ($action === 'firmware_update') {
            opn_request(
                $firewall,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $updateResult = opn_request(
                $firewall,
                'core/firmware/update',
                'POST',
                [],
                30
            );

            $updateMessage =
                $updateResult['status_msg']
                ?? $updateResult['message']
                ?? $updateResult['status']
                ?? 'Firmware update command accepted.';

            $message =
                'Firmware update started: ' .
                (string) $updateMessage .
                ' The firewall may reboot and temporarily become unavailable.';
        } elseif ($action === 'reboot') {
            opn_request(
                $firewall,
                'core/system/reboot',
                'POST',
                []
            );

            $message = 'Reboot command submitted.';
        } elseif ($action === 'delete') {
            $statement = db()->prepare(
                'DELETE FROM firewalls WHERE id = ?'
            );

            $statement->execute([$id]);

            header('Location: /');
            exit;
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$systemStatus = null;
$firmwareStatus = null;

try {
    $systemStatus = opn_request(
        $firewall,
        'core/system/status'
    );
} catch (Throwable $exception) {
    $error = $error ?: $exception->getMessage();
}

try {
    /*
     * Read cached status only.
     * A full update check is started by the button.
     */
    $firmwareStatus = opn_request(
        $firewall,
        'core/firmware/status'
    );
} catch (Throwable $exception) {
    $firmwareStatus = [
        'error' => $exception->getMessage(),
    ];
}

require __DIR__ . '/inc/header.php';

?>

<div class="page-title">
    <div>
        <h1><?= h((string) $firewall['name']) ?></h1>
        <p><?= h((string) $firewall['base_url']) ?></p>
    </div>

    <a
        class="button secondary"
        target="_blank"
        rel="noopener"
        href="<?= h((string) $firewall['base_url']) ?>"
    >
        Open WebGUI
    </a>
</div>

<?php if ($message): ?>
    <div class="alert goodbox">
        <?= h($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert error">
        <?= h($error) ?>
    </div>
<?php endif; ?>

<div class="detail-grid">

    <section class="card">
        <h2>System</h2>

        <pre><?= h(
            json_encode(
                $systemStatus,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

    <section class="card">
        <h2>Firmware</h2>

        <pre><?= h(
            json_encode(
                $firmwareStatus,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

</div>

<form method="post" class="actions danger-zone">
    <input
        type="hidden"
        name="csrf"
        value="<?= h(csrf_token()) ?>"
    >

    <button name="action" value="firmware_check">
        Check for updates
    </button>

    <button
        class="warning"
        name="action"
        value="firmware_update"
        onclick="return confirm(
            'Install available firmware updates now? ' +
            'The firewall may reboot and temporarily become unavailable.'
        )"
    >
        Update now
    </button>

    <button name="action" value="backup">
        Download configuration backup
    </button>

    <button
        class="warning"
        name="action"
        value="reboot"
        onclick="return confirm('Really reboot this firewall?')"
    >
        Reboot firewall
    </button>

    <button
        class="danger"
        name="action"
        value="delete"
        onclick="return confirm(
            'Delete this firewall from OpnCentral?'
        )"
    >
        Delete entry
    </button>
</form>

<?php require __DIR__ . '/inc/footer.php'; ?>
