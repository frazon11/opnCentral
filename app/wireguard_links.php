<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function wg_rows(array $value): array
{
    if (isset($value['rows']) && is_array($value['rows'])) {
        return $value['rows'];
    }
    foreach ($value as $candidate) {
        if (is_array($candidate) && array_is_list($candidate)) {
            return $candidate;
        }
    }
    return [];
}

function wg_enabled(mixed $value): bool
{
    return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on', 'enabled'], true);
}

try {
    $currentId = (int) ($_GET['id'] ?? 0);
    if ($currentId < 1) {
        throw new RuntimeException('Invalid firewall ID.');
    }

    $firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
    $inventory = [];
    $errors = [];

    foreach ($firewalls as $firewall) {
        try {
            $clients = wg_rows(opn_request($firewall, 'wireguard/client/search_client', 'GET', [], 12));
            $servers = wg_rows(opn_request($firewall, 'wireguard/server/search_server', 'GET', [], 12));
            $inventory[(int) $firewall['id']] = [
                'firewall' => [
                    'id' => (int) $firewall['id'],
                    'name' => (string) $firewall['name'],
                ],
                'clients' => array_values(array_map(static function (array $row): array {
                    return [
                        'uuid' => (string) ($row['uuid'] ?? $row['id'] ?? ''),
                        'name' => (string) ($row['name'] ?? $row['description'] ?? ''),
                        'pubkey' => (string) ($row['pubkey'] ?? $row['public-key'] ?? $row['public_key'] ?? ''),
                        'enabled' => wg_enabled($row['enabled'] ?? '1'),
                    ];
                }, $clients)),
                'servers' => array_values(array_map(static function (array $row): array {
                    return [
                        'uuid' => (string) ($row['uuid'] ?? $row['id'] ?? ''),
                        'name' => (string) ($row['name'] ?? $row['description'] ?? ''),
                        'pubkey' => (string) ($row['pubkey'] ?? $row['public-key'] ?? $row['public_key'] ?? ''),
                        'enabled' => wg_enabled($row['enabled'] ?? '1'),
                    ];
                }, $servers)),
            ];
        } catch (Throwable $exception) {
            $errors[] = (string) $firewall['name'] . ': ' . $exception->getMessage();
        }
    }

    $links = [];
    $current = $inventory[$currentId] ?? null;
    if ($current !== null) {
        foreach ($current['clients'] as $localClient) {
            if ($localClient['uuid'] === '' || $localClient['pubkey'] === '') {
                continue;
            }
            foreach ($inventory as $remoteId => $remote) {
                if ($remoteId === $currentId) {
                    continue;
                }
                foreach ($remote['servers'] as $remoteServer) {
                    if ($remoteServer['pubkey'] !== $localClient['pubkey']) {
                        continue;
                    }
                    foreach ($current['servers'] as $localServer) {
                        if ($localServer['pubkey'] === '') {
                            continue;
                        }
                        foreach ($remote['clients'] as $remoteClient) {
                            if ($remoteClient['pubkey'] !== $localServer['pubkey'] || $remoteClient['uuid'] === '') {
                                continue;
                            }
                            $links[$localClient['pubkey']] = [
                                'managed' => true,
                                'local' => [
                                    'firewall_id' => $currentId,
                                    'firewall_name' => $current['firewall']['name'],
                                    'client_uuid' => $localClient['uuid'],
                                    'client_name' => $localClient['name'],
                                    'enabled' => $localClient['enabled'],
                                    'expected_peer_key' => $localClient['pubkey'],
                                    'server_key' => $localServer['pubkey'],
                                ],
                                'remote' => [
                                    'firewall_id' => $remoteId,
                                    'firewall_name' => $remote['firewall']['name'],
                                    'client_uuid' => $remoteClient['uuid'],
                                    'client_name' => $remoteClient['name'],
                                    'enabled' => $remoteClient['enabled'],
                                    'expected_peer_key' => $remoteClient['pubkey'],
                                    'server_key' => $remoteServer['pubkey'],
                                ],
                                'paired_enabled' => $localClient['enabled'] && $remoteClient['enabled'],
                                'partial_state' => $localClient['enabled'] !== $remoteClient['enabled'],
                            ];
                        }
                    }
                }
            }
        }
    }

    echo json_encode([
        'ok' => true,
        'links' => $links,
        'errors' => $errors,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
