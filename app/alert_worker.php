<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/alerts.php';

$lockFile = DATA_DIR . '/alert-worker.lock';
$lock = fopen($lockFile, 'c');
if (!is_resource($lock) || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0);
}

$interval = max(60, (int) envv('ALERT_CHECK_INTERVAL', '300'));
while (true) {
    try {
        run_alert_checks();
    } catch (Throwable $exception) {
        error_log('[opnCentral alerts] ' . $exception->getMessage());
    }
    sleep($interval);
}
