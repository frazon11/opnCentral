<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('POST required.');
    }

    require_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $firewall = firewall_by_id($id);

    if ($action !== 'firmware_check') {
        throw new RuntimeException('Unsupported action.');
    }

    $value = opn_request(
        $firewall,
        'core/firmware/status',
        'POST',
        [],
        120
    );

    echo json_encode(
        [
            'ok' => true,
            'value' => $value,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
} catch (Throwable $exception) {
    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' => $exception->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
