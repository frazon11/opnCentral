opnCentral v0.4.3.0

Major changes:
- OPNsense-inspired fixed sidebar and top toolbar.
- Compact OPNsense-style panels, tables, buttons and navigation.
- New persistent Backups page and backup history.
- Backup all managed firewalls in parallel.
- Download all successful backups as one ZIP.
- Automatic verified pre-change backups before:
  * WireGuard peer-pair enable/disable on both affected firewalls
  * alias distribution
  * category distribution
  * firmware update and major upgrade
- Configuration changes are cancelled when the required backup fails.
- SHA-256 and minimum-size integrity checks.
- Configurable pre-change retention, default 20 per firewall.
- Backup metadata stored in SQLite.
