opnCentral v0.4.2.5 - Managed WireGuard peer-pair control

- Detects reciprocal WireGuard peer configurations between managed OPNsense firewalls using public keys.
- Adds Enable both sides / Disable both sides only when a reciprocal managed match is proven.
- Toggles only the two peer records; WireGuard instances and unrelated peers remain unchanged.
- Reconfigures both WireGuard services.
- Verifies the resulting state and attempts rollback if the second firewall fails.
- Shows partial-state warning when only one side is enabled.
