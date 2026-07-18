<?php

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

$firewalls = db()
    ->query('SELECT * FROM firewalls ORDER BY name')
    ->fetchAll();

require __DIR__ . '/inc/header.php';

?>

<div class="page-title">
    <div>
        <h1>Firewalls</h1>
        <p>Central status, backups and maintenance.</p>
    </div>

    <a class="button" href="/firewall_edit.php">
        Add firewall
    </a>
</div>

<?php if (!$firewalls): ?>

    <div class="empty">
        No firewalls configured.
    </div>

<?php else: ?>

    <div class="grid">

        <?php foreach ($firewalls as $firewall): ?>

            <?php

            $systemStatus = null;
            $firmwareStatus = null;
            $error = null;

            try {
                $systemStatus = opn_request(
                    $firewall,
                    'core/system/status'
                );

                try {
                    /*
                     * Only read the cached firmware status here.
                     * Do not trigger a full firmware check on every
                     * dashboard page load.
                     */
                    $firmwareStatus = opn_request(
                        $firewall,
                        'core/firmware/status'
                    );
                } catch (Throwable $exception) {
                    $firmwareStatus = [
                        'status_msg' => $exception->getMessage(),
                    ];
                }
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }

            ?>

            <article class="card">

                <div class="card-head">

                    <div>
                        <h2><?= h((string) $firewall['name']) ?></h2>

                        <a
                            class="muted"
                            target="_blank"
                            rel="noopener"
                            href="<?= h((string) $firewall['base_url']) ?>"
                        >
                            <?= h((string) $firewall['base_url']) ?>
                        </a>
                    </div>

                    <span class="badge <?= $error ? 'bad' : 'good' ?>">
                        <?= $error ? 'Offline' : 'Online' ?>
                    </span>

                </div>

                <?php if ($error): ?>

                    <div class="alert error">
                        <?= h($error) ?>
                    </div>

                <?php else: ?>

                    <dl>
                        <dt>Status</dt>

                        <dd>
                            <?= h(
                                (string) (
                                    $systemStatus['status']
                                    ?? $systemStatus['result']
                                    ?? 'reachable'
                                )
                            ) ?>
                        </dd>

                        <dt>Firmware</dt>

                        <dd>
                            <?= h(
                                (string) (
                                    $firmwareStatus['product_version']
                                    ?? $firmwareStatus['status_msg']
                                    ?? 'API reachable'
                                )
                            ) ?>
                        </dd>
                    </dl>

                <?php endif; ?>

                <div class="actions">

                    <a
                        class="button secondary"
                        href="/firewall_view.php?id=<?= (int) $firewall['id'] ?>"
                    >
                        Details
                    </a>

                    <a
                        class="button secondary"
                        href="/firewall_edit.php?id=<?= (int) $firewall['id'] ?>"
                    >
                        Edit
                    </a>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php require __DIR__ . '/inc/footer.php'; ?>
