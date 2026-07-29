opnCentral v0.4.5.0

Backup & Restore for opnCentral:
- Added Settings → Backup & Restore.
- Creates a consistent SQLite snapshot using VACUUM INTO.
- Includes application state from /var/www/data.
- Optionally includes stored OPNsense backups from /var/www/backups.
- Adds manifest.json with version, timestamp, sizes and SHA-256 hashes.
- APP_KEY is never included in downloadable archives.
- Restore refuses an archive when its APP_KEY fingerprint does not match.
- Validates archive paths to prevent ZIP path traversal.
- Verifies every file against the manifest before restoring.
- Checks the restored SQLite database with PRAGMA integrity_check.
- Creates a persistent safety archive before replacing current data.
- Safety archives are kept under /var/www/backups/opncentral-self.
- Restore preserves the safety-backup directory.
- Restored state requires a container restart/recreation.
- Container upload limit increased to 1 GiB.
