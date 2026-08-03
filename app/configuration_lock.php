<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('html_errors', '0');
ob_start();

require_once __DIR__ . '/inc/config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('POST required.');
    }

    require_csrf();

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'unlock') {
        $password = (string) ($_POST['password'] ?? '');

        if (!unlock_configuration($password)) {
            http_response_code(403);
            throw new RuntimeException('Incorrect unlock password.');
        }

        $message = 'Remote configuration changes are unlocked.';
    } elseif ($action === 'lock') {
        lock_configuration();
        $message = 'Remote configuration changes are locked.';
    } else {
        throw new RuntimeException('Unsupported lock action.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode(
        [
            'ok' => true,
            'unlocked' => configuration_unlocked(),
            'message' => $message,
        ],
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE |
        JSON_THROW_ON_ERROR
    );
} catch (Throwable $exception) {
    if (http_response_code() < 400) {
        http_response_code(400);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode(
        [
            'ok' => false,
            'unlocked' => configuration_unlocked(),
            'error' => $exception->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
