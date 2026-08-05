<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';
require_once __DIR__ . '/inc/opnsense.php';
require_login();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function ids_bool(mixed $value): string
{
    return in_array(strtolower(trim((string) $value)), ['1','true','yes','on','enabled'], true)
        ? '1'
        : '0';
}

function ids_set_recursive(array &$node, array $keys, string $value): bool
{
    foreach ($node as $key => &$child) {
        if (in_array(strtolower((string) $key), $keys, true)) {
            $child = $value;
            return true;
        }
        if (is_array($child) && ids_set_recursive($child, $keys, $value)) {
            return true;
        }
    }
    unset($child);
    return false;
}

function ids_selected_firewalls(array $ids): array
{
    if ($ids === []) {
        throw new RuntimeException('Select at least one firewall.');
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = db()->prepare(
        'SELECT * FROM firewalls WHERE id IN (' . $placeholders . ') ORDER BY name'
    );
    $statement->execute($ids);
    return $statement->fetchAll();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('POST required.');
    }
    require_csrf();
    require_configuration_unlocked();

    $action = trim((string) ($_POST['action'] ?? ''));
    $firewallIds = array_values(array_unique(array_filter(
        array_map('intval', (array) ($_POST['firewall_ids'] ?? [])),
        static fn(int $id): bool => $id > 0
    )));
    $firewalls = ids_selected_firewalls($firewallIds);
    $results = [];

    foreach ($firewalls as $firewall) {
        $entry = [
            'id' => (int) $firewall['id'],
            'name' => (string) $firewall['name'],
            'ok' => false,
            'message' => '',
        ];

        try {
            if ($action === 'set_ids') {
                $enabled = ids_bool($_POST['enabled'] ?? '0');
                $mode = trim((string) ($_POST['capture_mode'] ?? 'keep'));
                $settings = opn_raw_request($firewall, 'ids/settings/get', 'GET', null, 15);

                $changed = ids_set_recursive($settings, ['enabled'], $enabled);
                if (!$changed) {
                    throw new RuntimeException('The IDS enabled field was not found in the OPNsense response.');
                }

                if ($mode !== 'keep') {
                    $modeChanged = ids_set_recursive($settings, ['capture_mode','capturemode'], $mode);
                    if (!$modeChanged) {
                        $legacy = $mode === 'pcap' ? '0' : '1';
                        $modeChanged = ids_set_recursive($settings, ['ips_mode','ipsmode'], $legacy);
                    }
                    if (!$modeChanged) {
                        throw new RuntimeException('No supported IDS/IPS capture-mode field was found.');
                    }
                }

                opn_raw_request($firewall, 'ids/settings/set', 'POST', $settings, 20);
                opn_raw_request($firewall, 'ids/service/reconfigure', 'POST', [], 45);
                $entry['message'] = $enabled === '1'
                    ? 'IDS configuration enabled and applied.'
                    : 'IDS configuration disabled and applied.';
            } elseif ($action === 'toggle_rulesets') {
                $rulesets = array_values(array_unique(array_filter(array_map(
                    static fn(mixed $value): string => trim((string) $value),
                    (array) ($_POST['rulesets'] ?? [])
                ))));
                if ($rulesets === []) {
                    throw new RuntimeException('Select at least one ruleset.');
                }
                $enabled = ids_bool($_POST['enabled'] ?? '0');
                $filenames = implode(',', $rulesets);
                opn_raw_request(
                    $firewall,
                    'ids/settings/toggle_ruleset/' . rawurlencode($filenames) . '/' . $enabled,
                    'POST', [], 30
                );
                opn_raw_request($firewall, 'ids/service/reload_rules', 'POST', [], 45);
                $entry['message'] = count($rulesets) . ' ruleset(s) updated and reloaded.';
            } elseif ($action === 'update_rules') {
                opn_raw_request($firewall, 'ids/service/update_rules/1', 'POST', [], 180);
                $entry['message'] = 'Rules downloaded and reloaded.';
            } elseif ($action === 'deploy_policy') {
                $description = trim((string) ($_POST['description'] ?? ''));
                if ($description === '') {
                    throw new RuntimeException('Policy description is required.');
                }
                $priority = max(0, (int) ($_POST['priority'] ?? 0));
                $policyAction = strtolower(trim((string) ($_POST['action_value'] ?? 'alert')));
                if (!in_array($policyAction, ['alert','drop','reject','pass'], true)) {
                    throw new RuntimeException('Unsupported policy action.');
                }
                $rulesets = array_values(array_unique(array_filter(array_map(
                    static fn(string $value): string => trim($value),
                    explode(',', (string) ($_POST['rulesets'] ?? ''))
                ))));
                $uuid = trim((string) ($_POST['policy_uuid'] ?? ''));
                $payload = [
                    'policy' => [
                        'enabled' => ids_bool($_POST['enabled'] ?? '1'),
                        'priority' => (string) $priority,
                        'action' => $policyAction,
                        'new_action' => $policyAction,
                        'rulesets' => implode(',', $rulesets),
                        'description' => $description,
                    ],
                ];
                if ($uuid === '') {
                    $response = opn_raw_request($firewall, 'ids/settings/add_policy', 'POST', $payload, 30);
                    $createdUuid = trim((string) ($response['uuid'] ?? ''));
                    $entry['message'] = 'Policy created' . ($createdUuid !== '' ? ' (' . $createdUuid . ')' : '') . ' and applied.';
                } else {
                    opn_raw_request(
                        $firewall,
                        'ids/settings/set_policy/' . rawurlencode($uuid),
                        'POST', $payload, 30
                    );
                    $entry['message'] = 'Policy updated and applied.';
                }
                opn_raw_request($firewall, 'ids/service/reconfigure', 'POST', [], 60);
            } else {
                throw new RuntimeException('Unknown IDS action.');
            }

            $entry['ok'] = true;
        } catch (Throwable $exception) {
            $entry['message'] = $exception->getMessage();
        }

        $results[] = $entry;
    }

    echo json_encode(
        ['ok' => true, 'results' => $results],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    );
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode(
        ['ok' => false, 'error' => $exception->getMessage()],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}
