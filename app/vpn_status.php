<?php

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('html_errors', '0');
ob_start();

register_shutdown_function(static function (): void {
    $error = error_get_last();

    if ($error === null || !in_array(
        $error['type'],
        [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR],
        true
    )) {
        return;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    echo json_encode(
        [
            'ok' => false,
            'error' => 'PHP fatal error: ' . $error['message'],
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
});

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$id = (int) ($_GET['id'] ?? 0);
$type = (string) ($_GET['type'] ?? 'all');

function vpn_try_request(
    array $firewall,
    string $path,
    int $timeout = 20
): array {
    try {
        return [
            'ok' => true,
            'value' => opn_request(
                $firewall,
                $path,
                'GET',
                [],
                $timeout
            ),
        ];
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'error' => $exception->getMessage(),
        ];
    }
}

try {
    $firewall = firewall_by_id($id);

    $result = [
        'ok' => true,
        'type' => $type,
        'data' => [],
    ];

    if ($type === 'wireguard' || $type === 'all') {
        $result['data']['wireguard'] = [
            'service' => vpn_try_request(
                $firewall,
                'wireguard/service/status',
                15
            ),
            'tunnels' => vpn_try_request(
                $firewall,
                'wireguard/service/show',
                20
            ),
        ];
    }

    if ($type === 'ipsec' || $type === 'all') {
        $result['data']['ipsec'] = [
            'service' => vpn_try_request(
                $firewall,
                'ipsec/service/status',
                15
            ),
            'phase1' => vpn_try_request(
                $firewall,
                'ipsec/sessions/search_phase1',
                20
            ),
            'phase2' => vpn_try_request(
                $firewall,
                'ipsec/sessions/search_phase2',
                20
            ),
        ];
    }

    if ($type === 'openvpn' || $type === 'all') {
        $result['data']['openvpn'] = [
            'sessions' => vpn_try_request(
                $firewall,
                'openvpn/service/search_sessions',
                20
            ),
            'routes' => vpn_try_request(
                $firewall,
                'openvpn/service/search_routes',
                20
            ),
        ];
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    echo json_encode(
        $result,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
} catch (Throwable $exception) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code(500);

    echo json_encode(
        [
            'ok' => false,
            'error' => $exception->getMessage(),
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
