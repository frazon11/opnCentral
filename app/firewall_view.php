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

        /*
         * Configuration backup
         */
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
                (string) $f['name']
            );

            $filename =
                BACKUP_DIR . '/' .
                $safeName . '-' .
                gmdate('Ymd-His') .
                '.xml';

            /*
             * "this" downloads the currently active configuration.
             */
            $backupData = opn_download(
                $f,
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

            $msg = 'Backup saved: ' . basename($filename);
        }

        /*
         * Force a new firmware update check
         */
        elseif ($action === 'firmware_check') {
            opn_request(
                $f,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            $msg = 'Firmware update check completed.';
        }

        /*
         * Install available regular firmware updates
         */
        elseif ($action === 'firmware_update') {
            /*
             * First refresh the firmware information.
             */
            opn_request(
                $f,
                'core/firmware/status',
                'POST',
                [],
                90
            );

            /*
             * Start the firmware update.
             * OPNsense continues the update asynchronously.
             */
            $updateResult = opn_request(
                $f,
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

            $msg =
                'Firmware update started: ' .
                (string) $updateMessage .
                ' The firewall may reboot and temporarily become unavailable.';
        }

        /*
         * Reboot firewall
         */
        elseif ($action === 'reboot') {
            opn_request(
                $f,
                'core/system/reboot',
                'POST',
                []
            );

            $msg = 'Reboot command submitted.';
        }

        /*
         * Remove firewall from OpnCentral
         */
        elseif ($action === 'delete') {
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

/*
 * Load current information
 */
$systemStatus = null;
$firmwareStatus = null;
$services = null;
$upgradeStatus = null;

/*
 * System status
 */
try {
    $systemStatus = opn_request(
        $f,
        'core/system/status'
    );
} catch (Throwable $exception) {
    $err = $err ?: $exception->getMessage();
}

/*
 * Cached firmware status
 */
try {
    $firmwareStatus = opn_request(
        $f,
        'core/firmware/status'
    );
} catch (Throwable $exception) {
    $firmwareStatus = [
        'error' => $exception->getMessage(),
    ];
}

/*
 * Firmware update progress
 */
try {
    $upgradeStatus = opn_request(
        $f,
        'core/firmware/upgradestatus'
    );
} catch (Throwable $exception) {
    $upgradeStatus = [
        'error' => $exception->getMessage(),
    ];
}

/*
 * Service status
 */
try {
    $services = opn_request(
        $f,
        'core/service/search'
    );
} catch (Throwable $exception) {
    $services = [
        'error' => $exception->getMessage(),
    ];
}

require __DIR__ . '/inc/header.php';

?>

<div class="page-title">
    <div>
        <h1><?= h((string) $f['name']) ?></h1>
        <p><?= h((string) $f['base_url']) ?></p>
    </div>

    <a
        class="button secondary"
        target="_blank"
        rel="noopener"
        href="<?= h((string) $f['base_url']) ?>"
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

    <section class="card">
        <h2>Update status</h2>

        <pre><?= h(
            json_encode(
                $upgradeStatus,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            ) ?: 'Unavailable'
        ) ?></pre>
    </section>

    <section class="card wide">
        <h2>Services</h2>

        <pre><?= h(
            json_encode(
                $services,
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
        onclick="return confirm(
            'Really reboot this firewall?'
        )"
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