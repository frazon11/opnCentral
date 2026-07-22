<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$id = (int) ($_GET['id'] ?? 0);
$type = (string) ($_GET['type'] ?? 'all');

try {
    $firewall = firewall_by_id($id);

    $result = [
        'ok' => true,
        'type' => $type,
        'data' => [],
    ];

    if ($type === 'system' || $type === 'all') {
        try {
            $result['data']['system'] = [
                'ok' => true,
                'value' => opn_request(
                    $firewall,
                    'core/system/status',
                    'GET',
                    [],
                    10
                ),
            ];
        } catch (Throwable $exception) {
            $result['data']['system'] = [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    if ($type === 'firmware' || $type === 'all') {
        try {
            $result['data']['firmware'] = [
                'ok' => true,
                'value' => opn_request(
                    $firewall,
                    'core/firmware/status',
                    'GET',
                    [],
                    15
                ),
            ];
        } catch (Throwable $exception) {
            $result['data']['firmware'] = [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    if ($type === 'upgrade' || $type === 'all') {
        try {
            $result['data']['upgrade'] = [
                'ok' => true,
                'value' => opn_request(
                    $firewall,
                    'core/firmware/upgradestatus',
                    'GET',
                    [],
                    10
                ),
            ];
        } catch (Throwable $exception) {
            $result['data']['upgrade'] = [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    echo json_encode(
        $result,
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
