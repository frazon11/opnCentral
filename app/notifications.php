<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/mailer.php';
require_once __DIR__ . '/inc/alerts.php';
require_login();

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    try {
        smtp_send(
            '[opnCentral] Test email',
            "This is a test email from opnCentral.\n\nTime: " . date(DATE_RFC2822) . "\nHost: " . (gethostname() ?: 'opncentral')
        );
        $message = t('notifications.test_success');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

alerts_prepare_database();
$recent = db()->query('SELECT * FROM alert_log ORDER BY id DESC LIMIT 20')->fetchAll();
$config = smtp_config();
require __DIR__ . '/inc/header.php';
?>
<div class="page-title">
    <div>
        <h1><?= h(t('notifications.title')) ?></h1>
        <p><?= h(t('notifications.subtitle')) ?></p>
    </div>
</div>

<?php if ($message): ?><div class="alert goodbox"><?= h($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?= h($error) ?></div><?php endif; ?>

<div class="detail-grid">
    <section class="card">
        <h2><?= h(t('notifications.smtp_status')) ?></h2>
        <dl>
            <dt><?= h(t('notifications.enabled')) ?></dt>
            <dd><span class="badge <?= alerts_enabled() ? 'good' : 'bad' ?>"><?= alerts_enabled() ? h(t('common.online')) : h(t('common.offline')) ?></span></dd>
            <dt>SMTP</dt><dd><?= h($config['host'] ?: '—') ?>:<?= h((string)$config['port']) ?> (<?= h($config['security']) ?>)</dd>
            <dt><?= h(t('notifications.from')) ?></dt><dd><?= h($config['from'] ?: '—') ?></dd>
            <dt><?= h(t('notifications.recipients')) ?></dt><dd><?= h(implode(', ', $config['to']) ?: '—') ?></dd>
            <dt><?= h(t('notifications.interval')) ?></dt><dd><?= h(envv('ALERT_CHECK_INTERVAL', '300')) ?> s</dd>
            <dt><?= h(t('notifications.threshold')) ?></dt><dd><?= h(envv('ALERT_FAILURE_THRESHOLD', '2')) ?></dd>
        </dl>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
            <button type="submit"><?= h(t('notifications.send_test')) ?></button>
        </form>
    </section>

    <section class="card">
        <h2><?= h(t('notifications.active_alerts')) ?></h2>
        <p><?= h(t('notifications.alert_types')) ?></p>
        <p class="muted"><?= h(t('notifications.env_help')) ?></p>
    </section>

    <section class="card wide">
        <h2><?= h(t('notifications.recent')) ?></h2>
        <?php if (!$recent): ?>
            <p class="muted"><?= h(t('notifications.none')) ?></p>
        <?php else: ?>
            <div style="overflow:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead><tr><th style="text-align:left;padding:8px">Time</th><th style="text-align:left;padding:8px">Event</th><th style="text-align:left;padding:8px">Subject</th><th style="text-align:left;padding:8px">Result</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td style="padding:8px"><?= h((string)$row['created_at']) ?></td>
                            <td style="padding:8px"><?= h((string)$row['event_type']) ?></td>
                            <td style="padding:8px"><?= h((string)$row['subject']) ?></td>
                            <td style="padding:8px"><span class="badge <?= (int)$row['sent_ok'] === 1 ? 'good' : 'bad' ?>"><?= (int)$row['sent_ok'] === 1 ? 'Sent' : h((string)$row['error']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/inc/footer.php'; ?>
