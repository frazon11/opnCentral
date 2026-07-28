<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';

require_login();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function overview_wg_rows(array $value): array
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

function overview_wg_enabled(mixed $value): bool
{
    return in_array(
        strtolower(trim((string) $value)),
        ['1', 'true', 'yes', 'on', 'enabled'],
        true
    );
}

try {
    $firewalls = db()->query('SELECT * FROM firewalls ORDER BY name')->fetchAll();
    $inventory = [];
    $errors = [];
    $cacheHit = false;
    $cacheTtl = 30;
    $cachePath = wireguard_inventory_cache_path();

    if (
        is_file($cachePath) &&
        filemtime($cachePath) !== false &&
        time() - (int) filemtime($cachePath) < $cacheTtl
    ) {
        $cached = json_decode((string) file_get_contents($cachePath), true);
        if (is_array($cached) && is_array($cached['inventory'] ?? null)) {
            $inventory = $cached['inventory'];
            $errors = is_array($cached['errors'] ?? null) ? $cached['errors'] : [];
            $cacheHit = true;
        }
    }

    if (!$cacheHit) {
        $requests = [];

        foreach ($firewalls as $firewall) {
            $id = (int) $firewall['id'];
            $inventory[$id] = [
                'firewall' => [
                    'id' => $id,
                    'name' => (string) $firewall['name'],
                ],
                'clients' => [],
                'servers' => [],
            ];
            $requests[$id . '.clients'] = [
                'firewall' => $firewall,
                'path' => 'wireguard/client/search_client',
                'timeout' => 12,
            ];
            $requests[$id . '.servers'] = [
                'firewall' => $firewall,
                'path' => 'wireguard/server/search_server',
                'timeout' => 12,
            ];
        }

        $parallel = opn_requests_parallel($requests);

        foreach ($firewalls as $firewall) {
            $id = (int) $firewall['id'];
            $clientResult = $parallel[$id . '.clients'] ?? [];
            $serverResult = $parallel[$id . '.servers'] ?? [];

            if (($clientResult['ok'] ?? false) !== true) {
                $errors[] = $firewall['name'] . ' clients: ' .
                    ($clientResult['error'] ?? 'Unavailable');
            }
            if (($serverResult['ok'] ?? false) !== true) {
                $errors[] = $firewall['name'] . ' servers: ' .
                    ($serverResult['error'] ?? 'Unavailable');
            }

            $clients = ($clientResult['ok'] ?? false) === true
                ? overview_wg_rows($clientResult['value'])
                : [];
            $servers = ($serverResult['ok'] ?? false) === true
                ? overview_wg_rows($serverResult['value'])
                : [];

            $inventory[$id]['clients'] = array_values(array_map(
                static fn(array $row): array => [
                    'uuid' => (string) ($row['uuid'] ?? $row['id'] ?? ''),
                    'name' => (string) ($row['name'] ?? $row['description'] ?? ''),
                    'pubkey' => (string) ($row['pubkey'] ?? $row['public-key'] ?? $row['public_key'] ?? ''),
                    'enabled' => overview_wg_enabled($row['enabled'] ?? '1'),
                ],
                $clients
            ));

            $inventory[$id]['servers'] = array_values(array_map(
                static fn(array $row): array => [
                    'uuid' => (string) ($row['uuid'] ?? $row['id'] ?? ''),
                    'name' => (string) ($row['name'] ?? $row['description'] ?? ''),
                    'pubkey' => (string) ($row['pubkey'] ?? $row['public-key'] ?? $row['public_key'] ?? ''),
                    'enabled' => overview_wg_enabled($row['enabled'] ?? '1'),
                ],
                $servers
            ));
        }

        @file_put_contents(
            $cachePath,
            json_encode(
                ['inventory' => $inventory, 'errors' => $errors],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ),
            LOCK_EX
        );
    }

    $connections = [];
    $seen = [];

    foreach ($inventory as $localId => $local) {
        foreach ($local['clients'] as $localClient) {
            if ($localClient['uuid'] === '' || $localClient['pubkey'] === '') {
                continue;
            }

            foreach ($inventory as $remoteId => $remote) {
                if ((int) $remoteId === (int) $localId) {
                    continue;
                }

                foreach ($remote['servers'] as $remoteServer) {
                    if ($remoteServer['pubkey'] !== $localClient['pubkey']) {
                        continue;
                    }

                    foreach ($local['servers'] as $localServer) {
                        if ($localServer['pubkey'] === '') {
                            continue;
                        }

                        foreach ($remote['clients'] as $remoteClient) {
                            if (
                                $remoteClient['uuid'] === '' ||
                                $remoteClient['pubkey'] !== $localServer['pubkey']
                            ) {
                                continue;
                            }

                            $pairIds = [(int) $localId, (int) $remoteId];
                            sort($pairIds, SORT_NUMERIC);
                            $pairKey = implode(':', $pairIds) . ':' .
                                min($localClient['uuid'], $remoteClient['uuid']) . ':' .
                                max($localClient['uuid'], $remoteClient['uuid']);

                            if (isset($seen[$pairKey])) {
                                continue;
                            }
                            $seen[$pairKey] = true;

                            $partial = $localClient['enabled'] !== $remoteClient['enabled'];
                            $enabled = $localClient['enabled'] && $remoteClient['enabled'];

                            $connections[] = [
                                'pair_key' => $pairKey,
                                'status' => $partial
                                    ? 'partial'
                                    : ($enabled ? 'enabled' : 'disabled'),
                                'local' => [
                                    'firewall_id' => (int) $localId,
                                    'firewall_name' => (string) $local['firewall']['name'],
                                    'client_uuid' => $localClient['uuid'],
                                    'client_name' => $localClient['name'],
                                    'enabled' => $localClient['enabled'],
                                    'expected_peer_key' => $localClient['pubkey'],
                                ],
                                'remote' => [
                                    'firewall_id' => (int) $remoteId,
                                    'firewall_name' => (string) $remote['firewall']['name'],
                                    'client_uuid' => $remoteClient['uuid'],
                                    'client_name' => $remoteClient['name'],
                                    'enabled' => $remoteClient['enabled'],
                                    'expected_peer_key' => $remoteClient['pubkey'],
                                ],
                            ];
                        }
                    }
                }
            }
        }
    }

    usort(
        $connections,
        static fn(array $a, array $b): int =>
            strcasecmp(
                $a['local']['firewall_name'] . $a['remote']['firewall_name'],
                $b['local']['firewall_name'] . $b['remote']['firewall_name']
            )
    );

    echo json_encode([
        'ok' => true,
        'connections' => $connections,
        'errors' => $errors,
        'cache' => ['hit' => $cacheHit, 'ttl' => $cacheTtl],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(
        ['ok' => false, 'error' => $exception->getMessage()],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
