opnCentral v0.4.4.1

- Added Settings → Updates.
- Public GitHub latest-release check for frazon11/opnCentral.
- Automatic checks enabled by default and limited to once every 24 hours.
- Manual Check now button bypasses the cache.
- Shows installed version, latest published version, last check time and update status.
- Links to the published GitHub release when available.
- State is persisted in /var/www/data/update-check.json.
- Uses short connection/request timeouts and never blocks normal page rendering.
- Sends no installation ID, firewall details, credentials, networks or VPN data.
